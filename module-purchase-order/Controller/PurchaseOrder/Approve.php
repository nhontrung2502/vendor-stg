<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Action\Context as AppContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Processor\ApprovalProcessorInterface;
use Magento\PurchaseOrder\Model\PurchaseOrderBulkManagement;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Controller for purchase order approve action.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Approve extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var ApprovalProcessorInterface
     */
    private $purchaseOrderApprovalsProcessor;

    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @var PurchaseOrderBulkManagement
     */
    private $purchaseOrderBulkManagement;

    /**
     * Approve constructor.
     *
     * @param AppContext                       $context
     * @param CompanyContext                   $companyContext
     * @param Authorization                    $authorization
     * @param PurchaseOrderBulkManagement      $purchaseOrderBulkManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param ApprovalProcessorInterface       $purchaseOrderApprovalsProcessor
     * @param CompanyAdminPermission           $companyAdminPermission
     * @param CustomerRepository               $customerRepository
     * @param CommentManagement                $commentManagement
     */
    public function __construct(
        AppContext $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        PurchaseOrderBulkManagement $purchaseOrderBulkManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        ApprovalProcessorInterface $purchaseOrderApprovalsProcessor,
        CompanyAdminPermission $companyAdminPermission,
        CustomerRepository $customerRepository,
        CommentManagement $commentManagement
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->purchaseOrderBulkManagement = $purchaseOrderBulkManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->purchaseOrderApprovalsProcessor = $purchaseOrderApprovalsProcessor;
        $this->companyAdminPermission = $companyAdminPermission;
        $this->customerRepository = $customerRepository;
        $this->commentManagement = $commentManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        try {
            $customerId = $this->companyContext->getCustomerId();

            if ($this->isMassApprovalRequest()) {
                $this->processMassApproval();
                return $resultRedirect;
            }

            $purchaseOrder = $this->getPurchaseOrder();
            $this->purchaseOrderApprovalsProcessor->processApproval($purchaseOrder, (int)$customerId);

            // If there is a comment present in the request include it as part of the operation
            if ($this->getRequest()->getParam('comment')) {
                $this->commentManagement->addComment(
                    $purchaseOrder->getEntityId(),
                    $this->companyContext->getCustomerId(),
                    $this->getRequest()->getParam('comment')
                );
            }

            if ($purchaseOrder->getStatus() === PurchaseOrderInterface::STATUS_APPROVED) {
                $this->messageManager->addSuccessMessage(__('Purchase order has been successfully approved.'));
            } else {
                $customer = $this->customerRepository->getById($customerId);
                $this->messageManager->addSuccessMessage(
                    __('Purchase order has been approved by ') .
                        $customer->getFirstname() . ' ' . $customer->getLastname()
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to approve purchase order.'));
        }
        return $resultRedirect;
    }

    /**
     * Get current purchase order.
     *
     * @return PurchaseOrderInterface
     * @throws NoSuchEntityException
     */
    private function getPurchaseOrder() : PurchaseOrderInterface
    {
        $requestId = (int)$this->getRequest()->getParam('request_id');
        return $this->purchaseOrderRepository->getById($requestId);
    }

    /**
     * Perform mass approve if called from the orders grid
     *
     * @return void
     * @throws LocalizedException
     */
    private function processMassApproval()
    {
        $processedPurchaseOrders = $this->purchaseOrderBulkManagement->approvePurchaseOrders(
            (int)$this->companyContext->getCustomerId()
        );

        if (!empty($processedPurchaseOrders[PurchaseOrderInterface::STATUS_APPROVED])) {
            $approvedOrders = count($processedPurchaseOrders[PurchaseOrderInterface::STATUS_APPROVED]);
            $message = $approvedOrders === 1 ?
                    __("%1 Purchase Order has been successfully approved", $approvedOrders) :
                    __("%1 Purchase Orders have been successfully approved", $approvedOrders);
            $this->messageManager->addSuccessMessage($message);
        }

        if (!empty($processedPurchaseOrders[PurchaseOrderBulkManagement::FAILED_KEY])) {
            foreach ($processedPurchaseOrders[PurchaseOrderBulkManagement::FAILED_KEY] as $orderId) {
                $this->messageManager->addErrorMessage(
                    __("The Purchase Order #%1 couldn't be approved", $orderId)
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function isAllowed()
    {
        try {
            $requestId = (int)$this->getRequest()->getParam('request_id');

            if ($requestId) {
                return $this->purchaseOrderActionAuth->isAllowed('approve', $this->getPurchaseOrder());
            } elseif ($this->isMassApprovalRequest()) {
                return true;
            }
            return false;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Check if mass approval requested
     *
     * @return bool
     */
    private function isMassApprovalRequest(): bool
    {
        return $this->getRequest()->getParam(Filter::SELECTED_PARAM) ||
                $this->getRequest()->getParam(Filter::EXCLUDED_PARAM);
    }
}
