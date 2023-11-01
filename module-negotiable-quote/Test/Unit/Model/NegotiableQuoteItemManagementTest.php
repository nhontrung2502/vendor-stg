<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\NegotiableQuote\Model\Action\Item\Price\Update;
use Magento\NegotiableQuote\Model\Customer\StoreCustomerRate;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\NegotiableQuoteItemFactory;
use Magento\NegotiableQuote\Model\NegotiableQuoteItemManagement;
use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for NegotiableQuoteItemManagement class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NegotiableQuoteItemManagementTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var Config|MockObject
     */
    private $taxConfig;

    /**
     * @var NegotiableQuoteItemFactory|MockObject
     */
    private $negotiableQuoteItemFactory;

    /**
     * @var ExtensionAttributesFactory|MockObject
     */
    private $extensionFactory;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var Update|MockObject
     */
    private $priceUpdater;

    /**
     * @var NegotiableQuoteItem|MockObject
     */
    private $negotiableQuoteItemResource;

    /**
     * @var StoreCustomerRate|MockObject
     */
    private $storeCustomerRate;

    /**
     * @var NegotiableQuoteItemManagement
     */
    private $neqotiableQuoteItemManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->taxConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteItemFactory = $this
            ->getMockBuilder(NegotiableQuoteItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->extensionFactory = $this->getMockBuilder(ExtensionAttributesFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteTotalsFactory = $this->getMockBuilder(TotalsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->priceUpdater = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteItemResource = $this
            ->getMockBuilder(NegotiableQuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeCustomerRate = $this
            ->getMockBuilder(StoreCustomerRate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->neqotiableQuoteItemManagement = $objectManager->getObject(
            NegotiableQuoteItemManagement::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'taxConfig' => $this->taxConfig,
                'negotiableQuoteItemFactory' => $this->negotiableQuoteItemFactory,
                'extensionFactory' => $this->extensionFactory,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'priceUpdater' => $this->priceUpdater,
                'negotiableQuoteItemResource' => $this->negotiableQuoteItemResource,
                'storeCustomerRate' => $this->storeCustomerRate,
            ]
        );
    }

    /**
     * Test for updateQuoteItemsCustomPrices method.
     *
     * @param float|int $originalPrice
     * @param float|int $originalTax
     * @param float|int $originalDiscount
     * @param float|int $negotiatedPriceValue
     * @param int|null $negotiatedPriceType
     * @param float|int $baseToQuoteRate
     * @param array $updateData
     * @param int $originalSubtotalCalls
     * @param int $negotiatedPriceTypeCalls
     * @param int $baseToQuoteRateCalls
     * @param bool $needSave
     * @param string $negotiatedStatus
     * @param float|null $quoteItemCustomPrice
     * @return void
     * @throws NoSuchEntityException
     * @dataProvider updateQuoteItemsCustomPricesDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testUpdateQuoteItemsCustomPrices(
        $originalPrice,
        $originalTax,
        $originalDiscount,
        $negotiatedPriceValue,
        ?int $negotiatedPriceType,
        $baseToQuoteRate,
        array $updateData,
        int $originalSubtotalCalls,
        int $negotiatedPriceTypeCalls,
        int $baseToQuoteRateCalls,
        bool $needSave,
        string $negotiatedStatus,
        ?float $quoteItemCustomPrice
    ) {
        $quoteId = 1;
        $quoteItemId = 2;
        $quoteItemQty = 1;
        $baseCurrency = 'USD';
        $quoteCurrency = 'EUR';
        $storeId = 3;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'setTotalsCollectedFlag', 'collectTotals'])
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $quoteNegotiation = $this->mockNegotiableQuote($quote);
        $quoteNegotiation->method('getStatus')->willReturn($negotiatedStatus);
        $quoteNegotiation->expects($this->atLeastOnce())
            ->method('getNegotiatedPriceValue')->willReturn($negotiatedPriceValue);
        $quoteNegotiation->expects($this->exactly($negotiatedPriceTypeCalls))
            ->method('getNegotiatedPriceType')->willReturn($negotiatedPriceType);

        $invPrice = $negotiatedStatus === NegotiableQuoteInterface::STATUS_CREATED && $quoteItemCustomPrice ? 1 : 0;
        $quoteNegotiation->expects($this->exactly($invPrice))
            ->method('setNegotiatedPriceValue');
        $quoteNegotiation->expects($this->exactly($invPrice))
            ->method('setNegotiatedPriceType')
            ->with(NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL);

        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCurrency')->willReturn($currency);
        list($quoteItem, $negotiableQuoteItem) = $this->mockQuoteItem($quoteItemId, $quoteItemCustomPrice);
        $quote->expects($this->atLeastOnce())->method('getAllItems')->willReturn([$quoteItem]);
        $negotiableQuoteItem->expects($this->once())->method('getOriginalPrice')->willReturn($originalPrice);
        $quoteItem->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->taxConfig->expects($this->once())->method('priceIncludesTax')->with($storeId)->willReturn(true);
        $negotiableQuoteItem->expects($this->once())->method('getOriginalTaxAmount')->willReturn($originalTax);
        $negotiableQuoteItem->expects($this->once())
            ->method('getOriginalDiscountAmount')->willReturn($originalDiscount);
        $currency->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $currency->expects($this->atLeastOnce())
            ->method('getQuoteCurrencyCode')->willReturn($quoteCurrency);
        $currency->expects($this->exactly($baseToQuoteRateCalls))
            ->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactory->expects($this->exactly($originalSubtotalCalls))
            ->method('create')->with(['quote' => $quote])->willReturn($quoteTotals);
        $quoteTotals->method('getCatalogTotalPrice')->willReturn($originalPrice);
        $quoteItem->expects($this->atLeastOnce())->method('getQty')->willReturn($quoteItemQty);
        $this->priceUpdater->expects($this->once())->method('update')->with(
            $quoteItem,
            ['qty' => $quoteItemQty] + $updateData
        )->willReturnSelf();
        $quoteItem->expects($this->exactly($negotiatedPriceValue ? 0 : 1))
            ->method('setCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->exactly($negotiatedPriceValue ? 0 : 1))
            ->method('setOriginalCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setBaseTaxCalculationPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setTaxCalculationPrice')->with(null)->willReturnSelf();
        $quote->expects($this->once())->method('setTotalsCollectedFlag')->with(false)->willReturnSelf();
        $this->quoteRepository->expects($this->exactly($needSave ? 1 : 0))
            ->method('save')->with($quote)->willReturn($quote);
        $quote->expects($this->once())->method('collectTotals')->willReturnSelf();

        if (isset($updateData['custom_price'])) {
            $this->storeCustomerRate
                ->expects($this->once())
                ->method('getStoreTaxRateFactor')
                ->with($quoteItem)
                ->willReturn(1.0);

            $this->storeCustomerRate
                ->expects($this->once())
                ->method('getCustomerTaxRateFactor')
                ->with($quoteItem)
                ->willReturn(1.0);
        }

        $this->assertTrue($this->neqotiableQuoteItemManagement->updateQuoteItemsCustomPrices($quoteId, $needSave));
    }

    /**
     * Test for setNegotiableQuotePrices method when negotiable quote has 'created' status.
     *
     * @return void
     */
    public function testSetNegotiableQuotePricesWithQuoteStatusCreated()
    {
        $quoteId = 1;
        $totalsCatalogTotalPrice = 5;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'setTotalsCollectedFlag', 'collectTotals'])
            ->getMockForAbstractClass();
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCurrency')->willReturn($currency);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $quote->expects($this->atLeastOnce())->method('getAllItems')->willReturn([]);
        $quoteNegotiation = $this->mockNegotiableQuote($quote);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactory->expects($this->once())
            ->method('create')->with(['quote' => $quote])->willReturn($quoteTotals);
        $quoteTotals->expects($this->exactly(4))->method('getCatalogTotalPrice')->withConsecutive(
            [true],
            [],
            [],
            [true]
        )
            ->willReturn($totalsCatalogTotalPrice);
        $quoteNegotiation->expects($this->exactly(4))->method('setData')->willReturnSelf();
        $quoteNegotiation->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $quote->expects($this->once())->method('setTotalsCollectedFlag')->with(false)->willReturnSelf();
        $quote->expects($this->once())->method('collectTotals')->willReturnSelf();

        $this->assertTrue($this->neqotiableQuoteItemManagement->updateQuoteItemsCustomPrices($quoteId, false));
    }

    /**
     * Test for setNegotiableQuotePrices method when negotiable quote does't have 'created' status.
     *
     * @return void
     */
    public function testSetNegotiableQuotePricesWithQuoteStatusNotCreated()
    {
        $quoteId = 1;
        $totalsCatalogTotalPrice = 5;
        $totalsSubtotal = 10;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'setTotalsCollectedFlag', 'collectTotals'])
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCurrency')->willReturn($currency);
        $quote->expects($this->atLeastOnce())->method('getAllItems')->willReturn([]);
        $quoteNegotiation = $this->mockNegotiableQuote($quote);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactory->expects($this->once())
            ->method('create')->with(['quote' => $quote])->willReturn($quoteTotals);
        $quoteTotals->expects($this->exactly(2))->method('getCatalogTotalPrice')->withConsecutive(
            [true],
            []
        )
            ->willReturn($totalsCatalogTotalPrice);
        $quoteTotals->expects($this->exactly(2))->method('getSubTotal')->withConsecutive(
            [],
            [true]
        )
            ->willReturn($totalsSubtotal);
        $quoteNegotiation->expects($this->exactly(4))->method('setData')->willReturnSelf();
        $quoteNegotiation->method('getStatus')
            ->willReturn('dummy_status');
        $quote->expects($this->once())->method('setTotalsCollectedFlag')->with(false)->willReturnSelf();
        $quote->expects($this->once())->method('collectTotals')->willReturnSelf();

        $this->assertTrue($this->neqotiableQuoteItemManagement->updateQuoteItemsCustomPrices($quoteId, false));
    }

    /**
     * Test for updateQuoteItemsCustomPrices method with exception.
     *
     * @return void
     */
    public function testUpdateQuoteItemsCustomPricesWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $quoteId = 1;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $this->neqotiableQuoteItemManagement->updateQuoteItemsCustomPrices($quoteId);
    }

    /**
     * Test for recalculateOriginalPriceTax method.
     *
     * @param bool $needRecalculatePrice
     * @param bool $needRecalculateRule
     * @param bool $isChildrenCalculated
     * @param int $recalculationCalls
     * @param int $baseToQuoteRateCalls
     * @return void
     * @dataProvider recalculateOriginalPriceTaxDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRecalculateOriginalPriceTax(
        $needRecalculatePrice,
        $needRecalculateRule,
        $isChildrenCalculated,
        $recalculationCalls,
        $baseToQuoteRateCalls
    ) {
        $quoteId = 1;
        $quoteItemId = 2;
        $originalPrice = 200;
        $originalTax = 10;
        $originalDiscount = 5;
        $baseCurrency = 'USD';
        $quoteCurrency = 'EUR';
        $baseToQuoteRate = 0.75;
        $quoteItemQty = 1;
        $appliedRuleIds = [98, 99];
        $storeId = 3;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['setTotalsCollectedFlag', 'collectTotals', 'getAppliedRuleIds', 'getShippingAddress', 'getAllItems']
            )
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->exactly(2))->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $quoteNegotiation = $this->mockNegotiableQuote($quote);
        $quoteNegotiation->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->exactly(2))->method('getCurrency')->willReturn($currency);
        list($quoteItem, $negotiableQuoteItem) = $this->mockQuoteItem($quoteItemId);
        $quote->expects($this->atLeastOnce())->method('getAllItems')->willReturn([$quoteItem]);
        $negotiableQuoteItem->expects($this->atLeastOnce())->method('getOriginalPrice')->willReturn($originalPrice);
        $quoteItem->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->taxConfig->expects($this->once())->method('priceIncludesTax')->with($storeId)->willReturn(true);
        $negotiableQuoteItem->expects($this->once())->method('getOriginalTaxAmount')->willReturn($originalTax);
        $negotiableQuoteItem->expects($this->atLeastOnce())
            ->method('getOriginalDiscountAmount')->willReturn($originalDiscount);
        $currency->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $currency->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn($quoteCurrency);
        $currency->expects($this->exactly($baseToQuoteRateCalls))
            ->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $quoteTotals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactory->expects($this->exactly(1))
            ->method('create')->with(['quote' => $quote])->willReturn($quoteTotals);
        $quoteTotals->method('getCatalogTotalPrice')->willReturn($originalPrice);
        $quoteTotals->method('getSubtotal')->willReturn($originalPrice);
        $quoteItem->expects($this->atLeastOnce())->method('setCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->atLeastOnce())->method('setOriginalCustomPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->atLeastOnce())->method('setBaseTaxCalculationPrice')->with(null)->willReturnSelf();
        $quoteItem->expects($this->atLeastOnce())->method('setTaxCalculationPrice')->with(null)->willReturnSelf();
        $quote->expects($this->exactly(3))->method('setTotalsCollectedFlag')->with(false)->willReturnSelf();
        $quote->expects($this->exactly(2))->method('collectTotals')->willReturnSelf();
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isObjectNew'])
            ->getMockForAbstractClass();
        $quote->expects($this->exactly(3))->method('getShippingAddress')->willReturn($address);
        $quote->expects($this->exactly(3))->method('getBillingAddress')->willReturn($address);
        $address->expects($this->exactly(6))->method('isObjectNew')
            ->withConsecutive([], [], [true], [true], [false], [false])
            ->willReturnOnConsecutiveCalls(false, false, true, true, false, false);
        $quoteItem->expects($this->once())->method('getBasePrice')->willReturn($originalPrice);
        $quoteItem->expects($this->once())->method('getBaseTaxAmount')->willReturn($originalTax);
        $quoteItem->expects($this->atLeastOnce())->method('getQty')->willReturn($quoteItemQty);
        $negotiableQuoteItem->expects($this->atLeastOnce())->method('setOriginalPrice')
            ->withConsecutive([$originalPrice], [$originalPrice + $originalDiscount])->willReturnSelf();
        $negotiableQuoteItem->expects($this->once())
            ->method('setOriginalTaxAmount')->with($originalTax / $quoteItemQty)->willReturnSelf();
        $childItem = $this->getMockBuilder(Item::class)
            ->addMethods(['getBaseDiscountAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->expects($this->exactly($recalculationCalls))->method('getChildren')->willReturn([$childItem]);
        $quoteItem->expects($this->exactly($recalculationCalls))
            ->method('isChildrenCalculated')->willReturn($isChildrenCalculated);
        $childItem->expects($this->exactly($needRecalculateRule && $isChildrenCalculated ? 1 : 0))
            ->method('getBaseDiscountAmount')->willReturn($originalDiscount);
        $quoteItem->expects($this->exactly($needRecalculateRule && !$isChildrenCalculated ? 1 : 0))
            ->method('getBaseDiscountAmount')->willReturn($originalDiscount);
        $negotiableQuoteItem->expects($this->exactly($recalculationCalls))
            ->method('setOriginalDiscountAmount')->with($originalDiscount / $quoteItemQty)->willReturnSelf();
        $quote->expects($this->exactly($recalculationCalls))->method('getAppliedRuleIds')->willReturn($appliedRuleIds);
        $quoteNegotiation->expects($this->exactly($recalculationCalls))
            ->method('setAppliedRuleIds')->with($appliedRuleIds)->willReturnSelf();
        $this->negotiableQuoteItemResource->expects($this->once())->method('saveList')->with([$negotiableQuoteItem]);
        $this->assertTrue(
            $this->neqotiableQuoteItemManagement->recalculateOriginalPriceTax(
                $quoteId,
                $needRecalculatePrice,
                $needRecalculateRule
            )
        );
    }

    /**
     * Data provider for testUpdateQuoteItemsCustomPrices.
     *
     * @return array
     */
    public function updateQuoteItemsCustomPricesDataProvider()
    {
        return [
            [
                200,    //original price
                10,     //original tax
                5,      //original discount
                30,     //negotiated price value
                NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT,    //negotiated price type
                0.5,   //rate for base currency to quote currency
                ['custom_price' => (200 + 10 - 5) * 0.5 * (1 - 30 / 100)],     //expected quote item total
                1,
                1,
                2,
                true,
                NegotiableQuoteInterface::STATUS_CREATED,
                5
            ],
            [
                200,
                10,
                5,
                30,
                NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_AMOUNT_DISCOUNT,
                0.5,
                ['custom_price' => (200 + 10 - 5) * 0.5 * (1 - 30 / 200)],
                2,
                1,
                3,
                true,
                NegotiableQuoteInterface::STATUS_CREATED,
                null
            ],
            [
                200,
                10,
                5,
                30,
                NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL,
                0.5,
                ['custom_price' => (200 + 10 - 5) * 0.5 * 30 / 200],
                2,
                1,
                3,
                true,
                NegotiableQuoteInterface::STATUS_CREATED,
                null
            ],
            [
                200,
                10,
                5,
                null,
                null,
                0.75,
                ['use_discount' => true],
                1,
                0,
                0,
                false,
                NegotiableQuoteInterface::STATUS_CREATED,
                null
            ],
        ];
    }

    /**
     * Data provider for testRecalculateOriginalPriceTax.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function recalculateOriginalPriceTaxDataProvider()
    {
        return [
            [true, true, true, 1, 0],
            [true, true, false, 1, 0],
            [false, false, false, 0, 1],
        ];
    }

    /**
     * Mock negotiable quote.
     *
     * @param MockObject $quote
     * @return MockObject
     */
    private function mockNegotiableQuote(MockObject $quote)
    {
        $quoteNegotiation = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')->willReturn($quoteNegotiation);
        $quoteNegotiation->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        return $quoteNegotiation;
    }

    /**
     * Mock quote item.
     *
     * @param int $quoteItemId
     * @param float|null $customPrice
     * @return array
     */
    private function mockQuoteItem($quoteItemId, $customPrice = null)
    {
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getBasePrice',
                    'getItemId',
                    'setExtensionAttributes',
                    'getExtensionAttributes',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setBaseTaxCalculationPrice',
                    'setTaxCalculationPrice',
                    'getBaseTaxAmount',
                    'getQty',
                    'getChildren',
                    'isChildrenCalculated',
                    'getBaseDiscountAmount',
                    'getStoreId',
                    'getCustomPrice'
                ]
            )
            ->getMock();
        $negotiableQuoteItem = $this->getMockBuilder(
            NegotiableQuoteItemInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['load', 'save'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemFactory->expects($this->once())->method('create')->willReturn($negotiableQuoteItem);
        $quoteItem->expects($this->atLeastOnce())->method('getItemId')->willReturn($quoteItemId);
        $quoteItem->method('getCustomPrice')->willReturn($customPrice);
        $negotiableQuoteItem->expects($this->once())->method('load')->with($quoteItemId)->willReturnSelf();
        $negotiableQuoteItem->expects($this->atLeastOnce())->method('setItemId')->with($quoteItemId)->willReturnSelf();
        $quoteItemExtension = $this->getMockBuilder(
            CartItemExtensionInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['setNegotiableQuoteItem', 'getNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $this->extensionFactory->expects($this->once())->method('create')->willReturn($quoteItemExtension);
        $quoteItemExtension->expects($this->once())
            ->method('setNegotiableQuoteItem')->with($negotiableQuoteItem)->willReturnSelf();
        $quoteItem->expects($this->once())
            ->method('setExtensionAttributes')->with($quoteItemExtension)->willReturnSelf();
        $quoteItem->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturnOnConsecutiveCalls(
                null,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension,
                $quoteItemExtension
            );
        $quoteItemExtension->expects($this->atLeastOnce())
            ->method('getNegotiableQuoteItem')->willReturn($negotiableQuoteItem);
        return [$quoteItem, $negotiableQuoteItem];
    }
}
