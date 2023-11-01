<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Quote\Model;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;

/**
 * Plugin Class to prevent collect totals calls for Purchase Order Quote during place order process
 */
class QuoteRepositoryPlugin
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * QuoteRepositoryPlugin constructor.
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     *
     */
    public function __construct(PurchaseOrderRepositoryInterface $purchaseOrderRepository)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * There is no need to be active for purchase order quote.
     * Set total collected flag to true to prevent collect totals calls if
     * quote is used for Purchase Order and trigger_recollect is false
     *
     * @param CartRepositoryInterface $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param array $sharedStoreIds [optional]
     * @return CartInterface
     */
    public function aroundGetActive(
        CartRepositoryInterface $subject,
        \Closure $proceed,
        $cartId,
        array $sharedStoreIds = []
    ) {
        $quote = $subject->get($cartId);

        if ($quote !== null && $this->isPurchaseOrderQuote($quote)) {
            if (!$quote->getData('trigger_recollect')) {
                $quote->setTotalsCollectedFlag(true);
            }
            $result = $quote;
        } else {
            $result = $proceed($cartId, $sharedStoreIds);
        }

        return $result;
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
