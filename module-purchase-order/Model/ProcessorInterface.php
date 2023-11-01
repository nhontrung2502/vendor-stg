<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Processor for purchase order processing.
 *
 * @api
 */
interface ProcessorInterface
{
    /**
     * Create purchase order from quote.
     *
     * @param CartInterface $quote
     * @param PaymentInterface $paymentMethod
     * @return PurchaseOrderInterface
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createPurchaseOrder(
        CartInterface $quote,
        PaymentInterface $paymentMethod
    ) : PurchaseOrderInterface;
}
