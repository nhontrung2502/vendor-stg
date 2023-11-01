<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Items;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Catalog\Helper\Product\Configuration as ProductConfigurationHelper;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\NegotiableQuote\Helper\Quote as QuoteHelper;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Model\Config;
use Magento\NegotiableQuote\Helper\Quote;

/**
 * Adminhtml sales order create items grid block.
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.0
 */
class Grid extends Widget
{
    /**
     * @var SalesGrid
     */
    private $salesGridBlock;

    /**
     * @var RestrictionInterface
     */
    private $restriction;

    /**
     * @var Quote
     */
    private $negotiableQuoteHelper;

    /**
     * @var Config
     */
    private $taxConfig;

    /**
     * @var ConfigurationPool
     */
    private $productConfigurationPool;

    /**
     * @var TotalsFactory
     */
    private $quoteTotalsFactory;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param Context $context
     * @param SalesGrid $salesGridBlock
     * @param RestrictionInterface $restriction
     * @param Quote $negotiableQuoteHelper
     * @param Config $taxConfig
     * @param ConfigurationPool $productConfigurationPool
     * @param TotalsFactory $quoteTotalsFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        SalesGrid $salesGridBlock,
        RestrictionInterface $restriction,
        Quote $negotiableQuoteHelper,
        Config $taxConfig,
        ConfigurationPool $productConfigurationPool,
        TotalsFactory $quoteTotalsFactory,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $data['quoteHelper'] = ObjectManager::getInstance()->get(QuoteHelper::class);
        $data['productConfigurationHelper'] = ObjectManager::getInstance()->get(ProductConfigurationHelper::class);
        parent::__construct($context, $data);
        $this->salesGridBlock = $salesGridBlock;
        $this->restriction = $restriction;
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
        $this->taxConfig = $taxConfig;
        $this->productConfigurationPool = $productConfigurationPool;
        $this->quoteTotalsFactory = $quoteTotalsFactory;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('quotes_quote_index');
    }

    /**
     * Retrieve current quote.
     *
     * @param bool $snapshot
     * @return CartInterface|null
     */
    public function getQuote($snapshot = false)
    {
        return $this->negotiableQuoteHelper->resolveCurrentQuote($snapshot);
    }

    /**
     * Retrieve product url.
     *
     * @param Item $item
     * @return string
     */
    public function getProductUrlByItem(Item $item)
    {
        $params = [
            'id' => $item->getProduct()->getId()
        ];
        return $this->getUrl('catalog/product/edit', $params);
    }

    /**
     * Get items.
     *
     * @return Item[]
     */
    public function getItems()
    {
        $quote = $this->getQuote(true);
        if ($this->restriction->canSubmit()) {
            $quote->collectTotals();
        }
        $this->salesGridBlock->setQuote($quote);
        $this->salesGridBlock->setNameInLayout($this->getNameInLayout());
        return $this->salesGridBlock->getItems();
    }

    /**
     * Format catalog price.
     *
     * @param Item $item
     * @return float
     */
    public function getFormattedCatalogPrice(Item $item)
    {
        return $this->negotiableQuoteHelper
            ->getFormattedCatalogPrice($item, $this->getQuote()->getCurrency()->getBaseCurrencyCode());
    }

    /**
     * Format original price.
     *
     * @param Item $item
     * @return float
     */
    public function getFormattedOriginalPrice(Item $item)
    {
        return $this->negotiableQuoteHelper
            ->getFormattedOriginalPrice($item, $this->getQuote()->getCurrency()->getBaseCurrencyCode());
    }

    /**
     * Format cart price.
     *
     * @param Item $item
     * @return float
     */
    public function getFormattedCartPrice(Item $item)
    {
        return $this->negotiableQuoteHelper
            ->getFormattedCartPrice($item, $this->getQuote()->getCurrency()->getBaseCurrencyCode());
    }

    /**
     * Display subtotal.
     *
     * @param Item $item
     * @return string
     */
    public function getFormattedSubtotal(Item $item)
    {
        return $this->formatBaseCurrency($item->getBaseRowTotal() - $item->getBaseDiscountAmount());
    }

    /**
     * Display cost.
     *
     * @param Item $item
     * @return string
     */
    public function getFormattedCost(Item $item)
    {
        $totals = $this->quoteTotalsFactory->create(['quote' => $this->getQuote()]);
        $cost = $totals->getItemCost($item);
        return $this->formatBaseCurrency($cost);
    }

    /**
     * Check that edit action is allowed.
     *
     * @return bool
     */
    public function canEdit()
    {
        return $this->restriction->canSubmit();
    }

    /**
     * Check currency update availability.
     *
     * @return bool
     */
    public function canUpdate()
    {
        return $this->restriction->canCurrencyUpdate();
    }

    /**
     * Get subtotal incl. or excl. label.
     *
     * @return string
     */
    public function getSubtotalTaxLabel()
    {
        return $this->isTaxDisplayedWithSubtotal()
            ? __('Subtotal (Incl. Tax)') : __('Subtotal (Excl. Tax)');
    }

    /**
     * Get formatted tax amount for quote item.
     *
     * @param Item $item
     * @return string
     */
    public function getItemTaxAmount(Item $item)
    {
        return $this->formatBaseCurrency($item->getBaseTaxAmount());
    }

    /**
     * Get item subtotal include or exclude tax amount.
     *
     * @param Item $item
     * @return string
     */
    public function getItemSubtotalTaxValue(Item $item)
    {
        $subtotal = $this->isTaxDisplayedWithSubtotal()
            ? $item->getBaseRowTotal() + $item->getBaseTaxAmount()
            : $item->getBaseRowTotal();
        return $this->formatBaseCurrency($subtotal - $item->getBaseDiscountAmount());
    }

    /**
     * Get params for custom options.
     *
     * @return array
     */
    public function getParamsForCustomOptions()
    {
        return [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle">...</a>'
        ];
    }

    /**
     * Is tax included to subtotal value.
     *
     * @return bool
     */
    protected function isTaxDisplayedWithSubtotal()
    {
        return $this->taxConfig->displaySalesSubtotalInclTax($this->_storeManager->getStore())
            || $this->taxConfig->displaySalesSubtotalBoth($this->_storeManager->getStore());
    }

    /**
     * Retrieves item options.
     *
     * @param Item $item
     * @return array
     */
    public function getProductOptions(Item $item)
    {
        $configuration = $this->productConfigurationPool->getByProductType($item->getProductType());
        return $configuration->getOptions($item);
    }

    /**
     * Format price in quote base currency.
     *
     * @param  float $price
     * @return string
     */
    private function formatBaseCurrency($price)
    {
        return $this->priceCurrency->format(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $this->getQuote()->getCurrency()->getBaseCurrencyCode()
        );
    }
}
