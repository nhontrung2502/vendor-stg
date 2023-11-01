<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\ViewModel\PurchaseOrder;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategyInterface;

/**
 * View model for purchase order success page block.
 */
class Success implements ArgumentInterface
{
    /**
     * @var DeferredPaymentStrategyInterface
     */
    private $deferredPaymentStrategy;

    /**
     * Success view model constructor.
     *
     * @param DeferredPaymentStrategyInterface $deferredPaymentStrategy
     */
    public function __construct(
        DeferredPaymentStrategyInterface $deferredPaymentStrategy
    ) {
        $this->deferredPaymentStrategy = $deferredPaymentStrategy;
    }

    /**
     * Get deferred payment strategy object
     *
     * @return DeferredPaymentStrategyInterface
     */
    public function getPaymentStrategy(): DeferredPaymentStrategyInterface
    {
        return $this->deferredPaymentStrategy;
    }
}
