<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Items;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;

/**
 * Block class for purchase order item messages.
 *
 * @api
 * @since 100.2.0
 */
class Messages extends AbstractPurchaseOrder
{
    /**
     * @var array
     */
    private $itemMessages;

    /**
     * Get the messages for the item.
     *
     * @return array
     * @since 100.2.0
     */
    public function getItemMessages()
    {
        return  $this->itemMessages;
    }

    /**
     * Set the messages for the item.
     *
     * @param array $itemMessages
     * @since 100.2.0
     */
    public function setItemMessages(array $itemMessages)
    {
        $this->itemMessages = $itemMessages;
    }

    /**
     * Determines if item messages should be displayed for the current purchase order.
     *
     * This is based on whether the purchase order has already been converted to a sales order.
     *
     * @return bool
     * @since 100.2.0
     */
    public function shouldShowMessages()
    {
        try {
            $purchaseOrder = $this->getPurchaseOrder();
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return !($purchaseOrder->getStatus() === PurchaseOrderInterface::STATUS_ORDER_PLACED);
    }
}
