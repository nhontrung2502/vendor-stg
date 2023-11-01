<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Expired\Provider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Expired\Provider\ExpiredQuoteList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExpiredQuoteListTest extends TestCase
{
    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilder;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDate;

    /**
     * @var ExpiredQuoteList
     */
    private $expiredQuoteList;

    /**
     * Set up.
     * @return void
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteRepository = $this->getMockBuilder(
            NegotiableQuoteRepositoryInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(
            SearchCriteriaBuilder::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilder = $this->getMockBuilder(
            FilterBuilder::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->localeDate = $this->getMockBuilder(
            TimezoneInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->expiredQuoteList = $objectManager->getObject(
            ExpiredQuoteList::class,
            [
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'filterBuilder' => $this->filterBuilder,
                'localeDate' => $this->localeDate,
            ]
        );
    }

    /**
     * Test getExpiredQuotes() method.
     * @return void
     */
    public function testGetExpiredQuotes()
    {
        $date = $this->getMockBuilder(\DateTime::class)->disableOriginalConstructor()
            ->getMock();
        $date->expects($this->atLeastOnce())->method('format')->willReturn('2020-01-01');
        $this->localeDate->expects($this->once())->method('date')->willReturn($date);
        $this->filterBuilder->expects($this->atLeastOnce())->method('setField')->willReturnSelf();
        $this->filterBuilder->expects($this->atLeastOnce())->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->expects($this->atLeastOnce())->method('setValue')->willReturnSelf();
        $this->filterBuilder->expects($this->atLeastOnce())->method('setField')->willReturnSelf();
        $this->filterBuilder->expects($this->atLeastOnce())->method('create')->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(
            SearchCriteria::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->expects($this->atLeastOnce())->method('create')->willReturn($searchCriteria);
        $list = $this->getMockBuilder(
            SearchResultsInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteRepository->expects($this->atLeastOnce())->method('getList')->willReturn($list);
        $list->expects($this->atLeastOnce())->method('getItems')->willReturn(['items']);
        $this->assertNotEmpty($this->expiredQuoteList->getExpiredQuotes());
    }
}
