<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model;

use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Utility class for formatting various prices.
 */
class PriceFormatter
{
    /**
     * @var Currency[]
     */
    private $currencyArray;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * PriceFormatter constructor.
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Get convert rate for quote item.
     *
     * @param CartItemInterface $item
     * @param string $quoteCurrency
     * @param string $baseCurrency
     * @return int
     */
    private function getConvertRate(CartItemInterface $item, $quoteCurrency, $baseCurrency)
    {
        $quote = $item->getQuote();
        $rate = 1;
        if (isset($quoteCurrency) && isset($baseCurrency)) {
            if ($baseCurrency == $quote->getBaseCurrencyCode() && $quoteCurrency == $quote->getQuoteCurrencyCode()) {
                $rate = $quote->getBaseToQuoteRate();
            } else {
                $currency = $this->priceCurrency->getCurrency(null, $baseCurrency);
                $rate = $currency->getRate($quoteCurrency) ? $currency->getRate($quoteCurrency) : 1;
            }
        }

        return $rate;
    }

    /**
     * Retrieve formatted price.
     *
     * @param float $value
     * @param string $quoteCurrency
     * @return string
     * @throws NoSuchEntityException
     */
    private function formatProductPrice($value, $quoteCurrency)
    {
        return $this->priceCurrency->format(
            $value,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->storeManager->getStore(),
            $quoteCurrency
        );
    }

    /**
     * Get formatted price value including currency.
     *
     * @param float $price
     * @param string $code
     * @return string
     * @throws NoSuchEntityException
     */
    public function formatPrice($price, $code)
    {
        if (empty($code)) {
            $code = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        }

        if (!isset($this->currencyArray[$code])) {
            $this->currencyArray[$code] = $this->currencyFactory->create();
            $this->currencyArray[$code]->load($code);
        }

        return $this->currencyArray[$code]->formatPrecision($price, 2, [], true, false);
    }

    /**
     * Retrieve item original price.
     *
     * @param CartItemInterface $item
     * @param string $quoteCurrency
     * @param string $baseCurrency
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFormattedOriginalPrice(CartItemInterface $item, $quoteCurrency, $baseCurrency)
    {
        $rate = $this->getConvertRate($item, $quoteCurrency, $baseCurrency);
        return $this->formatProductPrice(
            ($item->getBasePrice() - $item->getBaseDiscountAmount() / $item->getQty()) * $rate,
            $quoteCurrency
        );
    }

    /**
     * Get item total.
     *
     * @param CartItemInterface $item
     * @param string $quoteCurrency
     * @param string $baseCurrency
     * @return string
     * @throws NoSuchEntityException
     */
    public function getItemTotal(CartItemInterface $item, $quoteCurrency, $baseCurrency)
    {
        $rate = $this->getConvertRate($item, $quoteCurrency, $baseCurrency);
        return $this->formatProductPrice(
            round($item->getBasePrice() * $rate, 2) * $item->getQty(),
            $quoteCurrency
        );
    }

    /**
     * Format catalog price.
     *
     * @param CartItemInterface $item
     * @param string $quoteCurrency
     * @param string $baseCurrency
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFormattedCatalogPrice(CartItemInterface $item, $quoteCurrency, $baseCurrency)
    {
        $rate = $this->getConvertRate($item, $quoteCurrency, $baseCurrency);
        return $this->formatProductPrice($item->getBasePrice() * $rate, $quoteCurrency);
    }
}
