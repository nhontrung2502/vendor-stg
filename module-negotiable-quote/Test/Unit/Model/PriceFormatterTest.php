<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\PriceFormatter;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\PriceFormatter class.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PriceFormatterTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CurrencyFactory|MockObject
     */
    private $currencyFactory;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $negotiableQuoteItemManagement;

    /**
     * @var CartItemInterface|MockObject
     */
    private $cartItem;

    /**
     * @var StoreInterface|MockObject
     */
    private $store;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var Currency|MockObject
     */
    private $currency;

    /**
     * @var PriceFormatter
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->currencyFactory = $this->getMockBuilder(CurrencyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemManagement = $this->getMockBuilder(
            NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getBasePrice', 'getBaseDiscountAmount', 'getQty', 'getExtensionAttributes'])
            ->getMockForAbstractClass();
        $this->store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentCurrency'])
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrencyCode', 'getQuoteCurrencyCode', 'getBaseToQuoteRate'])
            ->getMockForAbstractClass();
        $this->currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            PriceFormatter::class,
            [
                'storeManager' => $this->storeManager,
                'currencyFactory' => $this->currencyFactory,
                'priceCurrency' => $this->priceCurrency,
                'negotiableQuoteItemManagement' => $this->negotiableQuoteItemManagement,
            ]
        );
    }

    /**
     * Test formatPrice method.
     *
     * @return void
     */
    public function testFormatPrice()
    {
        $price = 100.0000;
        $code = 'USD';
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->store);
        $this->store->expects($this->once())->method('getCurrentCurrency')->willReturn($this->currency);
        $this->currency->expects($this->once())->method('getCode')->willReturn($code);
        $this->currencyFactory->expects($this->once())->method('create')->willReturn($this->currency);
        $this->currency->expects($this->once())->method('load')->with($code)->willReturnSelf();
        $this->currency->expects($this->once())
            ->method('formatPrecision')
            ->with($price, 2, [], true, false)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->model->formatPrice($price, null));
    }

    /**
     * Test getFormattedOriginalPrice method.
     *
     * @return void
     */
    public function testGetFormattedOriginalPrice()
    {
        $price = 100.0000;
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->cartItem->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getBaseCurrencyCode')->willReturn('EUR');
        $this->quote->expects($this->once())->method('getQuoteCurrencyCode')->willReturn('USD');
        $this->quote->expects($this->once())->method('getBaseToQuoteRate')->willReturn(1.2);
        $this->cartItem->expects($this->once())->method('getBasePrice')->willReturn($price);
        $this->cartItem->expects($this->once())->method('getBaseDiscountAmount')->willReturn(0);
        $this->cartItem->expects($this->once())->method('getQty')->willReturn(1);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->store);
        $this->priceCurrency->expects($this->once())
            ->method('format')
            ->with(120, true, PriceCurrencyInterface::DEFAULT_PRECISION, $this->store, 'USD')
            ->willReturn('$120.00');

        $this->assertEquals(
            '$120.00',
            $this->model->getFormattedOriginalPrice($this->cartItem, $quoteCurrency, $baseCurrency)
        );
    }

    /**
     * Test getFormattedCartPrice method.
     *
     * @return void
     */
    public function testGetFormattedCartPrice()
    {
        $price = 100.0000;
        $quoteCurrency = 'USD';
        $baseCurrency = 'USD';
        $this->cartItem->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->quote->expects($this->once())->method('getQuoteCurrencyCode')->willReturn('EUR');
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')
            ->with(null, $baseCurrency)
            ->willReturn($this->currency);
        $this->currency->expects($this->atLeastOnce())->method('getRate')->with($quoteCurrency)->willReturn(null);
        $this->negotiableQuoteItemManagement->expects($this->once())
            ->method('getOriginalPriceByItem')
            ->with($this->cartItem)
            ->willReturn($price);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->store);
        $this->priceCurrency->expects($this->once())
            ->method('format')
            ->with(100, true, PriceCurrencyInterface::DEFAULT_PRECISION, $this->store, 'USD')
            ->willReturn('$100.00');

        $this->assertEquals(
            '$100.00',
            $this->model->getFormattedCartPrice($this->cartItem, $quoteCurrency, $baseCurrency)
        );
    }

    /**
     * Test getItemTotal method.
     *
     * @return void
     */
    public function testGetItemTotal()
    {
        $price = 100.0000;
        $quoteCurrency = 'USD';
        $baseCurrency = 'USD';
        $extensionAttributes = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $negotiableQuoteItem = $this->getMockBuilder(
            NegotiableQuoteItemInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartItem->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->cartItem->expects($this->once())->method('getQty')->willReturn(1);
        $this->quote->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->quote->expects($this->once())->method('getQuoteCurrencyCode')->willReturn('EUR');
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')
            ->with(null, $baseCurrency)
            ->willReturn($this->currency);
        $this->currency->expects($this->atLeastOnce())->method('getRate')->with($quoteCurrency)->willReturn(null);
        $this->cartItem->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuoteItem')
            ->willReturn($negotiableQuoteItem);
        $negotiableQuoteItem->expects($this->atLeastOnce())->method('getOriginalPrice')->willReturn($price);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->store);
        $this->priceCurrency->expects($this->once())
            ->method('format')
            ->with(100, true, PriceCurrencyInterface::DEFAULT_PRECISION, $this->store, 'USD')
            ->willReturn('$100.00');

        $this->assertEquals(
            '$100.00',
            $this->model->getItemTotal($this->cartItem, $quoteCurrency, $baseCurrency)
        );
    }

    /**
     * Test getFormattedCatalogPrice method.
     *
     * @return void
     */
    public function testGetFormattedCatalogPrice()
    {
        $price = 100.0000;
        $quoteCurrency = 'USD';
        $baseCurrency = 'USD';
        $extensionAttributes = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $negotiableQuoteItem = $this->getMockBuilder(
            NegotiableQuoteItemInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartItem->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->quote->expects($this->once())->method('getQuoteCurrencyCode')->willReturn('EUR');
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')
            ->with(null, $baseCurrency)
            ->willReturn($this->currency);
        $this->currency->expects($this->atLeastOnce())->method('getRate')->with($quoteCurrency)->willReturn(null);
        $this->cartItem->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuoteItem')
            ->willReturn($negotiableQuoteItem);
        $negotiableQuoteItem->expects($this->atLeastOnce())->method('getOriginalPrice')->willReturn($price);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->store);
        $this->priceCurrency->expects($this->once())
            ->method('format')
            ->with(100, true, PriceCurrencyInterface::DEFAULT_PRECISION, $this->store, 'USD')
            ->willReturn('$100.00');

        $this->assertEquals(
            '$100.00',
            $this->model->getFormattedCatalogPrice($this->cartItem, $quoteCurrency, $baseCurrency)
        );
    }
}
