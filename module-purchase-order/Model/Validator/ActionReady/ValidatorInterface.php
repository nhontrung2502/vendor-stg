<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Validator\ActionReady;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Purchase order action ready validator interface.
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Validate action on purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     */
    public function validate(PurchaseOrderInterface $purchaseOrder) : bool;
}
