<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Applier;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\NegotiableQuoteItem;
use Magento\NegotiableQuote\Model\PriceChecker;
use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\PriceChecker class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PriceCheckerTest extends TestCase
{
    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var HistoryManagementInterface|MockObject
     */
    private $historyManagement;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var Applier|MockObject
     */
    private $messageApplier;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $negotiableQuoteItemManagement;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var PriceChecker
     */
    private $priceChecker;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->historyManagement = $this->getMockBuilder(
            HistoryManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteTotalsFactory = $this->getMockBuilder(TotalsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->messageApplier = $this->getMockBuilder(
            Applier::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteItemManagement = $this->getMockBuilder(
            NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->priceChecker = $objectManager->getObject(
            PriceChecker::class,
            [
                'priceCurrency' => $this->priceCurrency,
                'historyManagement' => $this->historyManagement,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'messageApplier' => $this->messageApplier,
                'negotiableQuoteItemManagement' => $this->negotiableQuoteItemManagement
            ]
        );
    }

    /**
     * Test for setIsProductPriceChanged method.
     *
     * @return void
     */
    public function testSetIsProductPriceChanged()
    {
        $quoteId = 1;
        $oldPrice = 1.25;
        $newPrice = 1.23;
        $baseCurrencyCode = 'USD';
        $this->quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $negotiableQuoteItem = $this->getMockBuilder(NegotiableQuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuoteItem->expects($this->once())
            ->method('getData')->with(NegotiableQuoteItemInterface::ORIGINAL_PRICE)->willReturn($newPrice);
        $quoteItem = $this->prepareQuoteItemMock($negotiableQuoteItem);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->exactly(2))->method('getCurrency')->willReturn($currency);
        $currency->expects($this->exactly(2))->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $this->priceCurrency->expects($this->exactly(2))->method('format')
            ->withConsecutive(
                [$oldPrice, true, PriceCurrencyInterface::DEFAULT_PRECISION, null, $baseCurrencyCode],
                [$newPrice, true, PriceCurrencyInterface::DEFAULT_PRECISION, null, $baseCurrencyCode]
            )
            ->willReturnOnConsecutiveCalls('$' . $oldPrice, '$' . $newPrice);
        $this->historyManagement->expects($this->once())->method('addCustomLog')->with(
            $quoteId,
            [
                [
                    'product_sku' => 'sku',
                    'values' => [
                        [
                            'old_value' => '$' . $oldPrice,
                            'new_value' => '$' . $newPrice,
                            'field_subtitle' => 'Catalog Price: ',
                            'field_id' => 'catalog_price'
                        ],
                    ],
                    'product_id' => 1,
                    'field_id' => 'product_sku'
                ],
            ],
            false,
            true
        );
        $this->messageApplier->expects($this->once())
            ->method('setHasItemChanges')->with($this->quote)->willReturnSelf();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $this->quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(1.99);
        $negotiableQuote->expects($this->once())->method('setIsCustomerPriceChanged')->with(true)->willReturnSelf();
        $this->quote->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$quoteItem]);
        $this->assertTrue($this->priceChecker->setIsProductPriceChanged($this->quote, ['sku' => $oldPrice]));
    }

    /**
     * Test for setIsDiscountChanged method.
     *
     * @return void
     */
    public function testSetIsDiscountChanged()
    {
        $quoteId = 1;
        $oldDiscount = 1.23;
        $newDiscount = 1.25;
        $baseCurrencyCode = 'USD';
        $this->quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($quoteTotals);
        $quoteTotals->expects($this->once())->method('getCartTotalDiscount')->willReturn($newDiscount);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->exactly(2))->method('getCurrency')->willReturn($currency);
        $currency->expects($this->exactly(2))->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $this->priceCurrency->expects($this->exactly(2))->method('format')
            ->withConsecutive(
                [$newDiscount, true, PriceCurrencyInterface::DEFAULT_PRECISION, null, $baseCurrencyCode],
                [$oldDiscount, true, PriceCurrencyInterface::DEFAULT_PRECISION, null, $baseCurrencyCode]
            )->willReturnOnConsecutiveCalls('$' . $newDiscount, '$' . $oldDiscount);
        $this->historyManagement->expects($this->once())->method('addCustomLog')->with(
            $quoteId,
            [
                [
                    'field_title' => 'Quote Discount',
                    'field_id' => 'discount',
                    'values' => [
                        [
                            'field_subtitle' => 'Discount amount: ',
                            'new_value' => '$' . $newDiscount,
                            'old_value' => '$' . $oldDiscount,
                            'field_id' => 'amount'
                        ],
                    ],
                ],
            ],
            false,
            true
        );
        $this->messageApplier->expects($this->once())
            ->method('setIsDiscountChanged')->with($this->quote)->willReturnSelf();
        $this->assertTrue($this->priceChecker->setIsDiscountChanged($this->quote, $oldDiscount));
    }

    /**
     * Test for setIsCartPriceChanged method.
     *
     * @return void
     */
    public function testSetIsCartPriceChanged()
    {
        $quoteId = 1;
        $oldPrice = 1.23;
        $newPrice = 1.25;
        $baseCurrencyCode = 'USD';
        $this->quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $quoteItem = $this->prepareQuoteItemMock();
        $this->quote->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$quoteItem]);
        $this->negotiableQuoteItemManagement->expects($this->once())
            ->method('getOriginalPriceByItem')->willReturn($newPrice);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->exactly(2))->method('getCurrency')->willReturn($currency);
        $currency->expects($this->exactly(2))->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $this->priceCurrency->expects($this->exactly(2))->method('format')
            ->withConsecutive(
                [$oldPrice, true, PriceCurrencyInterface::DEFAULT_PRECISION, null, $baseCurrencyCode],
                [$newPrice, true, PriceCurrencyInterface::DEFAULT_PRECISION, null, $baseCurrencyCode]
            )->willReturnOnConsecutiveCalls('$' . $oldPrice, '$' . $newPrice);
        $this->historyManagement->expects($this->once())->method('addCustomLog')->with(
            $quoteId,
            [
                [
                    'product_sku' => 'sku',
                    'values' => [
                        [
                            'old_value' => '$' . $oldPrice,
                            'new_value' => '$' . $newPrice,
                            'field_subtitle' => 'Cart Price: ',
                            'field_id' => 'cart_price'
                        ],
                    ],
                    'product_id' => 1,
                    'field_id' => 'product_sku'
                ],
            ],
            false,
            true
        );
        $this->assertTrue(
            $this->priceChecker->setIsCartPriceChanged($this->quote, ['sku' => $oldPrice])
        );
    }

    /**
     * Test for setIsSubtotalOriginalTaxChanged method.
     *
     * @return void
     */
    public function testSetIsSubtotalOriginalTaxChanged()
    {
        $quoteId = 1;
        $oldTax = 1.23;
        $newTax = 1.25;
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteTotals->expects($this->once())->method('getOriginalTaxValue')->willReturn($newTax);
        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($quoteTotals);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn($oldTax);
        $negotiableQuote->expects($this->once())->method('setIsCustomerPriceChanged')->with(true)->willReturnSelf();
        $this->assertTrue($this->priceChecker->setIsSubtotalOriginalTaxChanged($this->quote, $oldTax));
    }

    /**
     * Test for setIsShippingTaxChanged method.
     *
     * @return void
     */
    public function testSetIsShippingTaxChanged()
    {
        $quoteId = 1;
        $oldTax = 1.23;
        $newTax = 1.25;
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteTotals->expects($this->once())->method('getShippingTaxValue')->willReturn($newTax);
        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($quoteTotals);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $negotiableQuote->expects($this->once())->method('getShippingPrice')->willReturn(0.25);
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn($oldTax);
        $negotiableQuote->expects($this->once())->method('setIsShippingTaxChanged')->with(true)->willReturnSelf();
        $this->assertTrue($this->priceChecker->setIsShippingTaxChanged($this->quote, $oldTax));
    }

    /**
     * Data provider for testCollectItemsPriceData.
     *
     * @return array
     */
    public function collectItemsPriceDataDataProvider()
    {
        $negotiableQuoteItem = $this->getMockBuilder(NegotiableQuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuoteItem->expects($this->once())->method('getData')->willReturn(1.23);
        $firstQuoteItem = $this->prepareQuoteItemMock($negotiableQuoteItem);
        $secondQuoteItem = $this->prepareQuoteItemMock();
        $negotiableQuoteItem->expects($this->once())
            ->method('getData')->with(NegotiableQuoteItemInterface::ORIGINAL_PRICE)->willReturn(1.23);

        return [
            [[], []],
            [[$firstQuoteItem], ['sku' => 1.23]],
            [[$secondQuoteItem], ['sku' => 0]]
        ];
    }

    /**
     * Prepare quoteItem mock.
     *
     * @param MockObject|null $negotiableQuoteItem [optional]
     * @return MockObject
     */
    private function prepareQuoteItemMock($negotiableQuoteItem = null)
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn('sku');
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $product->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $extensionAttributes = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($negotiableQuoteItem ? $this->atLeastOnce() : $this->never())
            ->method('getNegotiableQuoteItem')->willReturn($negotiableQuoteItem);
        $quoteItem->expects($negotiableQuoteItem ? $this->atLeastOnce() : $this->never())
            ->method('getExtensionAttributes')->willReturn($extensionAttributes);

        return $quoteItem;
    }
}
