<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Process custom customer attributes before saving shipping address
 */
class ProcessCustomCustomerAttributes
{
    /**
     * Process shipping custom attribute before save
     *
     * @param ShippingInformationManagementInterface $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagementInterface $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): void {
        $shippingAddress = $addressInformation->getShippingAddress();
        if ($shippingAddress) {
            $this->processCustomCustomerAttributes($shippingAddress);
        }

        $billingAddress = $addressInformation->getBillingAddress();
        if ($billingAddress) {
            $this->processCustomCustomerAttributes($billingAddress);
        }
    }

    /**
     * Process customer custom attribute before save shipping or billing address
     *
     * @param AddressInterface $addressInformation
     * @return void
     */
    public function processCustomCustomerAttributes(
        AddressInterface $addressInformation
    ): void {
        $customerCustomAttributes = $addressInformation->getCustomAttributes();
        if ($customerCustomAttributes) {
            foreach ($customerCustomAttributes as $customAttribute) {
                $customAttributeValue = $customAttribute->getValue();
                if ($customAttributeValue && is_array($customAttributeValue)) {
                    if ($customAttributeValue['value'] !== null) {
                        $customAttribute->setValue($customAttributeValue['value']);
                    }
                }
            }
        }
    }
}
