<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Order;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Order\Totals;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TotalsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var Totals|MockObject
     */
    private $totals;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var Config|MockObject
     */
    private $taxConfigMock;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactoryMock;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyMock;

    /**
     * @var LayoutInterface|MockObject
     */
    private $layoutMock;

    /**
     * @var Template|MockObject
     */
    private $parentBlockMock;

    /**
     * @var OrderInterface|MockObject
     */
    private $orderMock;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var CartExtensionInterface|MockObject
     */
    private $quoteExtensionAttributes;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->setMethods([
                'getShippingPrice',
                'getNegotiatedPriceValue'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->taxConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'displaySalesSubtotalBoth',
                'displaySalesSubtotalInclTax',
                'displaySalesSubtotalExclTax',
                'displaySalesTaxWithGrandTotal'
            ])
            ->getMock();
        $this->quoteTotalsFactoryMock = $this->getMockBuilder(TotalsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $quoteTotalsMock = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Quote\Totals::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteTotalsFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($quoteTotalsMock);
        $this->priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->setMethods(['format'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentName', 'getBlock'])
            ->getMockForAbstractClass();
        $this->parentBlockMock = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder', 'getTotal', 'removeTotal', 'addTotalBefore'])
            ->getMock();
        $this->layoutMock->expects($this->any())->method('getParentName')
            ->willReturn('name');
        $this->layoutMock->expects($this->any())->method('getBlock')
            ->willReturn($this->parentBlockMock);

        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getQuoteId',
                'getBaseSubtotalInclTax',
                'getBaseSubtotal',
                'getBaseCurrencyCode',
                'getOrderCurrencyCode',
                'getBaseToOrderRate'
            ])
            ->getMockForAbstractClass();
        $this->orderMock->expects($this->any())->method('getQuoteId')->willReturn(1);

        $this->parentBlockMock->expects($this->any())->method('getOrder')
            ->willReturn($this->orderMock);

        $this->objectManagerHelper = new ObjectManager($this);
        $this->totals = $this->objectManagerHelper->getObject(
            Totals::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'taxConfig' => $this->taxConfigMock,
                'quoteTotalsFactory' => $this->quoteTotalsFactoryMock,
                'priceCurrency' => $this->priceCurrencyMock,
                'storeManager' => $storeManagerMock
            ]
        );
    }

    /**
     * Test for initTotals() method.
     *
     * @dataProvider initTotalsDataProvider
     * @param bool $isTaxDisplayedSalesSubtotalBoth
     * @param bool $displaySalesSubtotalInclTax
     * @param array $calls
     * @return void
     */
    public function testInitTotals($isTaxDisplayedSalesSubtotalBoth, $displaySalesSubtotalInclTax, array $calls)
    {
        $parentTotal = 20;
        $subTotalInclTax = 11;

        $this->setUpQuoteMock($parentTotal, $subTotalInclTax, 'EUR', 'USD', $calls);

        $this->taxConfigMock->expects($this->any())
            ->method('displaySalesSubtotalBoth')
            ->willReturn($isTaxDisplayedSalesSubtotalBoth);
        $this->taxConfigMock->expects($this->any())
            ->method('displaySalesSubtotalInclTax')
            ->willReturn($displaySalesSubtotalInclTax);
        $this->taxConfigMock->expects($this->any())
            ->method('displaySalesSubtotalExclTax')
            ->willReturn(true);

        $this->taxConfigMock->expects($this->any())->method('displaySalesTaxWithGrandTotal')->willReturn(true);

        $this->totals->setLayout($this->layoutMock);
        $this->totals->initTotals();
        $catalogTotals = $this->totals->getCatalogTotals();
        $expectedCatalogTotals = $this->buildExpectedCatalogTotals(
            $parentTotal,
            $subTotalInclTax,
            $isTaxDisplayedSalesSubtotalBoth
        );

        $this->assertEquals($expectedCatalogTotals, $catalogTotals);
    }

    /**
     * Data Provider for testInitTotals().
     *
     * @return array
     */
    public function initTotalsDataProvider()
    {
        return [
            [
                true, true,
                [
                    'parentBlock_getTotal' => 2,
                    'order_getBaseSubtotalInclTax' => 3,
                    'order_getBaseSubtotal' => 0,
                    'order_getBaseCurrencyCode' => 4,
                    'order_getOrderCurrencyCode' => 4,
                    'order_getBaseToOrderRate' => 4,
                    'quote_getShippingPrice' => 1,
                    'quote_getNegotiatedPriceValue' => 0,
                    'quote_getExtensionAttributes' => 1,
                    'quoteAttributes_getNegotiableQuote' => 2,
                    'quoteRepository_get' => 1
                ]
            ],
            [
                true, false,
                [
                    'parentBlock_getTotal' => 2,
                    'order_getBaseSubtotalInclTax' => 3,
                    'order_getBaseSubtotal' => 0,
                    'order_getBaseCurrencyCode' => 4,
                    'order_getOrderCurrencyCode' => 4,
                    'order_getBaseToOrderRate' => 4,
                    'quote_getShippingPrice' => 1,
                    'quote_getNegotiatedPriceValue' => 0,
                    'quote_getExtensionAttributes' => 1,
                    'quoteAttributes_getNegotiableQuote' => 2,
                    'quoteRepository_get' => 1
                ]
            ],
            [
                false, true,
                [
                    'parentBlock_getTotal' => 1,
                    'order_getBaseSubtotalInclTax' => 3,
                    'order_getBaseSubtotal' => 0,
                    'order_getBaseCurrencyCode' => 2,
                    'order_getOrderCurrencyCode' => 2,
                    'order_getBaseToOrderRate' => 2,
                    'quote_getShippingPrice' => 1,
                    'quote_getNegotiatedPriceValue' => 0,
                    'quote_getExtensionAttributes' => 1,
                    'quoteAttributes_getNegotiableQuote' => 2,
                    'quoteRepository_get' => 1
                ]
            ]
        ];
    }

    /**
     * Set Up Quote Mock.
     *
     * @param float $parentTotal
     * @param float $subTotalInclTax
     * @param string|null $baseCurrencyCode
     * @param string|null $orderCurrencyCode
     * @param array $calls
     * @return void
     */
    private function setUpQuoteMock(
        $parentTotal,
        $subTotalInclTax,
        $baseCurrencyCode,
        $orderCurrencyCode,
        array $calls
    ) {
        $this->parentBlockMock->expects($this->exactly($calls['parentBlock_getTotal']))->method('getTotal')
            ->willReturn($parentTotal);

        $this->orderMock->expects($this->exactly($calls['order_getBaseSubtotalInclTax']))
            ->method('getBaseSubtotalInclTax')->willReturn($subTotalInclTax);
        $this->orderMock->expects($this->exactly($calls['order_getBaseSubtotal']))->method('getBaseSubtotal')
            ->willReturn(10);
        $this->orderMock->expects($this->exactly($calls['order_getBaseCurrencyCode']))->method('getBaseCurrencyCode')
            ->willReturn($baseCurrencyCode);
        $this->orderMock->expects($this->exactly($calls['order_getOrderCurrencyCode']))->method('getOrderCurrencyCode')
            ->willReturn($orderCurrencyCode);
        $this->orderMock->expects($this->exactly($calls['order_getBaseToOrderRate']))->method('getBaseToOrderRate')
            ->willReturn(150);

        $this->quote->expects($this->exactly($calls['quote_getShippingPrice']))->method('getShippingPrice')
            ->willReturn(5);
        $this->quote->expects($this->exactly($calls['quote_getNegotiatedPriceValue']))
            ->method('getNegotiatedPriceValue')->willReturn(5.4);

        $this->quoteExtensionAttributes->expects($this->exactly($calls['quoteAttributes_getNegotiableQuote']))
            ->method('getNegotiableQuote')->willReturn($this->quote);

        $quoteMock = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteMock->expects($this->exactly($calls['quote_getExtensionAttributes']))->method('getExtensionAttributes')
            ->willReturn($this->quoteExtensionAttributes);

        $this->quoteRepositoryMock->expects($this->exactly($calls['quoteRepository_get']))->method('get')
            ->willReturn($quoteMock);
    }

    /**
     * Test for initTotals() method when getQuote() throws NoSuchEntityException.
     *
     * @return void
     */
    public function testGetQuoteWithNoSuchEntityException()
    {
        $phrase = new Phrase('message');
        $exception = new NoSuchEntityException($phrase);
        $this->quoteRepositoryMock->expects($this->once())->method('get')
            ->willThrowException($exception);

        $this->totals->setLayout($this->layoutMock);

        $this->assertFalse($this->totals->isNegotiableQuote());
    }

    /**
     * Build expected catalog totals.
     *
     * @param int $parentTotal
     * @param int $subTotalInclTax
     * @param bool $isTaxDisplayedSalesSubtotalBoth
     * @return array
     */
    private function buildExpectedCatalogTotals($parentTotal, $subTotalInclTax, $isTaxDisplayedSalesSubtotalBoth)
    {
        $catalogTotals = [];

        $catalogTotals['catalog_price'] = new DataObject(
            [
                'code' => 'catalog_price',
                'value' => null,
                'label' => __('Catalog Total Price'),
                'base_value' => null
            ]
        );
        $catalogTotals['negotiated_discount'] = new DataObject(
            [
                'code' => 'negotiated_discount',
                'value' => 1650,
                'label' => __('Negotiated Discount'),
                'base_value' => $subTotalInclTax
            ]
        );

        if ($isTaxDisplayedSalesSubtotalBoth === true) {
            $catalogTotals['catalog_price_excl_tax'] = new DataObject(
                [
                    'code' => 'catalog_price_excl_tax',
                    'value' => 0,
                    'label' => __('Catalog Total Price (Excl. Tax)'),
                    'base_value' => 0
                ]
            );
            $catalogTotals['catalog_price_incl_tax'] = new DataObject(
                [
                    'code' => 'catalog_price_incl_tax',
                    'value' => 0,
                    'label' => __('Catalog Total Price (Incl. Tax)'),
                    'base_value' => 0
                ]
            );
            $catalogTotals['subtotal_incl'] = $parentTotal;
            $catalogTotals['subtotal_excl'] = $parentTotal;
        } else {
            $catalogTotals['subtotal'] = $parentTotal;
        }

        return $catalogTotals;
    }

    /**
     * Test getCatalogTotals method.
     *
     * @return void
     */
    public function testGetCatalogTotals()
    {
        $this->assertEquals([], $this->totals->getCatalogTotals());
    }

    /**
     * Test isNegotiableQuote method.
     *
     * @return void
     */
    public function testIsNegotiableQuote()
    {
        $expected = true;
        $parentTotal = 20;
        $subTotalInclTax = 11;

        $calls = [
            'parentBlock_getTotal' => 0,
            'order_getBaseSubtotalInclTax' => 0,
            'order_getBaseSubtotal' => 0,
            'order_getBaseCurrencyCode' => 0,
            'order_getOrderCurrencyCode' => 0,
            'order_getBaseToOrderRate' => 0,
            'quote_getShippingPrice' => 1,
            'quote_getNegotiatedPriceValue' => 0,
            'quote_getExtensionAttributes' => 1,
            'quoteAttributes_getNegotiableQuote' => 2,
            'quoteRepository_get' => 1
        ];

        $this->setUpQuoteMock($parentTotal, $subTotalInclTax, 'EUR', 'USD', $calls);

        $this->totals->setLayout($this->layoutMock);

        $this->parentBlockMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $this->assertEquals($expected, $this->totals->isNegotiableQuote());
    }

    /**
     * Test getOrder method.
     *
     * @return void
     */
    public function testGetOrder()
    {
        $this->totals->setLayout($this->layoutMock);

        $this->parentBlockMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $this->assertEquals($this->orderMock, $this->totals->getOrder());
    }

    /**
     * Test displayPrice method.
     *
     * @param string $formattedPrice
     * @param string|null $baseCurrencyCode
     * @param string|null $orderCurrencyCode
     * @param string $expected
     * @param array $calls
     * @dataProvider displayPriceDataProvider
     * @return void
     */
    public function testDisplayPrice($formattedPrice, $baseCurrencyCode, $orderCurrencyCode, $expected, array $calls)
    {
        $parentTotal = 20;
        $subTotalInclTax = 11;
        $this->setUpQuoteMock($parentTotal, $subTotalInclTax, $baseCurrencyCode, $orderCurrencyCode, $calls);

        $this->totals->setLayout($this->layoutMock);

        $this->parentBlockMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);

        $basePrice = 432.3;
        $price = 235.3;
        $total = $this->getMockBuilder(DataObject::class)
            ->setMethods([
                'getBaseValue',
                'getValue'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $total->expects($this->exactly(2))->method('getBaseValue')->willReturn($basePrice);
        $total->expects($this->exactly($calls['total_getValue']))->method('getValue')->willReturn($price);

        $this->priceCurrencyMock->expects($this->exactly($calls['priceCurrency_format']))
            ->method('format')->willReturn($formattedPrice);

        $this->assertEquals($expected, $this->totals->displayPrice($total));
    }

    /**
     * Data provider for displayPrice() method.
     *
     * @return array
     */
    public function displayPriceDataProvider()
    {
        $formattedPrice = '434.5';

        $calls = [
            'parentBlock_getTotal' => 0,
            'order_getBaseSubtotalInclTax' => 0,
            'order_getBaseSubtotal' => 0,
            'order_getBaseCurrencyCode' => 2,
            'order_getBaseToOrderRate' => 0,
            'quote_getShippingPrice' => 0,
            'quote_getNegotiatedPriceValue' => 0,
            'quote_getExtensionAttributes' => 0,
            'quoteAttributes_getNegotiableQuote' => 0,
            'quoteRepository_get' => 0
        ];

        $data = [
            [
                $formattedPrice, 'EUR', 'EUR', $formattedPrice,
                'calls' => [
                    'total_getValue' => 0, 'priceCurrency_format' => 1, 'order_getOrderCurrencyCode' => 1
                ] + $calls
            ],
            [
                $formattedPrice, 'EUR', 'USD', $formattedPrice . '<br />[' . $formattedPrice . ']',
                'calls' => [
                    'total_getValue' => 1, 'priceCurrency_format' => 2, 'order_getOrderCurrencyCode' => 2
                ] + $calls
            ]
        ];
        return $data;
    }
}
