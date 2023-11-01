<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Quote\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Model\Quote\Address;

/**
 * Plugin Class to prevent collect totals calls for Purchase Order Quote during checkout page
 */
class AddressPlugin
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
     * Skip collect shipping rates for purchase order
     *
     * @param Address $subject
     * @param \Closure $proceed
     * @return Address
     */
    public function aroundCollectShippingRates(Address $subject, \Closure $proceed)
    {
        if ($subject->getAddressType() === Address::TYPE_SHIPPING
            && $this->isPurchaseOrderQuote($subject->getQuote())
        ) {
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
