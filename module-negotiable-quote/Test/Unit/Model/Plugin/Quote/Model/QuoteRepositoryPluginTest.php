<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Quote\Model;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteItemFactory;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\NegotiableQuote\Model\Plugin\Quote\Model\QuoteRepositoryPlugin;
use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\NegotiableQuote\Model\Plugin\Quote\Model\QuoteRepositoryPlugin class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteRepositoryPluginTest extends TestCase
{
    /**
     * @var QuoteRepositoryPlugin
     */
    private $quoteRepositoryPlugin;

    /**
     * @var CartExtensionFactory|MockObject
     */
    private $cartExtensionFactory;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var NegotiableQuoteRepository|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var QuoteGrid|MockObject
     */
    private $quoteGrid;

    /**
     * @var NegotiableQuoteItemFactory|MockObject
     */
    private $negotiableQuoteItemFactory;

    /**
     * @var CartItemInterface|MockObject
     */
    private $quoteItem;

    /**
     * @var ExtensionAttributesFactory|MockObject
     */
    private $extensionFactory;

    /**
     * @var CartItemExtensionInterface|MockObject
     */
    private $cartItemExtension;

    /**
     * @var CartExtensionInterface|MockObject
     */
    private $extensionAttributes;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var Totals|MockObject
     */
    private $quoteTotals;

    /**
     * @var NegotiableQuoteItem|MockObject
     */
    private $negotiableQuoteItemResource;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartExtensionFactory = $this->getMockBuilder(CartExtensionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->restriction = $this
            ->getMockBuilder(RestrictionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository = $this
            ->getMockBuilder(NegotiableQuoteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteGrid = $this->getMockBuilder(QuoteGrid::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteItemFactory = $this
            ->getMockBuilder(NegotiableQuoteItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'getNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $this->quoteItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'load',
                    'getTaxAmount',
                    'getTotalDiscountAmount',
                    'getBasePrice',
                    'getBaseTaxAmount',
                    'getChildren',
                    'isChildrenCalculated',
                    'getBaseDiscountAmount'
                ]
            )
            ->getMockForAbstractClass();
        $this->extensionFactory = $this->getMockBuilder(ExtensionAttributesFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteTotalsFactory = $this->getMockBuilder(TotalsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartItemExtension = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemResource = $this
            ->getMockBuilder(NegotiableQuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->quoteRepositoryPlugin = $objectManager->getObject(
            QuoteRepositoryPlugin::class,
            [
                'restriction' => $this->restriction,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'quoteGrid' => $this->quoteGrid,
                'negotiableQuoteItemFactory' => $this->negotiableQuoteItemFactory,
                'extensionFactory' => $this->extensionFactory,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'negotiableQuoteItemResource' => $this->negotiableQuoteItemResource,
            ]
        );
    }

    /**
     * Test for method aroundSave with quoteId.
     *
     * @param bool $isChildrenCalculated
     * @param float|int $originalPrice
     * @param int $pricesCalls
     * @param string $quoteStatus
     * @return void
     * @dataProvider aroundSaveDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAroundSave($isChildrenCalculated, $originalPrice, $pricesCalls, $quoteStatus)
    {
        $quoteId = 1;
        $quoteItemId = 2;
        $quoteItemBasePrice = 100;
        $quoteItemBaseTax = 10;
        $baseDiscountAmount = 5;
        $originalTax = 20;
        $originalDiscountAmount = 15;

        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMockForAbstractClass();
        $proceed = function () use ($quote) {
            return $quote;
        };
        $negotiableQuote = $this->mockNegotiableQuote($quote);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getQuoteId')->willReturnOnConsecutiveCalls(null, $quoteId);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $quote->expects($this->once())->method('setIsActive')->with(false)->willReturnSelf();
        $quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $negotiableQuote->expects($this->once())->method('setQuoteId')->with($quoteId)->willReturnSelf();
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('save')->with($negotiableQuote)->willReturn($negotiableQuote);
        $quote->expects($this->once())->method('getAllItems')->willReturn([$this->quoteItem]);
        $negotiableQuoteItem = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItem->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($this->extensionAttributes);
        $this->extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuoteItem')->willReturn($negotiableQuoteItem);
        $this->negotiableQuoteItemFactory->expects($this->exactly($pricesCalls))
            ->method('create')->willReturn($negotiableQuoteItem);
        $this->quoteItem->expects($this->atLeastOnce())->method('getItemId')->willReturn($quoteItemId);
        $negotiableQuoteItem->expects($this->exactly($pricesCalls))
            ->method('load')->with($quoteItemId)->willReturn($negotiableQuoteItem);
        $negotiableQuoteItem->expects($this->once())->method('setItemId')->with($quoteItemId)->willReturnSelf();
        $negotiableQuoteItem->expects($this->atLeastOnce())->method('getOriginalPrice')->willReturn($originalPrice);
        $this->quoteItem->expects($this->exactly($originalPrice ? 0 : 3))->method('getQty')->willReturn(1);
        $this->quoteItem->expects($this->exactly($pricesCalls))
            ->method('getBasePrice')->willReturn($quoteItemBasePrice);
        $this->quoteItem->expects($this->exactly($pricesCalls))
            ->method('getBaseTaxAmount')->willReturn($quoteItemBaseTax);
        $childItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseDiscountAmount'])
            ->getMockForAbstractClass();
        $this->quoteItem->expects($this->exactly($pricesCalls))->method('getChildren')->willReturn([$childItem]);
        $this->quoteItem->expects($this->exactly($pricesCalls))
            ->method('isChildrenCalculated')->willReturn($isChildrenCalculated);
        $childItem->expects($this->exactly(!$originalPrice && $isChildrenCalculated ? 1 : 0))
            ->method('getBaseDiscountAmount')->willReturn($baseDiscountAmount);
        $this->quoteItem->expects($this->exactly($isChildrenCalculated ? 0 : 1))
            ->method('getBaseDiscountAmount')->willReturn($baseDiscountAmount);
        $negotiableQuoteItem->expects($this->exactly($pricesCalls))
            ->method('getOriginalTaxAmount')->willReturn($originalTax);
        $negotiableQuoteItem->expects($this->exactly($pricesCalls))
            ->method('getOriginalDiscountAmount')->willReturn($originalDiscountAmount);
        $negotiableQuoteItem->expects($this->exactly($pricesCalls))
            ->method('setOriginalPrice')->with(null)->willReturnSelf();
        $negotiableQuoteItem->expects($this->exactly($pricesCalls))
            ->method('setOriginalTaxAmount')->with($originalTax)->willReturnSelf();
        $negotiableQuoteItem->expects($this->exactly($pricesCalls))
            ->method('setOriginalDiscountAmount')->with($originalDiscountAmount)->willReturnSelf();
        $this->extensionFactory->expects($this->exactly($pricesCalls))
            ->method('create')->willReturn($this->cartItemExtension);
        $this->cartItemExtension->expects($this->exactly($pricesCalls))
            ->method('setNegotiableQuoteItem')->with($negotiableQuoteItem)->willReturnSelf();
        $this->quoteItem->expects($this->exactly($pricesCalls))->method('setExtensionAttributes')
            ->with($this->cartItemExtension)->willReturnSelf();
        $this->quoteTotalsFactory->expects($this->once())
            ->method('create')
            ->with(['quote' => $quote])
            ->willReturn($this->quoteTotals);
        $negotiableQuote->expects($this->once())->method('getStatus')->willReturn($quoteStatus);
        if ($quoteStatus == NegotiableQuoteInterface::STATUS_CREATED) {
            $this->quoteTotals->expects($this->exactly(4))
                ->method('getCatalogTotalPrice')
                ->withConsecutive([true], [false], [false], [true])
                ->willReturn($originalPrice);
        } else {
            $this->quoteTotals->expects($this->exactly(2))
                ->method('getCatalogTotalPrice')
                ->withConsecutive([true], [false])
                ->willReturn($originalPrice);
            $this->quoteTotals->expects($this->exactly(2))
                ->method('getSubtotal')
                ->withConsecutive([false], [true])
                ->willReturn($originalPrice);
        }
        $negotiableQuote->expects($this->exactly(4))->method('setData')->willReturnSelf();
        $this->negotiableQuoteItemResource->expects($this->once())->method('saveList')->with([$negotiableQuoteItem]);
        $this->quoteGrid->expects($this->once())->method('refresh')->with($quote)->willReturnSelf();
        $this->quoteRepositoryPlugin->aroundSave($subject, $proceed, $quote);
    }

    /**
     * Test for method aroundGetActive without extensionAttributes.
     *
     * @return void
     */
    public function testAroundGetActiveWithoutAttributes()
    {
        $cartId = 42;
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $subject->expects($this->once())->method('get')->with($cartId, [])->willReturn($quote);
        $quote->expects($this->once())->method('getExtensionAttributes')->willReturn(null);
        $proceed = function () {
            return null;
        };
        $this->assertNull($this->quoteRepositoryPlugin->aroundGetActive($subject, $proceed, $cartId));
    }

    /**
     * Test for method aroundGetActive with extensionAttributes.
     *
     * @return void
     */
    public function testAroundGetActiveWithAttributes()
    {
        $cartId = 42;
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $subject->expects($this->once())->method('get')->willReturn($quote);
        $negotiableQuote = $this->mockNegotiableQuote($quote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);

        $proceed = function () {
            return null;
        };
        $this->assertEquals($quote, $this->quoteRepositoryPlugin->aroundGetActive($subject, $proceed, $cartId));
    }

    /**
     * Data provider for testAroundSave.
     *
     * @return array
     */
    public function aroundSaveDataProvider()
    {
        return [
            [true, null, 1, NegotiableQuoteInterface::STATUS_CREATED],
            [true, 10, 0, NegotiableQuoteInterface::STATUS_CREATED],
            [false, null, 1, NegotiableQuoteInterface::STATUS_CLOSED],
        ];
    }

    /**
     * Get mock for negotiable quote.
     *
     * @param MockObject $quote
     * @return MockObject
     */
    private function mockNegotiableQuote(MockObject $quote)
    {
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($this->extensionAttributes);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMockForAbstractClass();
        $this->extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')->willReturn($negotiableQuote);
        return $negotiableQuote;
    }
}
