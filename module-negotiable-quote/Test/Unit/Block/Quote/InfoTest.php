<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Quote;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Block\Quote\Info;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Company\DetailsProvider;
use Magento\NegotiableQuote\Model\Company\DetailsProviderFactory;
use Magento\NegotiableQuote\Model\Customer\AddressProvider;
use Magento\NegotiableQuote\Model\Customer\AddressProviderFactory;
use Magento\NegotiableQuote\Model\Expiration;
use Magento\NegotiableQuote\Model\Status\BackendLabelProvider;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Info.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InfoTest extends TestCase
{
    /**
     * @var Info
     */
    private $info;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @var \Magento\Quote\Model\Quote|MockObject
     */
    private $quote;

    /**
     * @var Expiration|MockObject
     */
    private $expiration;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var AddressProvider|MockObject
     */
    private $addressProvider;

    /**
     * @var AddressProviderFactory|MockObject
     */
    private $addressProviderFactory;

    /**
     * @var DetailsProvider|MockObject
     */
    private $companyDetailsProvider;

    /**
     * @var DetailsProviderFactory|MockObject
     */
    private $companyDetailsProviderFactory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteHelper = $this->createMock(Quote::class);

        $this->expiration = $this->createMock(Expiration::class);

        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);

        $this->prepareQuoteMock();

        $localeDate = $this->getMockForAbstractClass(TimezoneInterface::class);
        $localeDate->expects($this->any())->method('formatDateTime')->willReturnArgument(1);

        $labelProvider = $this->createPartialMock(
            BackendLabelProvider::class,
            []
        );

        $this->addressProvider = null;
        $this->addressProviderFactory = $this
            ->getMockBuilder(AddressProviderFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyDetailsProvider = null;
        $this->companyDetailsProviderFactory = $this
            ->getMockBuilder(DetailsProviderFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->info = $objectManager->getObject(
            Info::class,
            [
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'addressProvider' => $this->addressProvider,
                'addressProviderFactory' => $this->addressProviderFactory,
                'companyDetailsProvider' => $this->companyDetailsProvider,
                'companyDetailsProviderFactory' => $this->companyDetailsProviderFactory,
                'labelProvider' => $labelProvider,
                'expiration' => $this->expiration,
                '_localeDate' => $localeDate,
                '_urlBuilder' => $this->urlBuilder,
                'data' => []
            ]
        );
        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->info->setLayout($layout);
    }

    /**
     * Prepare Quote mock.
     *
     * @return void
     */
    private function prepareQuoteMock()
    {
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getShippingAddress', 'getCustomer', 'getCurrency'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteHelper->expects($this->any())
            ->method('resolveCurrentQuote')
            ->willReturn($this->quote);
    }

    /**
     * Test getQuoteStatusLabel method.
     *
     * @dataProvider getQuoteStatusLabelDataProvider
     * @param NegotiableQuoteInterface|null $negotiableQuote
     * @param bool $expectedResult
     * @return void
     */
    public function testGetQuoteStatusLabel($negotiableQuote, $expectedResult)
    {
        /** @var CartExtensionInterface $extensionAttributes */
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();

        $this->quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $extensionAttributes
            ->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuote);

        $this->assertEquals($expectedResult, $this->info->getQuoteStatusLabel());
    }

    /**
     * Data provider for testGetQuoteStatusLabel.
     *
     * @return array
     */
    public function getQuoteStatusLabelDataProvider()
    {
        $quoteArray = [];
        //null quote
        $quoteArray[] = [null, ''];

        //quote with status
        $quoteNegotiation = $this->createMock(
            NegotiableQuoteInterface::class
        );
        $quoteNegotiation->expects($this->any())
            ->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $quoteArray[] = [$quoteNegotiation, 'New'];

        //quote without status
        $quoteNegotiation = $this->createMock(
            NegotiableQuoteInterface::class
        );
        $quoteArray[] = [$quoteNegotiation, ''];

        return $quoteArray;
    }

    /**
     * Test getAddressHtml method.
     *
     * @return void
     */
    public function testGetAddressHtml()
    {
        $addressHtml = 'Test Address';

        $shippingAddress = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quote->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->quote->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddress);
        $this->negotiableQuoteHelper->expects($this->exactly(2))
            ->method('resolveCurrentQuote')->willReturn($this->quote);

        $addressProvider = $this->getMockBuilder(AddressProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addressProvider->expects($this->once())->method('getRenderedAddress')
            ->with($shippingAddress)->willReturn($addressHtml);
        $this->addressProviderFactory->expects($this->once())->method('create')->willReturn($addressProvider);

        $this->assertEquals($addressHtml, $this->info->getAddressHtml());
    }

    /**
     * Test getQuoteOwnerFullName method.
     *
     * @return void
     */
    public function testGetQuoteOwnerFullName()
    {
        $ownerFullName = 'Test Owner Name';

        $this->negotiableQuoteHelper->expects($this->once())->method('resolveCurrentQuote')->willReturn($this->quote);

        $companyDetailsProvider = $this
            ->getMockBuilder(DetailsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $companyDetailsProvider->expects($this->once())->method('getQuoteOwnerName')->willReturn($ownerFullName);
        $this->companyDetailsProviderFactory->expects($this->once())
            ->method('create')->willReturn($companyDetailsProvider);

        $this->assertEquals($ownerFullName, $this->info->getQuoteOwnerFullName());
    }

    /**
     * Test getSalesRep method.
     *
     * @return  void
     */
    public function testGetSalesRep()
    {
        $salesRepresentativeName = 'Test Name';

        $this->negotiableQuoteHelper->expects($this->once())->method('resolveCurrentQuote')->willReturn($this->quote);

        $companyDetailsProvider = $this
            ->getMockBuilder(DetailsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $companyDetailsProvider->expects($this->once())->method('getSalesRepresentativeName')
            ->willReturn($salesRepresentativeName);

        $this->companyDetailsProviderFactory->expects($this->once())
            ->method('create')->willReturn($companyDetailsProvider);
        $this->assertEquals($salesRepresentativeName, $this->info->getSalesRep());
    }

    /**
     * Test getQuoteCreatedAt method.
     *
     * @param int $createdAt
     * @param bool $expectedResult
     * @dataProvider getQuoteCreatedAtDataProvider
     * @return void
     */
    public function testGetQuoteCreatedAt($createdAt, $expectedResult)
    {
        if (!empty($createdAt)) {
            $this->quote->expects($this->once())->method('getCreatedAt')->willReturn($createdAt);
        } else {
            $this->negotiableQuoteHelper
                ->expects($this->exactly(2))
                ->method('resolveCurrentQuote')
                ->willReturn(null);
        }

        $this->assertEquals($expectedResult, $this->info->getQuoteCreatedAt());
    }

    /**
     * Data provider for testGetQuoteCreatedAt.
     *
     * @return array
     */
    public function getQuoteCreatedAtDataProvider()
    {
        return [
            ['2015-12-22 17:55:51', \IntlDateFormatter::MEDIUM],
            [0, \IntlDateFormatter::MEDIUM],
        ];
    }

    /**
     * Test getQuoteName method.
     *
     * @dataProvider getQuoteNameDataProvider
     * @param string|bool $quoteName
     * @param bool $expectedResult
     * @return void
     */
    public function testGetQuoteName($quoteName, $expectedResult)
    {
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $this->quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        if ($quoteName) {
            $quoteNegotiation = $this->getMockForAbstractClass(NegotiableQuoteInterface::class);
            $quoteNegotiation->expects($this->any())
                ->method('getQuoteName')->willReturn($quoteName);
            $this->quote
                ->getExtensionAttributes()
                ->expects($this->any())
                ->method('getNegotiableQuote')
                ->willReturn($quoteNegotiation);
        }

        $this->assertEquals($expectedResult, $this->info->getQuoteName());
    }

    /**
     * Data provider for testGetQuoteName.
     *
     * @return array
     */
    public function getQuoteNameDataProvider()
    {
        return [
            [false, ''],
            ['test', 'test']
        ];
    }

    /**
     * Test getExpirationPeriodTime.
     *
     * @return void
     */
    public function testGetExpirationPeriodTime()
    {
        $date = new \DateTime();
        $this->expiration->expects($this->once())->method('getExpirationPeriodTime')->willReturn($date);

        $this->assertEquals($date, $this->info->getExpirationPeriodTime());
    }

    /**
     * Test isQuoteExpirationDateDisplayed.
     *
     * @param int $timeStamp
     * @param bool $expectedResult
     * @dataProvider isQuoteExpirationDateDisplayedDataProvider
     * @return void
     */
    public function testIsQuoteExpirationDateDisplayed($timeStamp, $expectedResult)
    {
        $date = $this->createMock(
            \DateTime::class
        );
        $this->expiration->expects($this->exactly(2))->method('getExpirationPeriodTime')->willReturn($date);
        $date->expects($this->once())->method('getTimestamp')->willReturn($timeStamp);

        $this->assertEquals($expectedResult, $this->info->isQuoteExpirationDateDisplayed());
    }

    /**
     * Data provider for testIsQuoteExpirationDateDisplayed.
     *
     * @return array
     */
    public function isQuoteExpirationDateDisplayedDataProvider()
    {
        return [
            [1466063520, true],
            [0, false]
        ];
    }

    /**
     * Test getAllAddresses.
     *
     * @return void
     */
    public function testGetAllAddresses()
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quote->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->negotiableQuoteHelper->expects($this->once())->method('resolveCurrentQuote')->willReturn($this->quote);

        $customerAddresses = [
            'Customer Address 1',
            'Customer Address 2'
        ];
        $addressProvider = $this->getMockBuilder(AddressProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addressProvider->expects($this->once())->method('getAllCustomerAddresses')->willReturn($customerAddresses);
        $this->addressProviderFactory->expects($this->once())->method('create')->willReturn($addressProvider);

        $this->assertEquals($customerAddresses, $this->info->getAllAddresses());
    }

    /**
     * Test isDefaultAddress method.
     *
     * @dataProvider isDefaultAddressDataProvider
     * @param int $customerId
     * @param int $addressId
     * @param array $call
     * @param bool $expectedResult
     * @return void
     */
    public function testIsDefaultAddress($customerId, $addressId, $expectedResult, array $call)
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())->method('getDefaultShipping')->willReturn($customerId);

        $shippingAddress = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\AddressInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCustomerAddressId']
        );
        $shippingAddress->expects($this->exactly($call['getCustomerAddressId']))
            ->method('getCustomerAddressId')->willReturn($customerId);

        $this->quote->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->quote->expects($this->exactly($call['getShippingAddress']))
            ->method('getShippingAddress')->willReturn($shippingAddress);
        $this->negotiableQuoteHelper->expects($this->exactly($call['resolveCurrentQuote']))
            ->method('resolveCurrentQuote')->willReturn($this->quote);

        $this->assertEquals($expectedResult, $this->info->isDefaultAddress($addressId));
    }

    /**
     * DataProvider for testIsDefaultAddress method.
     *
     * @return array
     */
    public function isDefaultAddressDataProvider()
    {
        return [
            [
                1, 1, true,
                [
                    'getCustomerAddressId' => 0,
                    'getShippingAddress' => 0,
                    'resolveCurrentQuote' => 1,
                ]
            ],
            [
                1, 2, false,
                [
                    'getCustomerAddressId' => 1,
                    'getShippingAddress' => 2,
                    'resolveCurrentQuote' => 4,
                ]
            ]
        ];
    }

    /**
     * Test getLineAddressHtml method.
     *
     * @return  void
     */
    public function testGetLineAddressHtml()
    {
        $addressId = 46;
        $renderedLineAddress = 'Test Line Address';

        $addressProvider = $this->getMockBuilder(AddressProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addressProvider->expects($this->once())->method('getRenderedLineAddress')
            ->with($addressId)->willReturn($renderedLineAddress);
        $this->addressProviderFactory->expects($this->once())->method('create')->willReturn($addressProvider);

        $this->assertEquals($renderedLineAddress, $this->info->getLineAddressHtml($addressId));
    }

    /**
     * Test getQuoteShippingAddressId.
     *
     * @dataProvider getQuoteShippingAddressIdDataProvider
     *
     * @param int|null $id
     * @param bool $expectedResult
     * @return void
     */
    public function testGetQuoteShippingAddressId($id, $expectedResult)
    {
        $shippingAddress = $this->getMockBuilder(ExtensibleDataInterface::class)
            ->addMethods(['getId'])
            ->getMockForAbstractClass();
        $this->quote->expects($this->any())
            ->method('getShippingAddress')->willReturn($shippingAddress);
        $shippingAddress->expects($this->any())
            ->method('getId')->willReturn($id);
        $this->assertEquals($expectedResult, $this->info->getQuoteShippingAddressId());
    }

    /**
     * DataProvider for testGetQuoteShippingAddressId.
     *
     * @return array
     */
    public function getQuoteShippingAddressIdDataProvider()
    {
        return [
            [1, true],
            [null, false]
        ];
    }

    /**
     * Test getAddShippingAddressUrl method.
     *
     * @return void
     */
    public function testGetAddShippingAddressUrl()
    {
        $path = 'customer/address/new';
        $url = 'http://example.com/';
        $quoteId = 1;
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->urlBuilder->expects($this->any())
            ->method('getUrl')
            ->with('customer/address/new', ['quoteId' => $quoteId])
            ->willReturn($url . $path . '/quoteId/' . $quoteId . '/');

        $this->assertEquals($url . $path . '/quoteId/' . $quoteId . '/', $this->info->getAddShippingAddressUrl());
    }

    /**
     * Test getUpdateShippingAddressUrl.
     *
     * @return void
     */
    public function testGetUpdateShippingAddressUrl()
    {
        $path = '*/*/updateAddress';
        $url = 'http://example.com/';
        $this->urlBuilder->expects($this->any())
            ->method('getUrl')->willReturn($url . $path);

        $this->assertEquals($url . $path, $this->info->getUpdateShippingAddressUrl());
    }

    /**
     * Prepare Currency mock.
     *
     * @param array $returned
     * @param array $calls
     * @return void
     */
    private function prepareCurrencyMock(array $returned, array $calls)
    {
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->setMethods(['getBaseCurrencyCode', 'getQuoteCurrencyCode', 'getBaseToQuoteRate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $currency->expects($this->exactly($calls['currency_getBaseCurrencyCode']))->method('getBaseCurrencyCode')
            ->willReturn($returned['currency_getBaseCurrencyCode']);

        $currency->expects($this->exactly($calls['currency_getQuoteCurrencyCode']))->method('getQuoteCurrencyCode')
            ->willReturn($returned['currency_getQuoteCurrencyCode']);

        $currency->expects($this->exactly($calls['currency_getBaseToQuoteRate']))->method('getBaseToQuoteRate')
            ->willReturn($returned['currency_getBaseToQuoteRate']);

        $this->quote->expects($this->exactly(1))->method('getCurrency')->willReturn($currency);
    }

    /**
     * Test getCurrencyRateLabel() method.
     *
     * @param string $baseCurrencyCode
     * @param string $quoteCurrencyCode
     * @param string $expects
     * @param array $calls
     * @dataProvider getCurrencyRateLabelDataProvider
     * @return void
     */
    public function testGetCurrencyRateLabel($baseCurrencyCode, $quoteCurrencyCode, $expects, array $calls)
    {
        $returned = [
            'currency_getBaseCurrencyCode' => $baseCurrencyCode,
            'currency_getQuoteCurrencyCode' => $quoteCurrencyCode,
            'currency_getBaseToQuoteRate' => 1
        ];
        $this->prepareCurrencyMock($returned, $calls);

        $this->assertEquals($expects, $this->info->getCurrencyRateLabel());
    }

    /**
     * Data provider for getCurrencyRateLabel() method.
     *
     * @return array
     */
    public function getCurrencyRateLabelDataProvider()
    {
        $calls = ['currency_getBaseToQuoteRate' => 0];
        return [
            [
                'USD', 'EUR', 'USD / EUR',
                ['currency_getBaseCurrencyCode' => 2, 'currency_getQuoteCurrencyCode' => 2] + $calls
            ],
            [
                'EUR', 'EUR', '',
                ['currency_getBaseCurrencyCode' => 1, 'currency_getQuoteCurrencyCode' => 1] + $calls
            ]
        ];
    }

    /**
     * Test getCurrencyRate() method.
     *
     * @param float $baseCurrencyCode
     * @param float $quoteCurrencyCode
     * @param float $expects
     * @param array $calls
     * @dataProvider getCurrencyRateDataProvider
     * @return void
     */
    public function testGetCurrencyRate($baseCurrencyCode, $quoteCurrencyCode, $expects, array $calls)
    {
        $returned = [
            'currency_getBaseCurrencyCode' => $baseCurrencyCode,
            'currency_getQuoteCurrencyCode' => $quoteCurrencyCode,
            'currency_getBaseToQuoteRate' => $expects
        ];

        $this->prepareCurrencyMock($returned, $calls);

        $this->assertEquals($expects, $this->info->getCurrencyRate());
    }

    /**
     * Data provider for getCurrencyRate() method.
     *
     * @return array
     */
    public function getCurrencyRateDataProvider()
    {
        return [
            [
                'USD', 'EUR', 1.6,
                [
                    'currency_getBaseCurrencyCode' => 1,
                    'currency_getQuoteCurrencyCode' => 1,
                    'currency_getBaseToQuoteRate' => 2
                ]
            ],
            [
                'EUR', 'EUR', 1,
                [
                    'currency_getBaseCurrencyCode' => 1,
                    'currency_getQuoteCurrencyCode' => 1,
                    'currency_getBaseToQuoteRate' => 0
                ]
            ]
        ];
    }
}
