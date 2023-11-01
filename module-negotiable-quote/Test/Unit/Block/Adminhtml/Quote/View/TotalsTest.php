<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\View;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TotalsTest extends TestCase
{
    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyMock;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var Quote|MockObject
     */
    protected $negotiableQuoteHelper;

    /**
     * @var Totals|MockObject
     */
    private $totals;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->priceCurrencyMock = $this->createMock(
            PriceCurrencyInterface::class
        );

        $this->quoteTotalsFactory = $this->createPartialMock(
            TotalsFactory::class,
            ['create']
        );

        $this->negotiableQuoteHelper = $this->createMock(
            Quote::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->totals = $objectManagerHelper->getObject(
            Totals::class,
            [
                'priceCurrency' => $this->priceCurrencyMock,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper
            ]
        );
    }

    /**
     * Set Up quote Mock.
     *
     * @return void
     */
    private function setUpQuoteMock()
    {
        $baseCurrencyCode = 'USD';
        $quoteCurrencyCode = 'EUR';
        $quoteCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->setMethods([
                'getBaseCurrencyCode',
                'getQuoteCurrencyCode'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency->expects($this->any())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $quoteCurrency->expects($this->any())->method('getQuoteCurrencyCode')->willReturn($quoteCurrencyCode);

        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getExtensionAttributes',
                'getCurrency'
            ])
            ->getMockForAbstractClass();
        $this->quote->expects($this->any())->method('getCurrency')->willReturn($quoteCurrency);

        $this->negotiableQuoteHelper->expects($this->atLeastOnce())
            ->method('resolveCurrentQuote')->willReturn($this->quote);
    }

    /**
     * Test displayPrices() method.
     *
     * @return void
     */
    public function testDisplayPrices()
    {
        $this->setUpQuoteMock();

        $price = 5.5;
        $this->priceCurrencyMock->expects($this->once())->method('format')->willReturn($price);

        $this->assertEquals(5.5, $this->totals->displayPrices($price));
    }

    /**
     * Test getTotals() method.
     *
     * @return void
     */
    public function testGetTotals()
    {
        $this->setUpQuoteMock();

        $quoteTotals = $this->createMock(
            \Magento\NegotiableQuote\Model\Quote\Totals::class
        );
        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($quoteTotals);

        $quoteTotals->expects($this->once())->method('getTotalCost');
        $quoteTotals->expects($this->once())->method('getQuoteShippingPrice');

        $negotiableQuote = $this->createMock(
            NegotiableQuoteInterface::class
        );
        $negotiableQuote->expects($this->atLeastOnce())->method('getNegotiatedPriceType');
        $negotiableQuote->expects($this->atLeastOnce())->method('getNegotiatedPriceValue');

        $quoteExtension = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $quoteExtension->expects($this->any())->method('getNegotiableQuote')->willReturn($negotiableQuote);

        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($quoteExtension);

        $this->assertIsArray($this->totals->getTotals());
    }
}
