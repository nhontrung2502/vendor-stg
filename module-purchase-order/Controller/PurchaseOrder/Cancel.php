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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\PurchaseOrderManagement;

/**
 * Cancel purchase order controller.
 */
class Cancel extends AbstractController implements HttpPostActionInterface
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
     * Cancel constructor.
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param PurchaseOrderManagement $purchaseOrderManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CommentManagement $commentManagement
     */
    public function __construct(
        Context $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        PurchaseOrderManagement $purchaseOrderManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CommentManagement $commentManagement
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
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
            $this->purchaseOrderManagement->cancelPurchaseOrder(
                $this->getPurchaseOrder(),
                $this->companyContext->getCustomerId()
            );

            // If there is a comment present in the request include it as part of the operation
            if ($this->getRequest()->getParam('comment')) {
                $this->commentManagement->addComment(
                    $this->getPurchaseOrder()->getEntityId(),
                    $this->companyContext->getCustomerId(),
                    $this->getRequest()->getParam('comment')
                );
            }

            $this->messageManager->addSuccessMessage(__('Purchase order is successfully canceled.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Purchase order cannot be canceled'));
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
     * @inheritDoc
     */
    protected function isAllowed()
    {
        try {
            return $this->purchaseOrderActionAuth->isAllowed('cancel', $this->getPurchaseOrder());
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
}
