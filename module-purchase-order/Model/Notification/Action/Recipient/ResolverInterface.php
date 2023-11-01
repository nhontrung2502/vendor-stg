<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Action\Recipient;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Resolves user ids to be notified for purchase order.
 *
 * @api
 */
interface ResolverInterface
{
    /**
     * Get customer user ids to notify on purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return int[]
     */
    public function getRecipients(PurchaseOrderInterface $purchaseOrder) : array;
}
