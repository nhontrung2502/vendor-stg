<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class PurchaseOrderManagementInterface
 *
 * @api
 */
interface PurchaseOrderManagementInterface
{
    /**
     * Approve purchase order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int|null $actorId
     * @return void
     * @throws PurchaseOrderValidationException
     * @throws LocalizedException
     */
    public function approvePurchaseOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : void;

    /**
     * Create sales order from purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int|null $actorId
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function createSalesOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : OrderInterface;

    /**
     * Cancel purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int|null $actorId
     * @return void
     * @throws LocalizedException
     */
    public function cancelPurchaseOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : void;

    /**
     * Reject purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int|null $actorId
     * @return void
     * @throws LocalizedException
     */
    public function rejectPurchaseOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : void;

    /**
     * Mark purchase order as approval required.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return void
     * @throws LocalizedException
     */
    public function setApprovalRequired(PurchaseOrderInterface $purchaseOrder) : void;
}
