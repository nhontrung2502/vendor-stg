<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Interface PurchaseOrderPaymentInformationManagementInterface
 *
 * @api
 */
interface PurchaseOrderPaymentInformationManagementInterface
{
    /**
     * Set payment information and place purchase order for a specified cart.
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param QuoteAddressInterface|null $billingAddress
     * @return int Purchase Order ID.
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function savePaymentInformationAndPlacePurchaseOrder(
        $cartId,
        PaymentInterface $paymentMethod,
        QuoteAddressInterface $billingAddress = null
    );
}
