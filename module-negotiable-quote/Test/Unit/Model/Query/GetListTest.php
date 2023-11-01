<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Query;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\Query\GetList;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\Query\GetList class.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetListTest extends TestCase
{
    /**
     * @var JoinProcessorInterface|MockObject
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var SearchResultsFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessor;

    /**
     * @var GetList
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->extensionAttributesJoinProcessor = $this->getMockBuilder(
            JoinProcessorInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchResultsFactory = $this->getMockBuilder(SearchResultsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->restriction = $this->getMockBuilder(
            RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagement = $this->getMockBuilder(
            NegotiableQuoteManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionProcessor = $this->getMockBuilder(
            CollectionProcessorInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            GetList::class,
            [
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
                'searchResultsFactory' => $this->searchResultsFactory,
                'collectionFactory' => $this->collectionFactory,
                'restriction' => $this->restriction,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'collectionProcessor' => $this->collectionProcessor,
            ]
        );
    }

    /**
     * Test getList method.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResult = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $snapshot = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchResultsFactory->expects($this->once())->method('create')->willReturn($searchResult);
        $searchResult->expects($this->once())->method('setSearchCriteria')->with($searchCriteria)->willReturnSelf();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $this->extensionAttributesJoinProcessor->expects($this->once())->method('process')->with($collection);
        $collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with(
                'extension_attribute_negotiable_quote.is_regular_quote',
                ['eq' => 1]
            )
            ->willReturnSelf();
        $this->collectionProcessor->expects($this->once())->method('process')->with($searchCriteria, $collection);
        $collection->expects($this->once())->method('getItems')->willReturn([$item]);
        $this->restriction->expects($this->once())->method('setQuote')->with($item)->willReturnSelf();
        $this->restriction->expects($this->once())->method('isLockMessageDisplayed')->willReturn(true);
        $item->expects($this->once())->method('getId')->willReturn(1);
        $this->negotiableQuoteManagement->expects($this->once())->method('getSnapshotQuote')->willReturn($snapshot);
        $searchResult->expects($this->once())->method('setItems')->with([$snapshot])->willReturnSelf();
        $collection->expects($this->once())->method('getSize')->willReturn(1);
        $searchResult->expects($this->once())->method('setTotalCount')->with(1)->willReturnSelf();

        $this->assertSame($searchResult, $this->model->getList($searchCriteria, true));
    }

    /**
     * Test getListByCustomerId method.
     *
     * @return void
     */
    public function testGetListByCustomerId()
    {
        $customerId = 1;
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);
        $this->extensionAttributesJoinProcessor->expects($this->once())->method('process')->with($collection);
        $collection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->withConsecutive(
                [
                    'extension_attribute_negotiable_quote.is_regular_quote', ['eq' => 1]
                ],
                [
                    'main_table.customer_id', ['eq' => $customerId]
                ]
            )
            ->willReturnSelf();
        $collection->expects($this->once())->method('getItems')->willReturn([$quote]);

        $this->assertSame([$quote], $this->model->getListByCustomerId($customerId));
    }
}
