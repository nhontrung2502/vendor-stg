<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model;

use Magento\Backend\Model\Session\Quote;
use Magento\Directory\Model\Currency;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Plugin\Quote\Model\QuoteAdminhtmlPlugin;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class QuoteAdminhtmlPluginTest extends TestCase
{
    /**
     * @var QuoteAdminhtmlPlugin
     */
    private $quotePlugin;

    /**
     * @var Quote
     */
    private $quoteSession;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteSession = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCurrencyId'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->quotePlugin = $objectManager->getObject(
            QuoteAdminhtmlPlugin::class,
            [
                'quoteSession' => $this->quoteSession
            ]
        );
    }

    /**
     * Test afterGetStore() method.
     *
     * @return void
     */
    public function testAfterGetStoreWithCurrencyAvailable(): void
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->addMethods(['getQuoteCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $store = $this->createMock(Store::class);
        $quote->expects($this->once())->method('getQuoteCurrencyCode');
        $this->quoteSession->expects($this->once())->method('getCurrencyId')->willReturn('USD');
        $store->expects($this->once())->method('getAvailableCurrencyCodes')->willReturn(['USD']);
        $currency = $this->createMock(Currency::class);
        $currency->expects($this->once())->method('getRate')->willReturn(1);
        $store->expects($this->once())->method('getBaseCurrency')->willReturn($currency);
        $store->expects($this->once())->method('setCurrentCurrencyCode');
        $this->quotePlugin->afterGetStore($quote, $store);
    }

    /**
     * Test afterGetStore() method.
     *
     * @return void
     */
    public function testAfterGetStoreWithoutCurrencyAvailable(): void
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->addMethods(['getQuoteCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $store = $this->createMock(Store::class);
        $quote->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn('USD');
        $this->quoteSession->expects($this->once())->method('getCurrencyId')->willReturn(null);
        $store->expects($this->any())->method('getAvailableCurrencyCodes')->willReturn(['EUR']);
        $store->expects($this->never())->method('getBaseCurrency');
        $store->expects($this->never())->method('setCurrentCurrencyCode');
        $this->quotePlugin->afterGetStore($quote, $store);
    }

    /**
     * Test aroundBeforeSave() method.
     *
     * @return void
     */
    public function testAroundBeforeSaveWithSameCurrencies(): void
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->addMethods(
            [
                'getQuoteCurrencyCode',
                'getBaseToQuoteRate',
                'getBaseCurrencyCode',
                'setQuoteCurrencyCode',
                'setBaseToQuoteRate',
                'setBaseCurrencyCode',
                'getShippingAssignments',
                'setShippingAssignments'
            ]
        )
            ->onlyMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn('USD');
        $quote->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn('EUR');
        $quote->expects($this->atLeastOnce())->method('getBaseToQuoteRate')->willReturn(1.5);

        $quote->expects($this->never())->method('setQuoteCurrencyCode');
        $quote->expects($this->never())->method('setBaseToQuoteRate');
        $quote->expects($this->never())->method('setBaseCurrencyCode');

        $extension = $this->getMockBuilder(CartExtensionInterface::class)
            ->addMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $negotiable = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $negotiable->expects($this->once())->method('getIsRegularQuote')->willReturn(1);
        $negotiable->expects($this->once())->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_ORDERED);
        $extension->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiable);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extension);

        $proceed = function () {
        };
        $this->quotePlugin->aroundBeforeSave($quote, $proceed);
    }

    /**
     * Test aroundBeforeSave() method.
     *
     * @return void
     */
    public function testAroundBeforeSaveWithDifferentCurrencies(): void
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->addMethods(
            [
                'getQuoteCurrencyCode',
                'getBaseToQuoteRate',
                'getBaseCurrencyCode',
                'setQuoteCurrencyCode',
                'setBaseToQuoteRate',
                'setBaseCurrencyCode'
            ]
        )
            ->onlyMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->method('getQuoteCurrencyCode')
            ->willReturnOnConsecutiveCalls('USD');
        $quote->method('getBaseCurrencyCode')
            ->willReturnOnConsecutiveCalls('EUR');
        $quote->method('getBaseToQuoteRate')
            ->willReturnOnConsecutiveCalls(1.5);

        $quote->expects($this->once())->method('setQuoteCurrencyCode');
        $quote->expects($this->once())->method('setBaseToQuoteRate');
        $quote->expects($this->once())->method('setBaseCurrencyCode');

        $extension = $this->getMockBuilder(CartExtensionInterface::class)
            ->addMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $negotiable = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $negotiable->expects($this->once())->method('getIsRegularQuote')->willReturn(1);
        $negotiable->expects($this->once())->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_ORDERED);
        $extension->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiable);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extension);

        $proceed = function () {
        };
        $this->quotePlugin->aroundBeforeSave($quote, $proceed);
    }
}
