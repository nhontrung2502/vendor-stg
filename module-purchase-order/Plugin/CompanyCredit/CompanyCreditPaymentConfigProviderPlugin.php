<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Plugin\CompanyCredit;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Credit Limit Config provider plugin to modify data related to purchase order quote.
 */
class CompanyCreditPaymentConfigProviderPlugin
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
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * CompanyCreditPaymentConfigProviderPlugin constructor.
     *
     * @param Context $context
     * @param CartRepositoryInterface $quoteRepository
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $quoteRepository,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {
        $this->context = $context;
        $this->quoteRepository = $quoteRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Use purchase order quote for company credit payment method if available
     *
     * @param CompanyCreditPaymentConfigProvider $subject
     * @param \Closure $proceed
     * @return CartInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetQuote(CompanyCreditPaymentConfigProvider $subject, \Closure $proceed)
    {
        $purchaseOrderId = $this->context->getRequest()->getParam('purchaseOrderId');
        if ($purchaseOrderId) {
            try {
                $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
                $quoteId = $purchaseOrder->getQuoteId();
                return $this->quoteRepository->get($quoteId);
            } catch (\Exception $e) {
                return $proceed();
            }
        }
        return $proceed();
    }
}
