<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\PurchaseOrder\Model\PurchaseOrderRepository;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;

/**
 * Apply the Purchase Order Snapshot currency to Quote
 */
class ApplyPurchaseOrderSnapshotCurrency implements ObserverInterface
{
    /**
     * @var PurchaseOrderRepository
     */
    private $purchaseOrderRepository;

    /**
     * @param PurchaseOrderRepository $purchaseOrderRepository
     */
    public function __construct(PurchaseOrderRepository $purchaseOrderRepository)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var CartInterface $quote */
        $quote = $observer->getDataObject();
        $purchaseOrder = $this->getPurchaseOrder($quote);
        if ($purchaseOrder) {
            $quote->setQuoteCurrencyCode($purchaseOrder->getSnapshotQuote()->getQuoteCurrencyCode());
        }
    }

    /**
     * Get Purchase Order
     *
     * @param CartInterface $quote
     * @return bool|PurchaseOrderInterface
     */
    private function getPurchaseOrder(CartInterface $quote)
    {
        $purchaseOrder = $this->purchaseOrderRepository->getByQuoteId($quote->getId());
        return ($purchaseOrder->getEntityId()) ? $purchaseOrder : false;
    }
}
