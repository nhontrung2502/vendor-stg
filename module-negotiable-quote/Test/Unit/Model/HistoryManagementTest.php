<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\HistoryInterface;
use Magento\NegotiableQuote\Api\Data\HistoryInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\History;
use Magento\NegotiableQuote\Model\History\CriteriaBuilder;
use Magento\NegotiableQuote\Model\History\SnapshotManagement;
use Magento\NegotiableQuote\Model\HistoryManagement;
use Magento\NegotiableQuote\Model\HistoryRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * HistoryManagementTest tests \Magento\NegotiableQuote\Model\HistoryManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryManagementTest extends TestCase
{
    /**
     * @var HistoryInterfaceFactory|MockObject
     */
    private $historyFactory;

    /**
     * @var HistoryRepositoryInterface|MockObject
     */
    private $historyRepository;

    /**
     * @var SnapshotManagement|MockObject
     */
    private $snapshotManagement;

    /**
     * @var CriteriaBuilder|MockObject
     */
    private $criteriaBuilder;

    /**
     * @var HistoryManagement
     */
    private $historyManagement;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var int|null
     */
    private $quoteId;

    /**
     * @var History|MockObject
     */
    private $historyItem;

    /**
     * @var SearchResultsInterface|MockObject
     */
    private $searchResults;

    /**
     * @var SearchCriteriaInterface|MockObject
     */
    private $searchCriteria;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    /**
     * Set up.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->historyRepository = $this
            ->getMockBuilder(HistoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->historyFactory = $this->getMockBuilder(HistoryInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->snapshotManagement = $this
            ->getMockBuilder(SnapshotManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->criteriaBuilder = $this->getMockBuilder(CriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();
        $this->serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $this->serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return $value ? json_decode($value, true) : $value;
                }
            );

        $objectManager = new ObjectManager($this);
        $this->historyManagement = $objectManager->getObject(
            HistoryManagement::class,
            [
                'historyRepository' => $this->historyRepository,
                'historyFactory' => $this->historyFactory,
                'snapshotManagement' => $this->snapshotManagement,
                'criteriaBuilder' => $this->criteriaBuilder,
                'serializer' => $this->serializerMock
            ]
        );

        $this->quoteId = 1;
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->any())->method('getId')->willReturn($this->quoteId);
        $this->snapshotManagement->expects($this->any())->method('getQuote')->with(1)->willReturn($this->quote);
        $quoteExtension = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($quoteExtension);
        $quoteExtension->expects($this->any())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->any())->method('getStatus')->willReturn('quote_status');
        $historyLog = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();
        $this->historyFactory->expects($this->any())->method('create')->willReturn($historyLog);
        $this->searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->criteriaBuilder->expects($this->any())->method('getQuoteHistoryCriteria')
            ->willReturn($this->searchCriteria);
        $this->criteriaBuilder->expects($this->any())
            ->method('getQuoteSearchCriteria')
            ->willReturn($this->searchCriteria);
        $this->criteriaBuilder->expects($this->any())
            ->method('getSystemHistoryCriteria')
            ->willReturn($this->searchCriteria);
        $this->historyItem = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLogData', 'getSnapshotData', 'getStatus', 'setIsDraft', 'setStatus', 'setLogData'])
            ->getMock();
        $this->searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchResults->expects($this->any())->method('getItems')->willReturn([$this->historyItem]);
        $this->historyRepository->expects($this->any())->method('getList')->willReturn($this->searchResults);
    }

    /**
     * Test for createLog() method.
     *
     * @return void
     */
    public function testCreateLog()
    {
        $this->snapshotManagement->expects($this->any())
            ->method('collectSnapshotDataForNewQuote')->willReturn(['snapshot_data']);
        $this->snapshotManagement->expects($this->any())
            ->method('prepareCommentData')->willReturn(['comment_data', 'system_data' => true]);
        $this->snapshotManagement->expects($this->any())
            ->method('checkForSystemLogs')->willReturn(['system_data' => []]);
        $this->snapshotManagement->expects($this->any())
            ->method('getQuoteForRemovedItem')
            ->with($this->searchCriteria)
            ->willReturn($this->quote);

        $this->historyManagement->createLog($this->quoteId);
    }

    /**
     * Test for createLog() method with exception.
     *
     * @return void
     */
    public function testCreateLogWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->snapshotManagement->expects($this->any())
            ->method('collectSnapshotDataForNewQuote')->willReturn(['snapshot_data']);
        $this->snapshotManagement->expects($this->any())
            ->method('prepareCommentData')->willReturn(['comment_data', 'system_data' => true]);
        $this->snapshotManagement->expects($this->any())
            ->method('checkForSystemLogs')->willReturn(['system_data' => []]);
        $this->snapshotManagement->expects($this->any())
            ->method('getQuoteForRemovedItem')
            ->with($this->searchCriteria)
            ->willReturn($this->quote);

        $this->historyRepository->expects($this->any())->method('save')->willThrowException(
            new \Exception()
        );
        $this->historyManagement->createLog($this->quoteId);
    }

    /**
     * Test for updateLog() method.
     *
     * @return void
     */
    public function testUpdateLog()
    {
        $this->snapshotManagement->expects($this->once())
            ->method('collectSnapshotData')->willReturn(['snapshot_data']);
        $this->snapshotManagement->expects($this->once())
            ->method('getSnapshotsDiff')->willReturn(['snapshot_diff']);

        $this->historyManagement->updateLog($this->quoteId);
    }

    /**
     * Test for closeLog() method.
     *
     * @return void
     */
    public function testCloseLog()
    {
        $this->historyManagement->closeLog($this->quoteId);
    }

    /**
     * Test for updateStatusLog() method.
     *
     * @param string $snapshotData
     * @return void
     * @dataProvider dataProviderUpdateStatusLog
     */
    public function testUpdateStatusLog($snapshotData)
    {
        $this->historyItem->expects($this->any())
            ->method('getSnapshotData')->willReturn($snapshotData);

        $this->historyManagement->updateStatusLog($this->quoteId);
    }

    /**
     * Test for addCustomLog() method.
     *
     * @param string $logStatus
     * @param array $logData
     * @param array $newData
     * @param array $expectLogResult
     * @return void
     * @dataProvider dataProviderAddCustomLog
     */
    public function testAddCustomLog($logStatus, array $logData, array $newData, array $expectLogResult)
    {
        $this->historyItem->expects($this->any())->method('getStatus')->willReturn($logStatus);
        $this->historyItem->expects($this->any())->method('getLogData')->willReturn(json_encode($logData));
        $this->historyItem->expects($this->any())->method('getSnapshotData')->willReturn(json_encode($logData));
        $this->historyItem->expects($this->once())->method('setLogData')->with(json_encode($expectLogResult));

        $this->historyRepository->expects($this->once())->method('save')->with($this->historyItem);

        $this->historyManagement->addCustomLog($this->quoteId, $newData);
    }

    /**
     * Test getLogUpdatesList method.
     *
     * @return void
     */
    public function testGetLogUpdatesList()
    {
        $logId = 1;
        $history = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLogData'])
            ->getMock();
        $logData = json_encode([0 => 'test']);

        $history->expects($this->atLeastOnce())->method('getLogData')->willReturn($logData);
        $this->historyRepository->expects($this->atLeastOnce())->method('get')->willReturn($history);
        $this->assertEquals(['test'], $this->historyManagement->getLogUpdatesList($logId));
    }

    /**
     * Test getLogUpdatesList method with empty result.
     *
     * @return void
     */
    public function testGetLogUpdatesListWithEmptyResult()
    {
        $this->assertEquals([], $this->historyManagement->getLogUpdatesList(null));
    }

    /**
     * Test for method updateDraftLogs.
     *
     * @return void
     */
    public function testUpdateDraftLogs()
    {
        $this->historyItem->expects($this->any())->method('setIsDraft')->willReturnSelf();
        $this->historyRepository->expects($this->any())
            ->method('save')->with($this->historyItem)->willReturn($this->historyItem);
        $this->historyManagement->updateDraftLogs($this->quoteId);
        $this->historyManagement->updateDraftLogs($this->quoteId, true);
    }

    /**
     * Test for method updateSystemLogsStatus.
     *
     * @return void
     */
    public function testUpdateSystemLogsStatus()
    {
        $this->searchResults->expects($this->once())->method('getTotalCount')->willReturn(1);
        $this->historyManagement->updateSystemLogsStatus($this->quoteId);
    }

    /**
     * Data provider updateStatusLog.
     *
     * @return array
     */
    public function dataProviderUpdateStatusLog()
    {
        return [
            [json_encode(['status' => 'updated_by_system'])],
            ['{}'],
            [null],
        ];
    }

    /**
     * Data provider addCustomLog.
     *
     * @return array
     */
    public function dataProviderAddCustomLog()
    {
        return [
            [
                HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
                [
                    'status' => HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
                    'custom_log' => [
                        [
                            'product_sku' => 'sample_sku',
                            'values' => [
                                'key1' => 'value1',
                            ],
                        ],
                        [
                            'product_sku' => 'sample_sku',
                            'values' => [
                                'key2' => 'value2',
                            ],
                        ],
                    ],
                ],
                ['sample_value'],
                [
                    'custom_log' => [
                        [
                            'product_sku' => 'sample_sku',
                            'values' => [
                                'key1' => 'value1',
                            ],
                        ],
                        [
                            'product_sku' => 'sample_sku',
                            'values' => [
                                'key2' => 'value2',
                            ],
                        ],
                        'sample_value',
                    ],
                    'status' => HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
                ]
            ],
            [
                HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
                ['status' => HistoryInterface::STATUS_UPDATED_BY_SYSTEM],
                [],
                ['status' => HistoryInterface::STATUS_UPDATED_BY_SYSTEM]
            ],
            [
                HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
                [
                    'status' => HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
                    'custom_log' => [
                        [
                            'product_sku' => 'sample_sku',
                            'field_id' => 'product_sku',
                            'values' => [
                                [
                                    'field_id' => 'cart_price',
                                    'old_value' => 100,
                                    'new_value' => 90
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'product_sku' => 'sample_sku',
                        'field_id' => 'product_sku',
                        'values' => [
                            [
                                'field_id' => 'cart_price',
                                'old_value' => 90,
                                'new_value' => 85
                            ],
                        ],
                    ],
                ],
                [
                    'custom_log' => [
                        [
                            'product_sku' => 'sample_sku',
                            'field_id' => 'product_sku',
                            'values' => [
                                [
                                    'field_id' => 'cart_price',
                                    'old_value' => 100,
                                    'new_value' => 85
                                ],
                            ],
                        ],
                    ],
                    'status' => HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
                ]
            ],
        ];
    }

    /**
     * Test for addCustomLog() method with exeption.
     */
    public function testAddCustomLogWithExeption()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $logData = [
            'status' => HistoryInterface::STATUS_UPDATED_BY_SYSTEM,
            'custom_log' => [
                [
                    'product_sku' => 'sample_sku',
                    'values' => [
                        'key1' => 'value1',
                    ],
                ],
            ],
        ];
        $this->historyItem->expects($this->any())->method('getStatus')
            ->willReturn(HistoryInterface::STATUS_UPDATED_BY_SYSTEM);
        $this->historyItem->expects($this->any())->method('getLogData')->willReturn(json_encode($logData));
        $this->historyItem->expects($this->any())->method('getSnapshotData')->willReturn(json_encode($logData));

        $this->historyRepository->expects($this->once())->method('save')
            ->with($this->historyItem)->willThrowException(new \Exception());

        $this->historyManagement->addCustomLog($this->quoteId, ['sample_value']);
    }
}
