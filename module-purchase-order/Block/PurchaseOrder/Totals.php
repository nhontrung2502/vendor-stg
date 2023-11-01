<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\PriceFormatter;
use Magento\PurchaseOrder\Model\Quote\Totals as QuoteTotals;
use Magento\PurchaseOrder\Model\Quote\TotalsFactory as QuoteTotalsFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Block class for the various price totals for a purchase order.
 *
 * @api
 * @since 100.2.0
 */
class Totals extends AbstractPurchaseOrder
{
    /**#@+
     * Total Codes
     */
    const TOTAL_CATALOG_PRICE = 'catalog_price';
    const TOTAL_PROPOSED_SHIPPING = 'proposed_shipping';
    const TOTAL_QUOTE_TAX = 'quote_tax';
    const TOTAL_GRAND_TOTAL = 'grand_total';
    const TOTAL_BASE_GRAND_TOTAL = 'base_grand_total';
    const TOTAL_GIFT_CARD = 'gift_card';
    const TOTAL_DISCOUNT = 'discount';
    const TOTAL_CUSTOMER_BALANCE = 'customerbalance';
    const TOTAL_REWARD_POINTS = 'reward';
    /**#@-*/

    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    /**
     * @var QuoteTotalsFactory
     */
    private $quoteTotalsFactory;

    /**
     * @var QuoteTotals
     */
    private $quoteTotals;

    /**
     * @var TaxConfig
     */
    private $taxConfig;

    /**
     * @var bool
     */
    private $inQuoteCurrency = true;

    /**
     * @var DataObject[]
     */
    private $totals = [];

    /**
     * Totals constructor.
     *
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param TaxConfig $taxConfig
     * @param QuoteTotalsFactory $quoteTotalsFactory
     * @param PriceFormatter $priceFormatter
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        TaxConfig $taxConfig,
        QuoteTotalsFactory $quoteTotalsFactory,
        PriceFormatter $priceFormatter,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->taxConfig = $taxConfig;
        $this->quoteTotalsFactory = $quoteTotalsFactory;
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * Format a price for display.
     *
     * @param float $price
     * @param string $code
     * @return string
     * @since 100.2.0
     */
    public function formatPrice($price, $code)
    {
        return $this->priceFormatter->formatPrice($price, $code);
    }

    /**
     * Get the total price information for the current purchase order.
     *
     * @return array
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getTotals()
    {
        if (count($this->totals) == 0) {
            $this->initTotals();
        }

        return $this->totals;
    }

    /**
     * Initialize the total price information for the current purchase order.
     *
     * @return Totals
     * @throws NoSuchEntityException
     */
    private function initTotals()
    {
        $this->initSubtotal()
            ->initShipping()
            ->initTax()
            ->initGiftCard()
            ->initOtherTotal()
            ->initGrandTotal()
            ->initBaseGrandTotal();

        return $this;
    }

    /**
     * Initialize the subtotal information for the current purchase order.
     *
     * @return Totals
     * @throws NoSuchEntityException
     */
    private function initSubtotal()
    {
        $displayIncludeTax = $this->_scopeConfig->getValue(
            TaxConfig::XML_PATH_DISPLAY_SALES_SUBTOTAL,
            ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()
        );
        $subtotalClass = $displayIncludeTax == TaxConfig::DISPLAY_TYPE_INCLUDING_TAX ? 'hidden' : '';
        $subtotalTaxClass = $displayIncludeTax == TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX ? 'hidden' : '';
        $subtotals = [
            'subtotal' => [
                'value' => $this->getQuoteTotals()->getCatalogTotalPriceWithoutTax($this->inQuoteCurrency),
                'label' => __('Subtotal (Excl. Tax)'),
                'class' => $subtotalClass
            ],
            'subtotalTax' => [
                'value' => $this->getQuoteTotals()->getCatalogTotalPriceWithTax($this->inQuoteCurrency),
                'label' => __('Subtotal (Incl. Tax)'),
                'class' => $subtotalTaxClass
            ],
            'discount' => [
                'value' => -$this->getQuoteTotals()->getCartTotalDiscount($this->inQuoteCurrency),
                'label' => __('Discount'),
                'class' => $this->getQuoteTotals()->getCartTotalDiscount() ? '' : 'hidden'
            ],

        ];

        $this->totals[self::TOTAL_CATALOG_PRICE] = new DataObject(
            [
                'code' => self::TOTAL_CATALOG_PRICE,
                'subtotals' => $subtotals,
                'block_name' => 'purchase.order.totals.original',
                'value' => $this->getQuoteTotals()->getCatalogTotalPrice($this->inQuoteCurrency),
                'label' => $this->getCatalogTotalPriceLabel(),
                'base_currency' => $this->getQuote()->getCurrency()->getQuoteCurrencyCode(),
                'rate' => $this->getQuote()->getCurrency()->getBaseToQuoteRate(),
            ]
        );

        return $this;
    }

    /**
     * Initialize the gift card information for the current purchase order.
     *
     * @return Totals
     */
    private function initGiftCard()
    {
        $this->totals[self::TOTAL_GIFT_CARD] = new DataObject(
            [
                'code' => self::TOTAL_GIFT_CARD,
                'block_name' => 'purchase.order.totals.giftcards',
            ]
        );

        return $this;
    }

    /**
     * Initialize the tax information for the current purchase order.
     *
     * @return Totals
     */
    private function initTax()
    {
        $this->totals[self::TOTAL_QUOTE_TAX] = new DataObject(
            [
                'code' => self::TOTAL_QUOTE_TAX,
                'value' => $this->getQuoteTotals()->getTaxValue($this->inQuoteCurrency),
                'label' => __('Estimated Tax')
            ]
        );

        return $this;
    }

    /**
     * Initialize the 'other' total information for the current purchase order.
     *
     * @return Totals
     */
    private function initOtherTotal()
    {
        if ($this->getQuote()->isVirtual()) {
            $totals = $this->getQuote()->getBillingAddress()->getTotals();
        } else {
            $totals = $this->getQuote()->getShippingAddress()->getTotals();
        }

        $removeKeys = [
            'subtotal',
            'savingprice',
            'percentage',
            'percentage',
            'shipping',
            'tax',
            'grand_total',
            'giftwrapping',
            'giftcardaccount'
        ];
        foreach ($totals as $_key => $value) {
            if (in_array($_key, $removeKeys)) {
                unset($totals[$_key]);
            }
        }
        if (is_array($totals)) {
            foreach ($totals as $key => $total) {
                $value = $total->getValue();
                if ($value) {
                    $this->totals[$key] = new DataObject(
                        [
                            'code' => $total->getCode(),
                            'value' => $total->getValue(),
                            'label' => $total->getTitle()
                        ]
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Initialize the grand total information for the current purchase order.
     *
     * @return Totals
     * @throws NoSuchEntityException
     */
    private function initGrandTotal()
    {
        $this->totals[self::TOTAL_GRAND_TOTAL] = new DataObject(
            [
                'code' => self::TOTAL_GRAND_TOTAL,
                'field' => 'grand_total',
                'strong' => true,
                'value' => $this->getQuoteTotals()->getGrandTotal($this->inQuoteCurrency),
                'label' => __('Grand Total (Incl. Tax)'),
            ]
        );

        return $this;
    }

    /**
     * Initialize the base grand total information for the current purchase order.
     *
     * @return Totals
     * @throws NoSuchEntityException
     */
    private function initBaseGrandTotal()
    {
        $quoteCurrency = $this->getQuote()->getCurrency();

        if ($quoteCurrency->getBaseCurrencyCode() != $quoteCurrency->getQuoteCurrencyCode()) {
            $this->totals[self::TOTAL_BASE_GRAND_TOTAL] = new \Magento\Framework\DataObject(
                [
                    'code' => self::TOTAL_BASE_GRAND_TOTAL,
                    'field' => 'base_grand_total',
                    'style' => 'no-border',
                    'currency' => $this->getQuote()->getCurrency()->getBaseCurrencyCode(),
                    'strong' => true,
                    'value' => $this->getQuoteTotals()->getGrandTotal(),
                    'label' => __('Grand Total to Be Charged'),
                ]
            );
        }

        return $this;
    }

    /**
     * Initialize the shipping total information for the current purchase order.
     *
     * @return Totals
     * @throws NoSuchEntityException
     */
    private function initShipping()
    {
        $quoteShippingAddress = $this->getQuote()->getShippingAddress();

        if ($quoteShippingAddress !== null && $quoteShippingAddress->getShippingMethod()) {
            $proposedPrice = $this->getQuoteTotals()->getQuoteShippingPrice($this->inQuoteCurrency);
            $this->totals[self::TOTAL_PROPOSED_SHIPPING] = new \Magento\Framework\DataObject(
                [
                    'code' => self::TOTAL_PROPOSED_SHIPPING,
                    'value' => $proposedPrice,
                    'label' => __('Shipping & Handling')
                ]
            );
        }

        return $this;
    }

    /**
     * Get the label for the total price based on store configuration.
     *
     * @return Phrase
     * @throws NoSuchEntityException
     */
    private function getCatalogTotalPriceLabel()
    {
        $taxIncludedInPrice = $this->taxConfig->displaySalesTaxWithGrandTotal($this->_storeManager->getStore());

        return $taxIncludedInPrice ? __('Catalog Total Price (Incl. Tax)') : __('Catalog Total Price (Excl. Tax)');
    }

    /**
     * Get the quote totals object.
     *
     * @return QuoteTotals
     * @throws NoSuchEntityException
     */
    private function getQuoteTotals()
    {
        if (!$this->quoteTotals) {
            $this->quoteTotals = $this->quoteTotalsFactory->create();
            $this->quoteTotals->setQuote($this->getPurchaseOrder()->getSnapshotQuote());
        }

        return $this->quoteTotals;
    }
}
