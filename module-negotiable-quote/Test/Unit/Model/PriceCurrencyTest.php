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
use Magento\NegotiableQuote\Model\PriceCurrency;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\PriceCurrency class.
 */
class PriceCurrencyTest extends TestCase
{
    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyObject;

    /**
     * @var CurrencyFactory|MockObject
     */
    private $currencyFactory;

    /**
     * @var PriceCurrency
     */
    private $priceCurrency;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->priceCurrencyObject = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->currencyFactory = $this->getMockBuilder(CurrencyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->priceCurrency = $objectManager->getObject(
            PriceCurrency::class,
            [
                'priceCurrency' => $this->priceCurrencyObject,
                'currencyFactory' => $this->currencyFactory,
            ]
        );
    }

    /**
     * Test method getCurrency where currency set as string.
     *
     * @return void
     */
    public function testGetCurrencyWithString()
    {
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->currencyFactory->expects($this->once())->method('create')->willReturn($currency);
        $currency->expects($this->once())->method('load')->with('USD')->willReturnSelf();
        $this->priceCurrencyObject->expects($this->never())->method('getCurrency');

        $this->assertEquals($currency, $this->priceCurrency->getCurrency(null, 'USD'));
    }

    /**
     * Test method getCurrency where currency set as null.
     *
     * @return void
     */
    public function testGetCurrencyWithEmpty()
    {
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->currencyFactory->expects($this->never())->method('create');
        $currency->expects($this->never())->method('load');
        $this->priceCurrencyObject->expects($this->once())->method('getCurrency')->willReturn($currency);

        $this->assertEquals($currency, $this->priceCurrency->getCurrency(null, null));
    }
}
