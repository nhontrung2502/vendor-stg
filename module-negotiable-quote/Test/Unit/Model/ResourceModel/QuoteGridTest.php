<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\ResourceModel;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid;
use Magento\NegotiableQuote\Model\Restriction\Admin;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteGridTest extends TestCase
{
    /**
     * @var QuoteGrid
     */
    private $resource;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var Totals|MockObject
     */
    private $totals;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resources;

    /**
     * @var Admin|MockObject
     */
    private $restriction;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var State|MockObject
     */
    private $appState;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resources = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'getExtensionAttributes'])
            ->getMockForAbstractClass();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteTotalsFactory = $this->getMockBuilder(TotalsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->totals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCatalogTotalPrice', 'getSubtotal'])
            ->getMock();
        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId', 'getId'])
            ->getMockForAbstractClass();
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restriction = $this->getMockBuilder(Admin::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteManagement = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->appState = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->resource = $objectManagerHelper->getObject(
            QuoteGrid::class,
            [
                'resources' => $this->resources,
                'logger' => $this->logger,
                'companyManagement' => $this->companyManagement,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'restriction' => $this->restriction,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'appState' => $this->appState
            ]
        );
    }

    /**
     * Test for method refresh.
     *
     * @param string $areaCode
     * @param int $subtotalCalls
     * @param int $quoteStatus
     * @dataProvider dataProviderTestRefresh
     * @return void
     */
    public function testRefresh($areaCode, $subtotalCalls, $quoteStatus)
    {
        $quoteId = 1;
        $customerId = 2;
        $companyId = 33;
        $companyName = 'Test Company';
        $baseCurrencyCode = 'USD';
        $quoteCurrencyCode = 'EUR';
        $baseToQuoteRate = 0.75;
        $totalPrice = 100;
        $salesRepName = 'Customer Name';
        $this->prepareResourcesMock();
        $this->customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->company->expects($this->exactly(2))->method('getSalesRepresentativeId')->willReturn($customerId);
        $this->company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->company->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->willReturn($this->company);
        $this->companyManagement->expects($this->exactly(2))
            ->method('getSalesRepresentative')->willReturn($salesRepName);
        $this->quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($this->customer);
        $this->totals->expects($this->atLeastOnce())->method('getCatalogTotalPrice')->willReturn($totalPrice);
        $this->totals->expects($this->exactly($subtotalCalls))->method('getSubtotal')->willReturn($totalPrice);
        $this->restriction->expects($this->once())->method('isLockMessageDisplayed')->willReturn(true);
        $this->appState->expects($this->once())->method('getAreaCode')->willReturn($areaCode);
        $this->quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getSnapshotQuote')->with($quoteId)->willReturn($this->quote);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasData'])
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())->method('getStatus')->willReturn($quoteStatus);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->quoteTotalsFactory->expects($this->atLeastOnce())
            ->method('create')->with(['quote' => $this->quote])->willReturn($this->totals);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->once())->method('getCurrency')->willReturn($currency);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('hasData')
            ->withConsecutive([NegotiableQuoteInterface::QUOTE_STATUS], [NegotiableQuoteInterface::QUOTE_NAME])
            ->willReturn(true);
        $negotiableQuote->expects($this->once())->method('getQuoteName')->willReturn('Quote Name');
        $currency->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $currency->expects($this->once())->method('getQuoteCurrencyCode')->willReturn($quoteCurrencyCode);
        $currency->expects($this->once())->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $result = $this->resource->refresh($this->quote);

        $this->assertEquals($this->resource, $result);
    }

    /**
     * Test refresh with exception.
     *
     * @return void
     */
    public function testRefreshWithException()
    {
        $baseCurrencyCode = 'USD';
        $quoteCurrencyCode = 'EUR';
        $baseToQuoteRate = 0.75;
        $exception = new \Exception();
        $this->logger->expects($this->once())->method('critical');
        $this->customer->expects($this->atLeastOnce())->method('getId')->willReturn(14);
        $this->company->expects($this->exactly(2))->method('getSalesRepresentativeId')->willReturn(14);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')->willReturn($this->company);
        $this->quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($this->customer);
        $this->totals->expects($this->atLeastOnce())->method('getCatalogTotalPrice')->willReturn(100);

        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasData'])
            ->getMockForAbstractClass();
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_ORDERED);
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($this->totals);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->once())->method('getCurrency')->willReturn($currency);
        $currency->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $currency->expects($this->once())->method('getQuoteCurrencyCode')->willReturn($quoteCurrencyCode);
        $currency->expects($this->once())->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $this->resources->expects($this->once())->method('getConnection')->willThrowException($exception);
        $result = $this->resource->refresh($this->quote);

        $this->assertEquals($this->resource, $result);
    }

    /**
     * Test refresh with exception where collecting Company Fields.
     *
     * @return void
     */
    public function testRefreshWithExceptionWhereCollectingCompanyFields()
    {
        $customerId = 14;
        $baseCurrencyCode = 'USD';
        $quoteCurrencyCode = 'EUR';
        $baseToQuoteRate = 0.75;
        $exception = new \Exception();
        $this->prepareResourcesMock();
        $this->customer->expects($this->exactly(3))->method('getId')->willReturn($customerId);
        $this->quote->expects($this->exactly(4))->method('getCustomer')->willReturn($this->customer);
        $this->restriction->expects($this->never())->method('isLockMessageDisplayed')->willReturn(false);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasData'])
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturn($this->quote);

        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($this->totals);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->once())->method('getCurrency')->willReturn($currency);
        $currency->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $currency->expects($this->once())->method('getQuoteCurrencyCode')->willReturn($quoteCurrencyCode);
        $currency->expects($this->once())->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->willThrowException($exception);
        $this->assertEquals($this->resource, $this->resource->refresh($this->quote));
    }

    /**
     * Test for method refreshValue.
     *
     * @return void
     */
    public function testRefreshValue()
    {
        $exception = new \Exception();
        $this->resources->expects($this->once())->method('getConnection')->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical');
        $result = $this->resource->refreshValue(
            QuoteGrid::QUOTE_ID,
            1,
            QuoteGrid::COMPANY_NAME,
            2
        );

        $this->assertEquals($this->resource, $result);
    }

    /**
     * Test refreshValue with exception.
     *
     * @return void
     */
    public function testRefreshValueWithException()
    {
        $this->prepareResourcesMock();
        $this->connection->expects($this->once())->method('update');
        $result = $this->resource->refreshValue(
            QuoteGrid::QUOTE_ID,
            1,
            QuoteGrid::COMPANY_NAME,
            2
        );
        $this->assertEquals($this->resource, $result);
    }

    /**
     * Test for method refreshValue without update.
     *
     * @return void
     */
    public function testRefreshValueWithoutUpdate()
    {
        $this->connection->expects($this->never())->method('update');
        $result = $this->resource->refreshValue(
            'test',
            1,
            QuoteGrid::COMPANY_NAME,
            2
        );
        $this->assertEquals($this->resource, $result);
    }

    /**
     * DataProvider for refresh method.
     *
     * @return array
     */
    public function dataProviderTestRefresh()
    {
        return [
            [Area::AREA_ADMINHTML, 2, NegotiableQuoteInterface::STATUS_ORDERED],
            ['', 0, NegotiableQuoteInterface::STATUS_CREATED]
        ];
    }

    /**
     * Prepare resource mock.
     *
     * @return void
     */
    private function prepareResourcesMock()
    {
        $this->resources->expects($this->once())->method('getConnection')->willReturn($this->connection);
        $this->resources->expects($this->once())->method('getTableName')->willReturnArgument(1);
    }
}
