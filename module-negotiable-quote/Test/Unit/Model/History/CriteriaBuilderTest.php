<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\History;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\History\CriteriaBuilder;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CriteriaBuilderTest extends TestCase
{
    /**
     * @var CriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilder;

    /**
     * @var SortOrderBuilder|MockObject
     */
    private $sortOrderBuilder;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->filterBuilder = $this->createMock(FilterBuilder::class);
        $this->sortOrderBuilder = $this->createMock(SortOrderBuilder::class);

        $objectManager = new ObjectManager($this);
        $this->criteriaBuilder = $objectManager->getObject(
            CriteriaBuilder::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'filterBuilder' => $this->filterBuilder,
                'sortOrderBuilder' => $this->sortOrderBuilder,
            ]
        );
    }

    /**
     * Test for getQuoteHistoryCriteria() method
     *
     * @return void
     */
    public function testGetQuoteHistoryCriteria()
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);

        $this->sortOrderBuilder->expects($this->any())->method('setField')->willReturnSelf();
        $this->sortOrderBuilder->expects($this->any())->method('setDirection')->willReturnSelf();
        $this->sortOrderBuilder->expects($this->any())->method('create')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('create')->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->any())->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())->method('addSortOrder')->willReturnSelf();
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);

        $this->assertEquals($searchCriteria, $this->criteriaBuilder->getQuoteHistoryCriteria(1));
    }

    /**
     * Test for getSystemHistoryCriteria() method
     *
     * @return void
     */
    public function testGetSystemHistoryCriteria()
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);

        $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('create')->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->any())->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);

        $this->assertEquals($searchCriteria, $this->criteriaBuilder->getSystemHistoryCriteria(1));
    }

    /**
     * Test for getQuoteSearchCriteria() method.
     *
     * @return void
     */
    public function testGetQuoteSearchCriteria()
    {
        $quoteId = 1;
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->filterBuilder->expects($this->once())->method('setField')
            ->with('main_table.' . CartInterface::KEY_ENTITY_ID)->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->expects($this->once())->method('setValue')
            ->with($quoteId)->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('create')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->any())->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);

        $this->assertEquals($searchCriteria, $this->criteriaBuilder->getQuoteSearchCriteria(1));
    }
}
