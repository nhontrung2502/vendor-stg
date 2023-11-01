<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\View\Items;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Layout;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Items\Grid;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Items\SalesGrid;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GridTest extends TestCase
{
    /**
     * @var SalesGrid|MockObject
     */
    private $salesGridBlock;

    /**
     * @var Grid
     */
    private $grid;

    /**
     * @var Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var Config|MockObject
     */
    private $taxConfig;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var Item|MockObject
     */
    private $quoteItem;

    /**
     * Set up.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $urlBuilder = $this->createMock(
            UrlInterface::class
        );
        $urlBuilder->expects($this->any())->method('getUrl')->willReturn('http://magento.com/catalog/product/edit/1');
        $layout = $this->createMock(
            Layout::class
        );
        $block = $this->getMockBuilder(AbstractBlock::class)
            ->addMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->taxConfig = $this->createMock(
            Config::class
        );
        $this->restriction = $this->createMock(
            RestrictionInterface::class
        );
        $this->quoteTotalsFactory = $this->createPartialMock(
            TotalsFactory::class,
            ['create']
        );

        $block->expects($this->any())->method('getItems')->willReturn([$this->quoteItem]);
        $block->setLayout($layout);
        $layout->expects($this->any())->method('getBlock')->willReturn($block);
        $layout->expects($this->any())->method('getParentName')->willReturn('parentName');
        $this->salesGridBlock = $this->createPartialMock(
            SalesGrid::class,
            ['setQuote', 'setNameInLayout', 'getItems']
        );
        $this->salesGridBlock->expects($this->any())->method('setQuote')->willReturnSelf();
        $this->salesGridBlock->expects($this->any())->method('setNameInLayout')->willReturnSelf();

        $request = $this->createMock(
            Http::class
        );
        $request->expects($this->any())->method('getParam')->willReturn(1);

        $baseCurrencyCode = 'USD';
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->setMethods(['getBaseCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $currency->expects($this->any())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);

        $quote = $this->createMock(
            \Magento\Quote\Model\Quote::class
        );
        $quote->expects($this->any())->method('getCurrency')->willReturn($currency);

        $this->quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods([
                'getBaseRowTotal',
                'getBaseTaxAmount',
                'getBaseDiscountAmount',
                'getProduct',
                'getId',
                'getTaxAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteHelper = $this->createPartialMock(
            Quote::class,
            [
                'resolveCurrentQuote',
                'getFormattedCatalogPrice',
                'getFormattedOriginalPrice',
                'getFormattedCartPrice'
            ]
        );
        $this->negotiableQuoteHelper->expects($this->any())->method('resolveCurrentQuote')->willReturn($quote);

        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->setMethods(['format'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->grid = $objectManager->getObject(
            Grid::class,
            [
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'salesGridBlock' => $this->salesGridBlock,
                'data' => [],
                '_urlBuilder' => $urlBuilder,
                'restriction' => $this->restriction,
                'taxConfig' => $this->taxConfig,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'priceCurrency' => $this->priceCurrency
            ]
        );
    }

    /**
     * Test getQuote() method.
     *
     * @return void
     */
    public function testGetQuote()
    {
        $this->assertNotNull($this->grid->getQuote());
    }

    /**
     * Test getProductUrlByItem() method.
     *
     * @return void
     */
    public function testGetProductUrlByItem()
    {
        $this->quoteItem->expects($this->any())->method('getProduct')->willReturnSelf();
        $this->quoteItem->expects($this->any())->method('getId')->willReturn(1);
        $this->assertEquals(
            'http://magento.com/catalog/product/edit/1',
            $this->grid->getProductUrlByItem($this->quoteItem)
        );
    }

    /**
     * Test getItems() method.
     *
     * @return void
     */
    public function testGetItems()
    {
        $this->salesGridBlock->expects($this->any())->method('getItems')->willReturn([$this->quoteItem]);
        $this->assertEquals([$this->quoteItem], $this->grid->getItems());
    }

    /**
     * Test getFormattedCatalogPrice() method.
     *
     * @return void
     */
    public function testGetFormattedCatalogPrice()
    {
        $formattedPrice = 2354.3;
        $this->negotiableQuoteHelper->expects($this->exactly(1))
            ->method('getFormattedCatalogPrice')->willReturn($formattedPrice);

        $this->assertEquals($formattedPrice, $this->grid->getFormattedCatalogPrice($this->quoteItem));
    }

    /**
     * Test getFormattedOriginalPrice() method.
     *
     * @return void
     */
    public function testGetFormattedOriginalPrice()
    {
        $formattedPrice = 2354.3;
        $this->negotiableQuoteHelper->expects($this->exactly(1))
            ->method('getFormattedOriginalPrice')->willReturn($formattedPrice);

        $this->assertEquals($formattedPrice, $this->grid->getFormattedOriginalPrice($this->quoteItem));
    }

    /**
     * Test getFormattedCartPrice() method.
     *
     * @return void
     */
    public function testGetFormattedCartPrice()
    {
        $formattedPrice = 2354.3;
        $this->negotiableQuoteHelper->expects($this->exactly(1))
            ->method('getFormattedCartPrice')->willReturn($formattedPrice);

        $this->assertEquals($formattedPrice, $this->grid->getFormattedCartPrice($this->quoteItem));
    }

    /**
     * Test getFormattedSubtotal() method.
     *
     * @return void
     */
    public function testGetFormattedSubtotal()
    {
        $baseRowTotal = 23.4;
        $this->quoteItem->expects($this->exactly(1))->method('getBaseRowTotal')->willReturn($baseRowTotal);
        $baseDiscountAmount = 10.3;
        $this->quoteItem->expects($this->exactly(1))->method('getBaseDiscountAmount')->willReturn($baseDiscountAmount);

        $formattedPrice = '12.3';
        $this->priceCurrency->expects($this->exactly(1))->method('format')->willReturn($formattedPrice);

        $this->assertEquals($formattedPrice, $this->grid->getFormattedSubtotal($this->quoteItem));
    }

    /**
     * Test getFormattedCost() method.
     *
     * @return void
     */
    public function testGetFormattedCost()
    {
        $totals = $this->createMock(
            Totals::class
        );
        $this->quoteTotalsFactory->method('create')->willReturn($totals);
        $totals->expects($this->any())->method('getItemCost')->willReturn(20.20);

        $formattedPrice = '15.3';
        $this->priceCurrency->expects($this->exactly(1))->method('format')->willReturn($formattedPrice);

        $this->assertEquals($formattedPrice, $this->grid->getFormattedCost($this->quoteItem));
    }

    /**
     * Test canEdit() method.
     *
     * @return void
     *
     * @param bool $canSubmit
     * @param bool $expectedResult
     * @dataProvider canEditDataProvider
     */
    public function testCanEdit($canSubmit, $expectedResult)
    {
        $this->restriction->expects($this->any())->method('canSubmit')->willReturn($canSubmit);
        $this->assertEquals($expectedResult, $this->grid->canEdit());
    }

    /**
     * Data provider canEdit() for method.
     *
     * @return array
     */
    public function canEditDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }

    /**
     * Test getSubtotalTaxLabel method.
     *
     * @return void
     *
     * @param string $firstCall
     * @param bool $displaySalesSubtotalInclTax
     * @param string $secondCall
     * @param bool $displaySalesSubtotalBoth
     * @param string $expectedResult
     * @dataProvider getSubtotalTaxLabelDataProvider
     */
    public function testGetSubtotalTaxLabel(
        $firstCall,
        $displaySalesSubtotalInclTax,
        $secondCall,
        $displaySalesSubtotalBoth,
        $expectedResult
    ) {
        $this->taxConfig->expects($this->$firstCall())
            ->method('displaySalesSubtotalInclTax')
            ->willReturn($displaySalesSubtotalInclTax);
        $this->taxConfig->expects($this->$secondCall())
            ->method('displaySalesSubtotalBoth')
            ->willReturn($displaySalesSubtotalBoth);
        $this->assertEquals($expectedResult, $this->grid->getSubtotalTaxLabel());
    }

    /**
     * Data provider for getSubtotalTaxLabel() method.
     *
     * @return array
     */
    public function getSubtotalTaxLabelDataProvider()
    {
        return [
            ['once', true, 'never', false, 'Subtotal (Incl. Tax)'],
            ['once', false, 'once', true, 'Subtotal (Incl. Tax)'],
            ['once', false, 'once', false, 'Subtotal (Excl. Tax)']
        ];
    }

    /**
     * Test getItemTaxAmount method.
     *
     * @return void
     */
    public function testGetItemTaxAmount()
    {
        $this->quoteItem->expects($this->any())->method('getTaxAmount')->willReturn(20.20);

        $formattedPrice = '19.3';
        $this->priceCurrency->expects($this->exactly(1))->method('format')->willReturn($formattedPrice);

        $this->assertEquals($formattedPrice, $this->grid->getItemTaxAmount($this->quoteItem));
    }

    /**
     * Test getItemSubtotalTaxValue method.
     *
     * @return void
     *
     * @param string $firstCall
     * @param bool $displaySalesSubtotalInclTax
     * @param string $secondCall
     * @param bool $displaySalesSubtotalBoth
     * @param string $thirdCall
     * @param float $expectedResult
     * @dataProvider getItemSubtotalTaxValueDataProvider
     */
    public function testGetItemSubtotalTaxValue(
        $firstCall,
        $displaySalesSubtotalInclTax,
        $secondCall,
        $displaySalesSubtotalBoth,
        $thirdCall,
        $expectedResult
    ) {
        $this->taxConfig->expects($this->$firstCall())
            ->method('displaySalesSubtotalInclTax')
            ->willReturn($displaySalesSubtotalInclTax);
        $this->taxConfig->expects($this->$secondCall())
            ->method('displaySalesSubtotalBoth')
            ->willReturn($displaySalesSubtotalBoth);
        $this->quoteItem->expects($this->any())->method('getBaseRowTotal')->willReturn(80.60);
        $this->quoteItem->expects($this->$thirdCall())->method('getBaseTaxAmount')->willReturn(8.060);
        $this->quoteItem->expects($this->any())->method('getBaseDiscountAmount')->willReturn(10);

        $this->priceCurrency->expects($this->exactly(1))->method('format')->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->grid->getItemSubtotalTaxValue($this->quoteItem));
    }

    /**
     * Data provider for getItemSubtotalTaxValue() method.
     *
     * @return array
     */
    public function getItemSubtotalTaxValueDataProvider()
    {
        return [
            ['once', true, 'never', false, 'once', 78.66],
            ['once', false, 'once', true, 'once', 78.66],
            ['once', false, 'once', false, 'never', 70.60]
        ];
    }
}
