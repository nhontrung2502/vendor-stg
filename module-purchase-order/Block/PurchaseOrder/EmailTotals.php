<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Block class for the email specific price totals for a purchase order.
 *
 * @api
 * @since 100.2.0
 */
class EmailTotals extends Totals
{
    /**
     * @var DataObject[]
     */
    private $emailTotals = [];

    /**
     * Get the email specific total price information for the current purchase order.
     *
     * @return array
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getEmailTotals()
    {
        $totals = $this->getTotals();

        $subtotals = $totals[self::TOTAL_CATALOG_PRICE]->getSubtotals();
        $subtotal = new DataObject($subtotals['subtotal']);
        $subtotal->setLabel(__('Subtotal'));
        $this->emailTotals['subtotal'] = $subtotal;

        if (isset($totals[self::TOTAL_PROPOSED_SHIPPING])) {
            $shippingTotal = $totals[self::TOTAL_PROPOSED_SHIPPING];
            $shippingTotal->setLabel(__('Shipping & Handling'));
            $this->emailTotals[self::TOTAL_PROPOSED_SHIPPING] = $shippingTotal;
        }

        if (isset($totals[self::TOTAL_QUOTE_TAX]) && (double) $totals[self::TOTAL_QUOTE_TAX]->getValue()) {
            $taxTotal = $totals[self::TOTAL_QUOTE_TAX];
            $taxTotal->setLabel(__('Tax'));
            $this->emailTotals[self::TOTAL_QUOTE_TAX] = $taxTotal;
        }

        if (isset($totals[self::TOTAL_GIFT_CARD])) {
            $giftCardTotal = $totals[self::TOTAL_GIFT_CARD];
            $this->emailTotals[self::TOTAL_GIFT_CARD] = $giftCardTotal;
            if ($giftCardTotal->getBlockName()) {
                $giftCardTotalBlock = $this->getChildBlock($giftCardTotal->getBlockName());
                $giftCardTotalBlock->setPurchaseOrderById($this->getPurchaseOrder()->getEntityId());
            }
        }

        if (isset($totals[self::TOTAL_DISCOUNT]) && (double) $totals[self::TOTAL_DISCOUNT]->getValue()) {
            $this->emailTotals[self::TOTAL_DISCOUNT] = $totals[self::TOTAL_DISCOUNT];
        }

        if (isset($totals[self::TOTAL_CUSTOMER_BALANCE]) &&
            (double) $totals[self::TOTAL_CUSTOMER_BALANCE]->getValue()
        ) {
            $this->emailTotals[self::TOTAL_CUSTOMER_BALANCE] = $totals[self::TOTAL_CUSTOMER_BALANCE];
        }

        if (isset($totals[self::TOTAL_REWARD_POINTS]) && (double) $totals[self::TOTAL_REWARD_POINTS]->getValue()) {
            $this->emailTotals[self::TOTAL_REWARD_POINTS] = $totals[self::TOTAL_REWARD_POINTS];
        }

        $grandTotal = $totals[self::TOTAL_GRAND_TOTAL];
        $grandTotal->setLabel(__('Grand Total'));
        $this->emailTotals[self::TOTAL_GRAND_TOTAL] = $grandTotal;

        return $this->emailTotals;
    }
}
