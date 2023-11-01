<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Validate payment method interface
 *
 * @api
 */
interface DeferredPaymentStrategyInterface
{
    /**
     * Identifies whether the payment has been deferred for the purchase order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isDeferredPayment(PurchaseOrderInterface $purchaseOrder): bool;

    /**
     * Identifies whether the payment method is deferrable
     *
     * @param string $code
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isDeferrablePaymentMethod(string $code): bool;
}
