<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Exception;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Customer\Authorization;

/**
 * Action Controller for adding Comment to a Purchase Order
 */
class AddComment extends AbstractController implements HttpPostActionInterface
{
    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * @param ActionContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param CommentManagement $commentManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param PurchaseOrderConfig $purchaseOrderConfig
     */
    public function __construct(
        ActionContext $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        CommentManagement $commentManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        PurchaseOrderConfig $purchaseOrderConfig
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->commentManagement = $commentManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->purchaseOrderConfig = $purchaseOrderConfig;
    }

    /**
     * Execute function
     *
     * @return Redirect
     */
    public function execute()
    {
        $purchaseOrderId = (int)$this->getRequest()->getParam('request_id');
        $comment = $this->getRequest()->getParam('comment');

        $resultRedirect = $this->resultRedirectFactory->create();

        if (empty($purchaseOrderId)) {
            return $resultRedirect->setPath('*/*/index');
        }

        $resultRedirect->setPath('*/*/view', ['request_id' => $purchaseOrderId]);

        try {
            $this->commentManagement->addComment($purchaseOrderId, $this->companyContext->getCustomerId(), $comment);
            $this->messageManager->addSuccessMessage(__('Comment added successfully.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('We cannot comment the purchase order right now.'));
        }

        return $resultRedirect;
    }

    /**
     * Check if this action is allowed.
     *
     * Verify that the user belongs to a company with purchase orders enabled.
     *
     * @return bool
     */
    protected function isAllowed()
    {
        $purchaseOrderId = $this->_request->getParam('request_id');

        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
        } catch (NoSuchEntityException $exception) {
            return false;
        }

        return $this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite()
            && parent::isAllowed()
            && $this->purchaseOrderActionAuth->isAllowed('view', $purchaseOrder);
    }
}
