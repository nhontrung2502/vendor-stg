<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Comment;

use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Comment;
use Magento\NegotiableQuote\Model\Comment\SearchProvider;
use Magento\NegotiableQuote\Model\ResourceModel\Comment\Collection;
use Magento\NegotiableQuote\Model\ResourceModel\Comment\CollectionFactory as CommentCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\NegotiableQuote\Model\Comment\SearchProvider class
 */
class SearchProviderTest extends TestCase
{
    /**
     * @var SearchResultsFactory|MockObject
     */
    protected $searchResultsFactory;

    /**
     * @var CommentCollectionFactory|MockObject
     */
    protected $collectionFactory;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessorMock;

    /**
     * @var SearchProvider
     */
    private $searchProvider;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $comment = $this->getMockBuilder(Comment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $comment->expects($this->any())->method('load')->willReturnSelf();
        $comment->expects($this->any())->method('getId')->willReturn(14);
        $comment->expects($this->any())->method('delete')->willReturnSelf();

        $this->searchResultsFactory = $this->getMockBuilder(SearchResultsFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $searchResult = new SearchResults();
        $this->searchResultsFactory->expects($this->any())->method('create')->willReturn($searchResult);

        $this->collectionFactory =
            $this->getMockBuilder(\Magento\NegotiableQuote\Model\ResourceModel\Comment\CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();

        $this->collectionProcessorMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->searchProvider = $objectManager->getObject(
            SearchProvider::class,
            [
                'searchResultsFactory' => $this->searchResultsFactory,
                'collectionFactory' => $this->collectionFactory,
                'collectionProcessor' => $this->collectionProcessorMock,
            ]
        );
    }

    /**
     * Test for method \Magento\NegotiableQuote\Model\Comment\SearchProvider::getList
     * @dataProvider getParamsForModel
     *
     * @param $count
     * @param $expectedResult
     * @return void
     */
    public function testGetList($count, $expectedResult)
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory->expects($this->any())
            ->method('create')->willReturn($collection);
        $collection->expects($this->any())->method('getItems')->willReturn([]);
        $collection->expects($this->any())->method('getSize')->willReturn($count);

        $this->collectionProcessorMock->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $collection);

        $result = $this->searchProvider->getList($searchCriteria);
        $this->assertEquals($expectedResult, $result->getTotalCount());
    }

    /**
     * Data provider for method testGetList
     * @return array
     */
    public function getParamsForModel()
    {
        return [
            [0, 0],
            [1, 1]
        ];
    }
}
