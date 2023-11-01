<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\Totals;
use Magento\PurchaseOrder\Model\PriceFormatter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Model\Quote\TotalsFactory as QuoteTotalsFactory;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Block class for the original price total for a purchase order.
 *
 * @api
 * @since 100.2.0
 */
class Original extends Totals
{
    /**
     * @var string
     */
    private $code = 'catalog_price';

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param TaxConfig $taxConfig
     * @param QuoteTotalsFactory $quoteTotalsFactory
     * @param PriceFormatter $priceFormatter
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        TaxConfig $taxConfig,
        QuoteTotalsFactory $quoteTotalsFactory,
        PriceFormatter $priceFormatter,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $purchaseOrderRepository,
            $quoteRepository,
            $taxConfig,
            $quoteTotalsFactory,
            $priceFormatter,
            $data
        );
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Display the original price total for the current purchase order.
     *
     * @param float $price
     * @param string $currency
     * @return string
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function displayPrices($price = null, $currency = null)
    {
        $total = $this->getTotal();

        return $this->priceCurrency->format(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            isset($currency) ? $currency : $total->getBaseCurrency()
        );
    }

    /**
     * Get the original price total for the current purchase order.
     *
     * @return DataObject
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getTotal()
    {
        $totals = $this->getTotals();

        return $totals[$this->code];
    }
}
