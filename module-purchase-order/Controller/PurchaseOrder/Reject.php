<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context as AppContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\PurchaseOrderBulkManagement;
use Magento\PurchaseOrder\Model\PurchaseOrderManagement;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Controller for purchase order reject action.
 */
class Reject extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var PurchaseOrderManagement
     */
    private $purchaseOrderManagement;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @var PurchaseOrderBulkManagement
     */
    private $purchaseOrderBulkManagement;

    /**
     * Reject constructor.
     *
     * @param AppContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $purchaseOrderActionAuth
     * @param PurchaseOrderBulkManagement $purchaseOrderBulkManagement
     * @param PurchaseOrderManagement $purchaseOrderManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CommentManagement $commentManagement
     */
    public function __construct(
        AppContext $context,
        CompanyContext $companyContext,
        Authorization $purchaseOrderActionAuth,
        PurchaseOrderBulkManagement $purchaseOrderBulkManagement,
        PurchaseOrderManagement $purchaseOrderManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CommentManagement $commentManagement
    ) {
        parent::__construct($context, $companyContext, $purchaseOrderActionAuth);
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->commentManagement = $commentManagement;
        $this->purchaseOrderBulkManagement = $purchaseOrderBulkManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $purchaseOrderId = (int)$this->getRequest()->getParam('request_id');

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        try {

            if ($this->isMassRejectRequest()) {
                $this->processMassReject();
                return $resultRedirect;
            }

            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
            $this->purchaseOrderManagement->rejectPurchaseOrder($purchaseOrder, $this->companyContext->getCustomerId());
            $this->messageManager->addSuccessMessage(__('Purchase order has been successfully rejected.'));

            // If there is a comment present in the request include it as part of the operation
            if ($this->getRequest()->getParam('comment')) {
                $this->commentManagement->addComment(
                    $purchaseOrder->getEntityId(),
                    $this->companyContext->getCustomerId(),
                    $this->getRequest()->getParam('comment')
                );
            }

            return $resultRedirect;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to reject purchase order.'));
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
     * Perform mass reject if called from the orders grid
     *
     * @return void
     * @throws LocalizedException
     */
    private function processMassReject()
    {
        $processedPurchaseOrders = $this->purchaseOrderBulkManagement->rejectPurchaseOrders(
            (int)$this->companyContext->getCustomerId()
        );

        if (!empty($processedPurchaseOrders[PurchaseOrderInterface::STATUS_REJECTED])) {
            $approvedOrders = count($processedPurchaseOrders[PurchaseOrderInterface::STATUS_REJECTED]);
            $message = $approvedOrders === 1 ?
                    __("%1 Purchase Order has been successfully rejected", $approvedOrders) :
                    __("%1 Purchase Orders have been successfully rejected", $approvedOrders);
            $this->messageManager->addSuccessMessage($message);
        }

        if (!empty($processedPurchaseOrders[PurchaseOrderBulkManagement::FAILED_KEY])) {
            foreach ($processedPurchaseOrders[PurchaseOrderBulkManagement::FAILED_KEY] as $orderId) {
                $this->messageManager->addErrorMessage(
                    __("The Purchase Order #%1 couldn't be rejected", $orderId)
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
                return $this->purchaseOrderActionAuth->isAllowed('reject', $this->getPurchaseOrder());
            } elseif ($this->isMassRejectRequest()) {
                return true;
            }
            return false;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Check if mass reject requested
     *
     * @return bool
     */
    private function isMassRejectRequest(): bool
    {
        return $this->getRequest()->getParam(Filter::SELECTED_PARAM) ||
                $this->getRequest()->getParam(Filter::EXCLUDED_PARAM);
    }
}
