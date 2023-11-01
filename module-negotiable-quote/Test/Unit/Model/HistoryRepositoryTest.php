<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\HistoryInterfaceFactory;
use Magento\NegotiableQuote\Model\HistoryRepository;
use Magento\NegotiableQuote\Model\ResourceModel\History;
use Magento\NegotiableQuote\Model\ResourceModel\History\Collection;
use Magento\NegotiableQuote\Model\ResourceModel\History\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryRepositoryTest extends TestCase
{
    /**
     * @var History|MockObject
     */
    private $historyResource;

    /**
     * @var HistoryInterfaceFactory|MockObject
     */
    private $historyFactory;

    /**
     * @var SearchResultsFactory|MockObject
     */
    private $searchResultsFactory;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var HistoryRepository
     */
    private $historyRepository;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessorMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->historyResource = $this->createMock(History::class);
        $this->historyFactory =
            $this->createPartialMock(HistoryInterfaceFactory::class, ['create']);
        $this->searchResultsFactory =
            $this->createPartialMock(SearchResultsFactory::class, ['create']);
        $searchResult = new SearchResults();
        $this->searchResultsFactory->expects($this->any())->method('create')->willReturn($searchResult);
        $this->collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->collectionProcessorMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->historyRepository = $objectManager->getObject(
            HistoryRepository::class,
            [
                'historyResource' => $this->historyResource,
                'historyFactory' => $this->historyFactory,
                'searchResultsFactory' => $this->searchResultsFactory,
                'collectionFactory' => $this->collectionFactory,
                'logger' => $this->logger,
                'collectionProcessor' => $this->collectionProcessorMock,
            ]
        );
    }

    /**
     * Test for method Save with empty ID.
     *
     * @return void
     */
    public function testSaveWithEmptyEntityId()
    {
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $this->assertNull($this->historyRepository->save($history));
    }

    /**
     * Test for method Save.
     *
     * @return void
     */
    public function testSave()
    {
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $history->expects($this->atLeastOnce())->method('getHistoryId')->willReturn(1);
        $this->assertEquals(1, $this->historyRepository->save($history));
    }

    /**
     * Test for method Save with Exception.
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function testSaveWithException()
    {
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $this->expectException(CouldNotSaveException::class);
        $this->historyResource->expects($this->once())->method('save')->willThrowException(new \Exception());
        $this->assertFalse($this->historyRepository->save($history));
    }

    /**
     * Test for method Get.
     *
     * @return void
     */
    public function testGet()
    {
        $id = 1;
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $history->expects($this->once())->method('load')->with($id)->willReturnSelf();
        $history->expects($this->atLeastOnce())->method('getHistoryId')->willReturn($id);
        $this->historyFactory->expects($this->once())->method('create')->willReturn($history);
        $this->assertEquals($this->historyRepository->get($id), $history);
    }

    /**
     * Test for method Get with Exception.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetWithException()
    {
        $id = 1;
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $history->expects($this->once())->method('load')->with($id)->willReturnSelf();
        $this->historyFactory->expects($this->once())->method('create')->willReturn($history);
        $this->expectException(NoSuchEntityException::class);
        $this->assertEquals($this->historyRepository->get($id), $history);
    }

    /**
     * Test for getList method.
     *
     * @dataProvider getParams
     * @param int $count
     * @param int $expectedResult
     * @return void
     */
    public function testGetList($count, $expectedResult)
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);

        $collection = $this->createMock(Collection::class);
        $this->collectionFactory->expects($this->any())
            ->method('create')->willReturn($collection);
        $collection->expects($this->any())->method('getItems')->willReturn([]);
        $collection->expects($this->any())->method('getSize')->willReturn($count);

        $this->collectionProcessorMock->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $collection);

        $result = $this->historyRepository->getList($searchCriteria);
        $this->assertEquals($expectedResult, $result->getTotalCount());
    }

    /**
     * Data provider for method testGetList.
     *
     * @return array
     */
    public function getParams()
    {
        return [
            [0, 0],
            [1, 1]
        ];
    }

    /**
     * Test for delete() method.
     *
     * @return void
     */
    public function testDelete()
    {
        /** @var \Magento\NegotiableQuote\Model\History|MockObject $history */
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $this->historyResource->expects($this->once())->method('delete');

        $this->assertTrue($this->historyRepository->delete($history));
    }

    /**
     * Test for delete() method with exception.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->expectExceptionMessage('Cannot delete history log with id 1');
        /** @var \Magento\NegotiableQuote\Model\History|MockObject $history */
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $history->expects($this->once())->method('getEntityId')->willReturn(1);
        $exception = new \Exception();
        $this->historyResource->expects($this->once())->method('delete')->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical');

        $this->historyRepository->delete($history);
    }

    /**
     * Test for deleteById() method.
     *
     * @return void
     */
    public function testDeleteById()
    {
        $id = 1;
        $history = $this->createMock(\Magento\NegotiableQuote\Model\History::class);
        $history->expects($this->once())->method('load')->with($id)->willReturnSelf();
        $history->expects($this->atLeastOnce())->method('getHistoryId')->willReturn($id);
        $this->historyFactory->expects($this->once())->method('create')->willReturn($history);
        $this->historyResource->expects($this->once())->method('delete');

        $this->assertTrue($this->historyRepository->deleteById($id));
    }
}
