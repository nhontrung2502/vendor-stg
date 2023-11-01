<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\View\Totals;

use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals\Negotiation;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NegotiationTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

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
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var Negotiation
     */
    private $negotiation;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->postDataHelper = $this->createMock(PostHelper::class);
        $this->negotiableQuoteHelper = $this->createMock(Quote::class);
        $this->priceCurrency = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->restriction = $this->createMock(
            RestrictionInterface::class
        );

        $this->negotiation = $objectManager->getObject(
            Negotiation::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'postDataHelper' => $this->postDataHelper,
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'priceCurrency' => $this->priceCurrency,
                'restriction' => $this->restriction,
                'data' => []
            ]
        );
    }

    /**
     * Test getTotalOptions.
     *
     * @param int $type
     * @param string $expectType
     * @return void
     * @dataProvider getTotalOptionsDataProvider
     */
    public function testGetTotalOptions($type, $expectType)
    {
        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $layout->expects($this->any())->method('getParentName')->willReturn('parent');
        $parent = $this->createMock(Totals::class);
        $total = new DataObject(
            [
                'code' => NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE,
                'code_value' => NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE,
                'value' => 10,
                'type' => $type
            ]
        );
        $parent->expects($this->any())->method('getTotals')->willReturn(['negotiation' => $total]);
        $layout->expects($this->any())->method('getBlock')->willReturn($parent);
        $this->negotiation->setLayout($layout);

        $totals = $this->negotiation->getTotalOptions();

        $this->assertEquals($totals[$expectType]->getValue(), 10);
    }

    /**
     * Data provider for testGetTotalOptions.
     *
     * @return array
     */
    public function getTotalOptionsDataProvider()
    {
        return [
            [NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT, 'percentage'],
            [NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL, 'proposed'],
        ];
    }

    /**
     * Test getCatalogPrice.
     *
     * @return void
     */
    public function testGetCatalogPrice()
    {
        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $layout->expects($this->any())->method('getParentName')->willReturn('parent');
        $parent = $this->createMock(Totals::class);
        $total = new DataObject(
            [
                'value' => 50
            ]
        );
        $parent->expects($this->any())->method('getTotals')->willReturn(['catalog_price' => $total]);
        $layout->expects($this->any())->method('getBlock')->willReturn($parent);
        $this->negotiation->setLayout($layout);
        $this->assertEquals(50, $this->negotiation->getCatalogPrice());
    }

    /**
     * Test displayPrices.
     *
     * @return void
     */
    public function testDisplayPrices()
    {
        $this->assertSame('10.00', $this->negotiation->displayPrices(10));
    }
}
