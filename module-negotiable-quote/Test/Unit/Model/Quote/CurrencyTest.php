<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteConverter;
use Magento\NegotiableQuote\Model\Quote\Currency;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\Quote\Currency class.
 */
class CurrencyTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $quoteItemManagement;

    /**
     * @var NegotiableQuoteConverter|MockObject
     */
    private $negotiableQuoteConverter;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteItemManagement = $this->getMockBuilder(
            NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteConverter = $this->getMockBuilder(
            NegotiableQuoteConverter::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->currency = $objectManager->getObject(
            Currency::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'quoteItemManagement' => $this->quoteItemManagement,
                'negotiableQuoteConverter' => $this->negotiableQuoteConverter,
            ]
        );
    }

    /**
     * Test updateQuoteCurrency method.
     *
     * @param array $quoteData
     * @param array $snapshotData
     * @return void
     * @dataProvider updateQuoteCurrencyDataProvider
     */
    public function testUpdateQuoteCurrency(array $quoteData, array $snapshotData)
    {
        $quoteId = 1;
        $quoteCurrencyCode = 'USD';
        $baseCurrencyCode = 'EUR';
        $quoteCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $baseCurrency = $this->getMockBuilder(\Magento\Directory\Model\Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currentCurrency = $this->getMockBuilder(\Magento\Directory\Model\Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository->expects($this->exactly(2))
            ->method('get')->withConsecutive([$quoteId, ['*']], [$quoteId])->willReturn($quote);
        $quoteExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot', 'setSnapshot'])
            ->getMockForAbstractClass();
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseCurrency', 'getCurrentCurrency'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($quoteExtensionAttributes);
        $quoteExtensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())
            ->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $quote->expects($this->atLeastOnce())->method('getCurrency')->willReturn($quoteCurrency);
        $quote->expects($this->exactly(2))->method('getStore')->willReturn($store);
        $store->expects($this->once())->method('getBaseCurrency')->willReturn($baseCurrency);
        $store->expects($this->once())->method('getCurrentCurrency')->willReturn($currentCurrency);
        $quoteCurrency->expects($this->atLeastOnce())->method('getQuoteCurrencyCode')->willReturn($quoteCurrencyCode);
        $currentCurrency->expects($this->once())->method('getCurrencyCode')->willReturn($quoteCurrencyCode);
        $quoteCurrency->expects($this->atLeastOnce())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $baseCurrency->expects($this->once())->method('getCurrencyCode')->willReturn($baseCurrencyCode);
        $quoteCurrency->expects($this->atLeastOnce())->method('getBaseToQuoteRate')->willReturn(0.7);
        $baseCurrency->expects($this->once())->method('getRate')->with($currentCurrency)->willReturn(0.8);
        $negotiableQuote->expects($this->atLeastOnce())->method('getSnapshot')->willReturn(json_encode($snapshotData));
        $this->negotiableQuoteConverter->expects($this->atLeastOnce())
            ->method('quoteToArray')->with($quote)->willReturn($quoteData);
        $this->currency->updateQuoteCurrency($quoteId);
    }

    /**
     * Data provider for updateQuoteCurrency method.
     *
     * @return array
     */
    public function updateQuoteCurrencyDataProvider()
    {
        $snapshotData = $quoteData = [
            'quote' => [
                'items_count' => 1,
                'items_qty' => 1,
                'base_grand_total' => 70,
                'base_subtotal' => 60,
                'base_subtotal_with_discount' => 60,
            ],
        ];
        $snapshotData['quote']['base_grand_total'] = 65;
        return [
            [$quoteData, $snapshotData],
            [$quoteData, $quoteData],
            [$quoteData, []],
        ];
    }
}
