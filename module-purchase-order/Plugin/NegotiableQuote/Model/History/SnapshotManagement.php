<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\NegotiableQuote\Model\History;

use Magento\NegotiableQuote\Model\History\SnapshotManagement as NegotiableQuoteSnapshotManagement;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Plugin Class to override customer id to System (0) for log updates pertaining to Purchase Order end state actions
 */
class SnapshotManagement
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var HistoryManagementInterface
     */
    private $historyManagement;

    /**
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param HistoryManagementInterface $historyManagement
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        HistoryManagementInterface $historyManagement
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->historyManagement = $historyManagement;
    }

    /**
     * Change customer id to 0 ("System") if Purchase Order created from a Negotiable Quote is closed or ordered
     *
     * @param NegotiableQuoteSnapshotManagement $subject
     * @param int $result
     * @param CartInterface $quote
     * @param bool $isSeller
     * @param bool $isExpired
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCustomerId(
        NegotiableQuoteSnapshotManagement $subject,
        $result,
        CartInterface $quote,
        $isSeller,
        $isExpired
    ) {
        $associatedPurchaseOrder = $this->purchaseOrderRepository->getByQuoteId($quote->getId());

        $isAssociatedPurchaseOrderInFinalState = in_array(
            $associatedPurchaseOrder->getStatus(),
            [
                PurchaseOrderInterface::STATUS_CANCELED,
                PurchaseOrderInterface::STATUS_REJECTED,
                PurchaseOrderInterface::STATUS_ORDER_PLACED
            ]
        );

        // if purchase order is in final state, all negotiable quote status updates are handled by system
        if ($isAssociatedPurchaseOrderInFinalState) {
            $result = 0;
        }

        return $result;
    }
}
