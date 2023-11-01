<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Checkout\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Company\Model\CompanyUserPermission;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Controller\Index\Index as IndexController;
use Magento\Framework\Controller\ResultInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Plugin class for managing access to purchase order payment page.
 */
class IndexPlugin
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CompanyUserPermission
     */
    private $companyUserPermission;

    /**
     * @var UserContextInterface
     */
    private $customerContext;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * IndexPlugin constructor.
     *
     * @param Context $context
     * @param CartRepositoryInterface $quoteRepository
     * @param CompanyUserPermission $companyUserPermission
     * @param UserContextInterface $customerContext
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $quoteRepository,
        CompanyUserPermission $companyUserPermission,
        UserContextInterface $customerContext,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {
        $this->context = $context;
        $this->quoteRepository = $quoteRepository;
        $this->companyUserPermission = $companyUserPermission;
        $this->customerContext = $customerContext;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Plugin for checking restriction for purchase order quote.
     *
     * @param IndexController $subject
     * @param \Closure $proceed
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        IndexController $subject,
        \Closure $proceed
    ) {
        $purchaseOrderId = (int) $this->context->getRequest()->getParam('purchaseOrderId');
        if ($purchaseOrderId) {
            $resultRedirect = $this->context->getResultRedirectFactory()->create();
            try {
                $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
                $userType = $this->customerContext->getUserType();
                $userId = $this->customerContext->getUserId();
                $quote = $subject->getOnepage()->getQuote();
                if (!$this->validate($purchaseOrderId)) {
                    $resultRedirect->setPath('noroute');
                    return $resultRedirect;
                }
                if (empty($userId)
                    || $userType != UserContextInterface::USER_TYPE_CUSTOMER
                ) {
                    $resultRedirect->setPath('customer/account/login');
                    return $resultRedirect;
                } elseif (!$this->companyUserPermission->isCurrentUserCompanyUser() ||
                    ((int) $purchaseOrder->getCreatorId()) !== $userId) {
                    $resultRedirect->setPath('company/accessdenied');
                    return $resultRedirect;
                } elseif (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
                    return $resultRedirect->setPath('purchaseorder/purchaseorder/view', [
                        'request_id' => $purchaseOrderId
                    ]);
                }
            } catch (\Exception $e) {
                $resultRedirect->setPath('noroute');
            }
        }

        return $proceed();
    }

    /**
     * Validate purchase order
     *
     * @param int $purchaseOrderId
     * @return bool
     * @throws NoSuchEntityException
     */
    private function validate($purchaseOrderId)
    {
        $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
        return $purchaseOrder->getStatus() === PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT;
    }
}
