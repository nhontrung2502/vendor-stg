<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategy;

/**
 * Place Order Controller
 */
class PlaceOrder extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var PurchaseOrderManagementInterface
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
     * @var DeferredPaymentStrategy
     */
    private $deferredPaymentStrategy;

    /**
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param Authorization $purchaseOrderActionAuth
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CommentManagement $commentManagement
     * @param DeferredPaymentStrategy|null $deferredPaymentStrategy
     */
    public function __construct(
        Context $context,
        CompanyContext $companyContext,
        Authorization $purchaseOrderActionAuth,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CommentManagement $commentManagement,
        DeferredPaymentStrategy $deferredPaymentStrategy = null
    ) {
        parent::__construct($context, $companyContext, $purchaseOrderActionAuth);
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->commentManagement = $commentManagement;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy
            ?? ObjectManager::getInstance()->get(DeferredPaymentStrategy::class);
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $purchaseOrderId = $this->_request->getParam('request_id');
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);

            if ($this->_request->getParam('payment_redirect') !== null &&
                $purchaseOrder->getStatus() === PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT &&
                $this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder) &&
                (int)$purchaseOrder->getCreatorId() === (int)$this->companyContext->getCustomerId()
            ) {
                return $resultRedirect->setPath(
                    'checkout/index/index',
                    ['purchaseOrderId' => $purchaseOrder->getEntityId()]
                );
            }
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            $order = $this->purchaseOrderManagement->createSalesOrder(
                $purchaseOrder,
                $this->companyContext->getCustomerId()
            );

            // If there is a comment present in the request include it as part of the operation
            if ($this->getRequest()->getParam('comment')) {
                $this->commentManagement->addComment(
                    $purchaseOrder->getEntityId(),
                    $this->companyContext->getCustomerId(),
                    $this->getRequest()->getParam('comment')
                );
            }
            $this->messageManager->addSuccessMessage(
                __(
                    'Successfully placed order #%1 from purchase order #%2.',
                    $order->getIncrementId(),
                    $purchaseOrder->getIncrementId()
                )
            );
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $resultRedirect;
    }

    /**
     * @inheritDoc
     */
    protected function isAllowed()
    {
        $purchaseOrderId = $this->_request->getParam('request_id');
        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
        } catch (NoSuchEntityException $exception) {
            return false;
        }
        return parent::isAllowed() && $this->purchaseOrderActionAuth->isAllowed('PlaceOrder', $purchaseOrder);
    }
}
