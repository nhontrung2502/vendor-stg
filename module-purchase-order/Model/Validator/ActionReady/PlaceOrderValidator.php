<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Validator\ActionReady;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Validates purchase order is ready for placing order
 */
class PlaceOrderValidator implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(PurchaseOrderInterface $purchaseOrder): bool
    {
        $status = $purchaseOrder->getStatus();
        $placeOrderStatuses = [
            PurchaseOrderInterface::STATUS_APPROVED,
            PurchaseOrderInterface::STATUS_ORDER_FAILED
        ];
        if (!in_array($status, $placeOrderStatuses)
            || (null != $purchaseOrder->getOrderId())
            || !$purchaseOrder->getQuoteId()
        ) {
            return false;
        }
        return true;
    }
}
