<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Controller\AbstractController;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Customer\Authorization;

/**
 * Controller class for purchase order details view.
 */
class View extends AbstractController implements HttpGetActionInterface
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * View constructor.
     * @param ActionContext $context
     * @param CompanyContext $companyContext
     * @param Authorization $purchaseOrderActionAuth
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param PurchaseOrderConfig $purchaseOrderConfig
     */
    public function __construct(
        ActionContext $context,
        CompanyContext $companyContext,
        Authorization $purchaseOrderActionAuth,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        PurchaseOrderConfig $purchaseOrderConfig
    ) {
        parent::__construct($context, $companyContext, $purchaseOrderActionAuth);
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->purchaseOrderConfig = $purchaseOrderConfig;
    }

    /**
     * View the details of a purchase order.
     *
     * @return AbstractResult
     */
    public function execute()
    {
        $requestId = $this->_request->getParam('request_id');
        $purchaseOrder = $this->purchaseOrderRepository->getById($requestId);

        if (!$purchaseOrder->getEntityId()) {
            $this->messageManager->addErrorMessage(__('Requested purchase order was not found'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');

        if ($navigationBlock) {
            $navigationBlock->setActive('purchaseorder/purchaseorder');
        }

        return $resultPage;
    }

    /**
     * Check if this action is allowed.
     *
     * Verify that the user belongs to a company with purchase orders enabled.
     * Verify that the user can view the purchase order from the request.
     *
     * @return bool
     * @throws LocalizedException
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
            && $this->purchaseOrderActionAuth->isAllowed('View', $purchaseOrder);
    }
}
