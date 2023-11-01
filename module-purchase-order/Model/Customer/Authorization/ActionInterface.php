<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Customer\Authorization;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Purchase order customer action authorization interface
 *
 * @api
 */
interface ActionInterface
{
    /**
     * Check if the current customer is allowed to perform the action on the given purchase order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     */
    public function isAllowed(PurchaseOrderInterface $purchaseOrder) : bool;
}
