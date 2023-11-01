<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Ui\Component\Listing\Column;

use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Ui\Component\Listing\Column\PriceAdmin;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PriceAdminTest extends TestCase
{
    /**
     * @var PriceAdmin
     */
    protected $column;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    protected $priceFormatter;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->priceFormatter = $this->getMockForAbstractClass(PriceCurrencyInterface::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $context = $this->getMockForAbstractClass(ContextInterface::class);
        $processorMock =
            $this->createMock(Processor::class);
        $context->expects($this->never())->method('getProcessor')->willReturn($processorMock);
        $this->column = $objectManagerHelper->getObject(
            PriceAdmin::class,
            [
                'context' => $context,
                'priceFormatter' => $this->priceFormatter,
                'storeManager' => $this->storeManager,
                'data' => [
                    'name' => 'price'
                ]
            ]
        );
    }

    /**
     * Test prepareDataSource function.
     */
    public function testPrepareDataSource()
    {
        $items = $this->getDataSourceItems();
        $expect = $this->getExpectedResult();

        $currency = $this->createMock(Currency::class);
        $currency->expects($this->atLeastOnce())->method('getRate')->with('EUR')->willReturn(1.5);
        $currency->expects($this->atLeastOnce())->method('getCode')->willReturn('USD');
        $this->priceFormatter->expects($this->atLeastOnce())->method('getCurrency')
            ->with(null, 'USD')->willReturn($currency);
        $this->priceFormatter->expects($this->atLeastOnce())->method('format')->willReturnArgument(0);

        $store = $this->createMock(Store::class);
        $store->expects($this->atLeastOnce())->method('getCurrentCurrency')->willReturn($currency);
        $store->expects($this->atLeastOnce())->method('getBaseCurrency')->willReturn($currency);
        $store->expects($this->atLeastOnce())->method('getAvailableCurrencyCodes')->willReturn(['USD', 'EUR']);
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);

        $dataSourceResult = $this->column->prepareDataSource(['data' => ['items' => $items]]);
        $this->assertEquals($expect, $dataSourceResult['data']['items']);
    }

    /**
     * Return Data source for items.
     *
     * @return array
     */
    private function getDataSourceItems()
    {
        return [
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CLOSED,
                'base_currency_code' => 'USD',
                'quote_currency_code' => 'EUR',
                'base_price' => 100,
                'price' => 150,
                'rate' => 1.5,
            ],
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CLOSED,
                'base_currency_code' => 'USD',
                'quote_currency_code' => 'USD',
                'base_price' => 100,
                'price' => 150,
                'rate' => 1.5,
            ],
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CREATED,
                'base_currency_code' => 'RUB',
                'quote_currency_code' => 'EUR',
                'base_price' => 100,
                'price' => 150,
                'rate' => 1.5,
                'store_id' => 1,
            ],
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CREATED,
                'base_currency_code' => 'RUB',
                'quote_currency_code' => 'RUB',
                'base_price' => 100,
                'price' => 150,
                'rate' => 1.5,
                'store_id' => 1,
            ]
        ];
    }

    /**
     * Return expected array for items.
     *
     * @return array
     */
    private function getExpectedResult()
    {
        return [
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CLOSED,
                'base_currency_code' => 'USD',
                'quote_currency_code' => 'EUR',
                'base_price' => 100,
                'price' => '100 (150)',
                'rate' => 1.5,
            ],
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CLOSED,
                'base_currency_code' => 'USD',
                'quote_currency_code' => 'USD',
                'base_price' => 100,
                'price' => '100',
                'rate' => 1.5,
            ],
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CREATED,
                'base_currency_code' => 'RUB',
                'quote_currency_code' => 'EUR',
                'base_price' => 100,
                'price' => 100,
                'rate' => 1.5,
                'store_id' => 1,
            ],
            [
                'status_original' => NegotiableQuoteInterface::STATUS_CREATED,
                'base_currency_code' => 'RUB',
                'quote_currency_code' => 'RUB',
                'base_price' => 100,
                'price' => 100,
                'rate' => 1.5,
                'store_id' => 1,
            ]
        ];
    }
}
