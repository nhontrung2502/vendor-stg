<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Quote\Totals;

use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Block\Quote\Totals\Original;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OriginalTest extends TestCase
{
    /**
     * @var PostHelper|MockObject
     */
    private $postDataHelper;

    /**
     * @var Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var LayoutInterface|MockObject
     */
    private $layout;

    /**
     * @var Original
     */
    private $original;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->postDataHelper = $this->createMock(PostHelper::class);
        $this->negotiableQuoteHelper = $this->createMock(Quote::class);
        $this->priceCurrency = $this->getMockForAbstractClass(PriceCurrencyInterface::class);
        $this->layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $objectManager = new ObjectManager($this);
        $this->original = $objectManager->getObject(
            Original::class,
            [
                'postDataHelper' => $this->postDataHelper,
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'priceCurrency' => $this->priceCurrency,
                '_layout' => $this->layout,
                'data' => [],
            ]
        );
    }

    /**
     * Test displayPrices.
     *
     * @return void
     */
    public function testDisplayPrices()
    {
        $parentName = 'parentName';
        $block = $this->getMockForAbstractClass(
            BlockInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getTotals']
        );
        $block->expects($this->once())->method('getTotals')->willReturn(['catalog_price' => 10.00]);
        $this->layout->expects($this->once())->method('getParentName')->willReturn($parentName);
        $this->layout->expects($this->once())->method('getBlock')->with($parentName)->willReturn($block);
        $price = '$10.00';
        $this->priceCurrency->expects($this->once())->method('format')->willReturn($price);

        $this->assertEquals($price, $this->original->displayPrices(10.00, 'USD'));
    }

    /**
     * Test formatPrice.
     *
     * @return void
     */
    public function testFormatPrice()
    {
        $price = '$10.00';
        $this->negotiableQuoteHelper->expects($this->once())->method('formatPrice')->willReturn($price);

        $this->assertEquals($price, $this->original->formatPrice(10.00, 'USD'));
    }

    /**
     * Test getCurrencySymbol.
     *
     * @return void
     */
    public function testGetCurrencySymbol()
    {
        $currency = $this->getMockForAbstractClass(CurrencyInterface::class);
        $currency->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $quote = $this->getMockForAbstractClass(CartInterface::class);
        $quote->expects($this->once())->method('getCurrency')->willReturn($currency);
        $this->negotiableQuoteHelper->expects($this->once())->method('resolveCurrentQuote')->willReturn($quote);
        $currencySymbol = '$';
        $this->priceCurrency->expects($this->once())->method('getCurrencySymbol')->willReturn($currencySymbol);

        $this->assertEquals($currencySymbol, $this->original->getCurrencySymbol());
    }
}
