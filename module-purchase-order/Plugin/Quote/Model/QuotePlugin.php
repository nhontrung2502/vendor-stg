<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Quote\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;

/**
 * Plugin Class to prevent collect totals calls for Purchase Order Quote during checkout page
 */
class QuotePlugin
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * QuotePlugin constructor.
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Skip collect totals for purchase order
     *
     * @param Quote $subject
     * @param \Closure $proceed
     * @return Quote
     */
    public function aroundCollectTotals(Quote $subject, \Closure $proceed)
    {
        if ($this->isPurchaseOrderQuote($subject)) {
            return $subject;
        }
        return $proceed();
    }

    /**
     * Check if quote is using for Purchase Order
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function isPurchaseOrderQuote(CartInterface $quote)
    {
        $purchaseOrder = $this->purchaseOrderRepository->getByQuoteId($quote->getId());
        return ($purchaseOrder->getEntityId()) ? true : false;
    }
}
