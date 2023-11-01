<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\View\Shipping;

use Magento\Backend\Model\UrlInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface as NegotiableQuote;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Shipping\Method;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Cart\Currency;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Model\Store;
use Magento\Tax\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MethodTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var Method|MockObject
     */
    private $method;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var MockObject
     */
    private $sessionQuoteMock;

    /**
     * @var MockObject
     */
    private $orderCreate;

    /**
     * @var \Magento\Tax\Helper\Data|MockObject
     */
    private $taxData;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var TotalsCollector|MockObject
     */
    private $totalsCollector;

    /**
     * @var \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuote;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->orderCreate = $this->createMock(Create::class);
        $helper = new ObjectManager($this);
        $this->taxData = $helper->getObject(Data::class);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->priceCurrency = $this->getMockForAbstractClass(
            PriceCurrencyInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['format', 'getCurrencySymbol']
        );
        $this->restriction = $this->createMock(
            RestrictionInterface::class
        );
        $this->negotiableQuote = $this->createMock(
            \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface::class
        );
        $this->negotiableQuote->expects($this->any())->method('getIsRegularQuote')->willReturn(true);

        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            false,
            false,
            ['getNegotiableQuote']
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $customer->expects($this->any())->method('getDefaultShipping')
            ->willReturn(1);
        $address = $this->createMock(Address::class);
        $this->quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getBaseCurrencyCode'])
            ->onlyMethods(
                [
                    'getExtensionAttributes',
                    'getCustomer',
                    'getId',
                    'getAddressesCollection',
                    'getShippingAddress',
                    'getBillingAddress',
                    'getCurrency',
                    'getStore'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->quote->expects($this->any())->method('getCustomer')
            ->willReturn($customer);
        $this->quote->expects($this->any())->method('getId')
            ->willReturn(2);
        $this->quote->expects($this->any())->method('getAddressesCollection')
            ->willReturn([]);
        $this->quote->expects($this->any())->method('getShippingAddress')
            ->willReturn($address);
        $this->quote->expects($this->any())->method('getBillingAddress')
            ->willReturn($address);
        $baseCurrencyCode = 'USD';
        $this->quote->expects($this->any())->method('getBaseCurrencyCode')
            ->willReturn($baseCurrencyCode);
        $address->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->sessionQuoteMock = $this->createMock(\Magento\Backend\Model\Session\Quote::class);
        $this->totalsCollector = $this->createMock(TotalsCollector::class);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($this->quote);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);

        $objectManager = new ObjectManager($this);
        $this->method = $objectManager->getObject(
            Method::class,
            [
                'sessionQuote' => $this->sessionQuoteMock,
                'orderCreate' => $this->orderCreate,
                'priceCurrency' => $this->priceCurrency,
                '_taxData' => $this->taxData,
                'quoteRepository' => $this->quoteRepository,
                'restriction' => $this->restriction,
                'data' => [],
                '_urlBuilder' => $this->urlBuilderMock,
                'totalsCollector' => $this->totalsCollector,
                '_request' => $this->request
            ]
        );
    }

    /**
     * Test getQuote().
     *
     * @return void
     */
    public function testGetQuote()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with('quote_id')->willReturn(2);
        $this->negotiableQuote->expects($this->atLeastOnce())->method('getStatus')
            ->willReturn(NegotiableQuote::STATUS_CREATED);
        $this->totalsCollector->expects($this->once())->method('collectAddressTotals');
        $this->assertInstanceOf(Quote::class, $this->method->getQuote());
    }

    /**
     * Test getQuote().
     *
     * @return void
     */
    public function testGetQuoteNull()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with('quote_id')->willReturn(2);
        $this->quoteRepository->expects($this->any())->method('get')
            ->willThrowException(new NoSuchEntityException());
        $this->assertNull($this->method->getQuote());
    }

    /**
     * Test getQuote method for quote with ordered status.
     *
     * @return void
     */
    public function testGetQuoteWithOrderedStatus()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with('quote_id')->willReturn(2);
        $this->negotiableQuote->expects($this->once())->method('getStatus')
            ->willReturn(NegotiableQuote::STATUS_ORDERED);
        $this->totalsCollector->expects($this->never())->method('collectAddressTotals');
        $this->assertInstanceOf(Quote::class, $this->method->getQuote());
    }

    /**
     * Test getShippingMethodUrl().
     *
     * @return void
     */
    public function testGetShippingMethodUrl()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with('quote_id')->willReturn(2);
        $this->negotiableQuote->expects($this->atLeastOnce())->method('getStatus')
            ->willReturn(NegotiableQuote::STATUS_CREATED);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('*/*/shippingMethod/')
            ->willReturn('some value');
        $this->assertEquals('some value', $this->method->getShippingMethodUrl());
    }

    /**
     * Test getShippingMethodUrl() with exception.
     *
     * @return void
     */
    public function testGetShippingMethodUrlWithException()
    {
        $this->quoteRepository->expects($this->any())->method('get')
            ->willThrowException(new NoSuchEntityException());
        $this->assertEquals('', $this->method->getShippingMethodUrl());
    }

    /**
     * Test canEdit().
     *
     * @return void
     */
    public function testCanEdit()
    {
        $this->restriction->expects($this->once())->method('canSubmit')->willReturn(true);
        $this->assertTrue($this->method->canEdit());
    }

    /**
     * Test getProposedShippingPrice().
     *
     * @return void
     */
    public function testGetProposedShippingPrice()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['isAjax', null, false],
                ['quote_id', null, 2]
            ]
        );
        $price = 3.5;
        $this->negotiableQuote->expects($this->once())->method('getShippingPrice')->willReturn($price);
        $this->assertEquals($price, $this->method->getProposedShippingPrice());
    }

    /**
     * Test getProposedShippingPrice() with ajax param.
     *
     * @return void
     */
    public function testGetProposedShippingPriceWithAjax()
    {
        $price = 3.5;
        $this->request->expects($this->atLeastOnce())->method('getParam')->willReturnMap(
            [
                ['isAjax', null, true],
                ['custom_shipping_price', null, $price]
            ]
        );
        $this->negotiableQuote->expects($this->never())->method('getShippingPrice');
        $this->assertEquals($price, $this->method->getProposedShippingPrice());
    }

    /**
     * Test getCurrencySymbol().
     *
     * @return void
     */
    public function testGetCurrencySymbol()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with('quote_id')->willReturn(2);
        $baseCurrencyCode = 'USD';
        $currency = $this->getMockBuilder(Currency::class)
            ->setMethods(['getBaseCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $currency->expects($this->exactly(1))->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);

        $this->quote->expects($this->exactly(1))->method('getCurrency')->willReturn($currency);

        $symbol = '$';
        $this->priceCurrency->expects($this->once())->method('getCurrencySymbol')->willReturn($symbol);

        $this->assertEquals($symbol, $this->method->getCurrencySymbol());
    }

    /**
     * Test getOriginalShippingPrice() method.
     *
     * @param float $originalOriginalShippingPrice
     * @param float $price
     * @param string $expected
     * @dataProvider getOriginalShippingPriceDataProvider
     * @return void
     */
    public function testGetOriginalShippingPrice($originalOriginalShippingPrice, $price, $expected)
    {
        $store = $this->createMock(Store::class);
        $this->quote->expects($this->exactly(2))->method('getStore')->willReturn($store);
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with('quote_id')->willReturn(2);
        $rate = $this->getMockBuilder(Rate::class)
            ->setMethods(['getOriginalShippingPrice', 'getPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $rate->expects($this->any())->method('getOriginalShippingPrice')->willReturn($originalOriginalShippingPrice);
        $rate->expects($this->any())->method('getPrice')->willReturn($price);

        $this->priceCurrency->expects($this->once())->method('format')->willReturn($expected);

        $this->assertEquals($expected, $this->method->getOriginalShippingPrice($rate, true));
    }

    /**
     * Data provider for getOriginalShippingPrice() method.
     *
     * @return array
     */
    public function getOriginalShippingPriceDataProvider()
    {
        $price = 3.5;
        $expected = '$' . $price;

        return [
            [
                $price, null, $expected
            ],
            [
                null, $price, $expected
            ]
        ];
    }
}
