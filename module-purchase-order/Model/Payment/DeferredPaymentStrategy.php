<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Payment;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Payment\Helper\Data as PaymentData;

/**
 * Validate payment method
 */
class DeferredPaymentStrategy implements DeferredPaymentStrategyInterface
{
    /**
     * @var PaymentData
     */
    private $paymentData;

    /**
     * @var array
     */
    private $overrides;

    /**
     * DeferredPaymentStrategy constructor.
     *
     * @param PaymentData $paymentData
     * @param array $overrides
     */
    public function __construct(
        PaymentData $paymentData,
        array $overrides = []
    ) {
        $this->paymentData = $paymentData;
        $this->overrides = $overrides;
    }

    /**
     * @inheritdoc
     */
    public function isDeferredPayment(PurchaseOrderInterface $purchaseOrder): bool
    {
        $paymentMethod = $purchaseOrder->getPaymentMethod();
        return $this->isDeferrablePaymentMethod($paymentMethod);
    }

    /**
     * @inheritdoc
     */
    public function isDeferrablePaymentMethod(string $code): bool
    {
        if ($code === '') {
            throw new \InvalidArgumentException(__("Payment method code cannot be empty"));
        }
        if (in_array($code, $this->overrides['deferred'])) {
            return true;
        }
        if (in_array($code, $this->overrides['undeferred'])) {
            return false;
        }
        $paymentMethod = $this->paymentData->getMethodInstance($code);
        return !$paymentMethod->isOffline();
    }
}
