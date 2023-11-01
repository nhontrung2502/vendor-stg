<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Ui\DataProvider;

use Magento\Company\Model\Company\Structure;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\NegotiableQuote\Ui\DataProvider\DataProvider as SystemUnderTest;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProviderTest extends TestCase
{
    /**
     * @var ObjectManager|MockObject
     */
    private $objectManagerHelper;

    /**
     * @var SystemUnderTest
     */
    private $quoteDataProvider;

    /**
     * @var  NegotiableQuoteRepository|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var  Structure|MockObject
     */
    private $structureMock;

    /**
     * @var SearchResultsInterface|MockObject
     */
    private $searchResult;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * setUp
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $className = SystemUnderTest::class;
        $arguments = $this->objectManagerHelper->getConstructArguments($className);

        $searchCriteriaBuilderMock = $arguments['searchCriteriaBuilder'];
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $searchCriteriaBuilderMock->expects($this->any())->method('create')->willReturn($searchCriteriaMock);

        $storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $website = $this->createMock(Website::class);
        $storeManager->expects($this->any())->method('getStore')->willReturn($storeManager);
        $website->expects($this->any())->method('getStoreIds')->willReturn(1);
        $storeManager->expects($this->any())->method('getWebsite')->willReturn($website);
        $arguments['storeManager'] = $storeManager;

        $filterBuilder = $arguments['filterBuilder'];
        $filter = $this->getMockForAbstractClass(
            Filter::class,
            [],
            '',
            false
        );
        $filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
        $filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
        $filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
        $filterBuilder->expects($this->any())->method('create')->willReturn($filter);

        $this->searchResult = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $this->negotiableQuoteRepository = $arguments['negotiableQuoteRepository'];
        $this->negotiableQuoteRepository->method('getList')->willReturn($this->searchResult);
        $this->structureMock = $arguments['structure'];
        $this->structureMock->method('getAllowedChildrenIds')
            ->willReturn([1, 2]);
        $this->requestMock = $arguments['request'];

        $this->quoteDataProvider = $this->objectManagerHelper->getObject(
            $className,
            $arguments
        );
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param array $items
     * @param array $attributes
     */
    public function testGetData($items, $attributes)
    {
        $this->requestMock->method('getParam')
            ->with('namespace')
            ->willReturn('namespace');

        $this->searchResult->expects($this->any())->method('getItems')->willReturn($items);
        $data = $this->quoteDataProvider->getData();

        foreach ($data['items'] as $item) {
            foreach (array_keys($attributes) as $key) {
                $this->assertArrayHasKey($key, $item);
            }
        }
    }

    /**
     * Data provider for getData
     *
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [$this->getItems(), ['quote_name' => 'name_1']]
        ];
    }

    /**
     * Test to retrieve empty data
     */
    public function testGetEmptyData()
    {
        $this->requestMock->method('getParam')
            ->with('namespace')
            ->willReturn(null);

        $this->searchResult->expects($this->never())->method('getItems');
    }

    /**
     * @return array
     */
    protected function getItems(): array
    {
        return [
            $this->initItem(['key' => 'value'], ['quote_name' => 'name_1']),
            $this->initItem(['key' => 'value'], ['quote_name' => 'name_1']),
            $this->initItem(['key' => 'value'], ['quote_name' => 'name_1']),
            $this->initItem(['key' => 'value'], ['quote_name' => 'name_1'])
        ];
    }

    /**
     * @param $data
     * @param $quoteData
     * @return object
     */
    protected function initItem($data, $quoteData)
    {
        $objectManagerHelper = new ObjectManager($this);
        $itemArguments = $objectManagerHelper->getConstructArguments(Quote::class);
        $itemArguments['data'] = $data;
        $item = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes'])
            ->getMock();

        $quoteNegotiation =
            $this->createPartialMock(NegotiableQuote::class, ['getData']);
        $quoteNegotiation->expects($this->any())->method('getData')->willReturn($quoteData);

        $extensionMock = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionMock->expects($this->any())->method('getNegotiableQuote')->willReturn($quoteNegotiation);

        $item->expects($this->any())->method('getExtensionAttributes')->willReturn($extensionMock);

        return $item;
    }
}
