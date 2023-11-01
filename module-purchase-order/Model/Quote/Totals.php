<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Class handles Totals information.
 */
class Totals
{
    /**
     * @var TaxConfig
     */
    private $taxConfig;

    /**
     * @var float|null
     */
    private $baseToQuoteCurrencyRate;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var QuoteItem
     */
    private $items;

    /**
     * @var CartInterface
     */
    private $quote;

    /**
     * @param TaxConfig $taxConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TaxConfig $taxConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->taxConfig = $taxConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Get catalog total price without tax.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return int|float
     */
    public function getCatalogTotalPriceWithoutTax($useQuoteCurrency = false)
    {
        $totalPrice = 0;

        foreach ($this->getQuoteVisibleItems() as $item) {
            $totalPrice += $this->retrieveQuoteData($item, $item->getPrice(), $useQuoteCurrency);
        }
        return $totalPrice;
    }

    /**
     * Get catalog total price with tax.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return int|float
     */
    public function getCatalogTotalPriceWithTax($useQuoteCurrency = false)
    {
        return $this->getCatalogTotalPriceWithoutTax($useQuoteCurrency)
            + $this->getOriginalTaxValue($useQuoteCurrency);
    }

    /**
     * Get catalog total price.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return int|float
     */
    public function getCatalogTotalPrice($useQuoteCurrency = false)
    {
        $price = $this->isTaxDisplayedWithGrandTotal() ?
            $this->getCatalogTotalPriceWithTax($useQuoteCurrency) :
            $this->getCatalogTotalPriceWithoutTax($useQuoteCurrency);
        return $price - $this->getCartTotalDiscount();
    }

    /**
     * Get cart total discount.
     *
     * @return int|float
     */
    public function getCartTotalDiscount()
    {
        $totalDiscount = 0;

        foreach ($this->getQuoteVisibleItems() as $item) {
            $totalDiscount += $item->getDiscountAmount();
        }

        return $totalDiscount;
    }

    /**
     * Get cart total tax.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return int|float
     */
    public function getOriginalTaxValue($useQuoteCurrency = false)
    {
        $totalTax = 0;

        foreach ($this->getQuoteVisibleItems() as $item) {
            $totalTax += $this->retrieveQuoteData($item, $item->getOriginalTaxAmount(), $useQuoteCurrency);
        }

        return $totalTax;
    }

    /**
     * Get subtotal value.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float|int
     */
    public function getSubtotal($useQuoteCurrency = false)
    {
        $subtotal = $this->getOriginalSubtotal($useQuoteCurrency);
        if ($this->isTaxDisplayedWithGrandTotal()) {
            $subtotal += $this->getSubtotalTaxValue($useQuoteCurrency);
        }

        return $subtotal;
    }

    /**
     * Get subtotal value without tax included.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float|int
     */
    public function getSubtotalWithoutTax(bool $useQuoteCurrency = false)
    {
        return $this->getOriginalSubtotal($useQuoteCurrency);
    }

    /**
     * Get subtotal value with tax included.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float|int
     */
    public function getSubtotalWithTax(bool $useQuoteCurrency = false)
    {
        return $this->getOriginalSubtotal($useQuoteCurrency) + $this->getSubtotalTaxValue($useQuoteCurrency);
    }

    /**
     * Get original subtotal value.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float|int
     */
    private function getOriginalSubtotal($useQuoteCurrency = false)
    {
        $subtotal = 0;
        if ($this->getQuote()) {
            $subtotal = $useQuoteCurrency
                ? $this->getQuote()->getSubtotalWithDiscount()
                : $this->getQuote()->getBaseSubtotalWithDiscount();
        }

        return $subtotal;
    }

    /**
     * Get grand total.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float|int
     */
    public function getGrandTotal($useQuoteCurrency = false)
    {
        $grandTotal = 0;
        if ($this->getQuote()) {
            $grandTotal = $useQuoteCurrency
                ? $this->getQuote()->getGrandTotal()
                : $this->getQuote()->getBaseGrandTotal();
        }

        return $grandTotal;
    }

    /**
     * Retrieve quote total price.
     *
     * @return float|int
     */
    public function getQuoteTotalPrice()
    {
        $totalPrice = $this->getQuote() !== null ? $this->getQuote()->getBaseSubtotalWithDiscount() : 0;

        if ($this->isTaxDisplayedWithGrandTotal()) {
            $totalPrice += $this->getSubtotalTaxValue();
        }
        return $totalPrice;
    }

    /**
     * Get quote shipping price.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float|int
     */
    public function getQuoteShippingPrice($useQuoteCurrency = false)
    {
        return $this->getAddressShippingAmount($useQuoteCurrency);
    }

    /**
     * Get shipping amount from address.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return int
     */
    private function getAddressShippingAmount($useQuoteCurrency = false)
    {
        $shippingAmount = 0;
        if ($this->getQuote() !== null) {
            $address = $this->getQuote()->getShippingAddress();
            $shippingAmount = $useQuoteCurrency ? $address->getShippingAmount() : $address->getBaseShippingAmount();
        }
        return $shippingAmount;
    }

    /**
     * Get total cost.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float
     */
    public function getTotalCost($useQuoteCurrency = false)
    {
        $source = $this->getQuote();
        $totalCost = 0;
        foreach ($source->getAllVisibleItems() as $item) {
            $totalCost += $this->getItemCost($item, $useQuoteCurrency) * $item->getQty();
        }
        return $totalCost;
    }

    /**
     * Get item cost.
     *
     * @param CartItemInterface $item
     * @param bool $useQuoteCurrency [optional]
     * @return float
     */
    public function getItemCost(CartItemInterface $item, $useQuoteCurrency = false)
    {
        $totalCost = 0;
        $children = $item->getChildren();
        if (count($children)) {
            foreach ($children as $child) {
                $cost = floatval($child->getProduct()->getCost());
                if ($useQuoteCurrency) {
                    $cost = round($cost * $this->getBaseToQuoteRate(), 2);
                }
                $totalCost += $cost * $child->getQty();
            }
            return $totalCost;
        }
        $cost = floatval($item->getProduct()->getCost());
        if ($useQuoteCurrency) {
            $cost = round($cost * $this->getBaseToQuoteRate(), 2);
        }
        return $cost;
    }

    /**
     * Retrieve item total price.
     *
     * @param CartItemInterface $item
     * @param float $price
     * @param bool $useQuoteCurrency
     * @return float|int
     */
    private function retrieveQuoteData(CartItemInterface $item, $price, $useQuoteCurrency = false)
    {
        if ($useQuoteCurrency) {
            $price = round($price * $this->getBaseToQuoteRate(), 2);
        }

        return $price * $item->getQty();
    }

    /**
     * Set quote
     *
     * @param CartInterface $quote
     */
    public function setQuote(CartInterface $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Get quote.
     *
     * @return CartInterface
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * Is tax included to grand total value.
     *
     * @return bool
     */
    public function isTaxDisplayedWithGrandTotal()
    {
        return $this->taxConfig->displaySalesTaxWithGrandTotal($this->storeManager->getStore());
    }

    /**
     * Is tax included to subtotal value.
     *
     * @return bool
     */
    public function isTaxDisplayedWithSubtotal()
    {
        return $this->taxConfig->displaySalesSubtotalInclTax($this->storeManager->getStore())
            || $this->taxConfig->displaySalesSubtotalBoth($this->storeManager->getStore());
    }

    /**
     * Get tax value.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float
     */
    public function getSubtotalTaxValue($useQuoteCurrency = false)
    {
        return $this->getTaxValue($useQuoteCurrency) - $this->getShippingTaxValue($useQuoteCurrency);
    }

    /**
     * Get base tax value.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float
     */
    public function getTaxValue($useQuoteCurrency = false)
    {
        /** @var \Magento\Quote\Model\Quote\Address $address */
        $address = $this->getQuote()->getBillingAddress();
        if (!$this->getQuote()->isVirtual()) {
            $address = $this->getQuote()->getShippingAddress();
        }
        return $useQuoteCurrency ? $address->getTaxAmount() : $address->getBaseTaxAmount();
    }

    /**
     * Get shipping tax value.
     *
     * @param bool $useQuoteCurrency [optional]
     * @return float
     */
    public function getShippingTaxValue($useQuoteCurrency = false)
    {
        $shippingTaxValue = 0;
        if (!$this->getQuote()->isVirtual()) {
            $address = $this->getQuote()->getShippingAddress();
            $shippingTaxValue = $useQuoteCurrency
                ? $address->getShippingTaxAmount()
                : $address->getBaseShippingTaxAmount();
        }
        return $shippingTaxValue;
    }

    /**
     * Retrieve tax amount for quote.
     *
     * @return float
     */
    public function getTaxValueForAddInTotal()
    {
        $tax = 0;
        if (!$this->isTaxDisplayedWithGrandTotal()) {
            $tax += $this->getSubtotalTaxValue();
        }
        if (!$this->taxConfig->shippingPriceIncludesTax($this->storeManager->getStore())) {
            $tax += $this->getShippingTaxValue();
        }
        return $tax;
    }

    /**
     * Get conversion rate from base currency to quote currency.
     *
     * @return float|null
     */
    public function getBaseToQuoteRate()
    {
        if ($this->baseToQuoteCurrencyRate === null) {
            $this->baseToQuoteCurrencyRate = $this->getQuote()->getCurrency()->getBaseToQuoteRate();
        }

        return $this->baseToQuoteCurrencyRate;
    }

    /**
     * Get quote visible items.
     *
     * @param bool $useCache [optional]
     * @return array
     */
    private function getQuoteVisibleItems($useCache = true)
    {
        if (!$this->items || !$useCache) {
            $this->items = $this->getQuote()->getAllVisibleItems();
        }
        return $this->items;
    }
}
