<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface as UserContext;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface as NegotiableQuoteRepository;
use Magento\NegotiableQuote\Model\NegotiableQuoteTaxRecalculate;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuotePrice\ScheduleBulk;
use Magento\Quote\Api\Data\CartInterface as Cart;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test of NegotiableQuoteTaxRecalculate model.
 */
class NegotiableQuoteTaxRecalculateTest extends TestCase
{
    /**
     * @var NegotiableQuoteRepository|MockObject
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
     * @var UserContext|MockObject
     */
    private $userContext;

    /**
     * @var ScheduleBulk|MockObject
     */
    private $scheduleBulk;

    /**
     * @var Cart|MockObject
     */
    private $quote;

    /**
     * @var NegotiableQuoteTaxRecalculate
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository = $this->getMockBuilder(NegotiableQuoteRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['save', 'getList'])->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'addSortOrder', 'create'])->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContext = $this->getMockBuilder(UserContext::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scheduleBulk = $this->getMockBuilder(ScheduleBulk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            NegotiableQuoteTaxRecalculate::class,
            [
                'userContext' => $this->userContext,
                'scheduleBulk' => $this->scheduleBulk,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'filterBuilder' => $this->filterBuilder,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder
            ]
        );
    }

    /**
     * Test for RecalculateTax method.
     */
    public function testRecalculateTax()
    {
        $this->filterBuilder->expects($this->once())->method('setField')
            ->with('extension_attribute_negotiable_quote.status')->willReturnSelf();
        $this->filterBuilder->expects($this->once())->method('setConditionType')->with('nin')->willReturnSelf();
        $this->filterBuilder->expects($this->once())->method('setValue')->willReturnSelf();

        $filter = $this->createMock(Filter::class);
        $this->filterBuilder->expects($this->atLeastOnce())->method('create')->willReturn($filter);

        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);

        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')->with($filter)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('addSortOrder')->with('entity_id', 'DESC')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $this->negotiableQuoteRepository->expects($this->once())->method('getList')->with($searchCriteria)
            ->willReturn($searchResults);

        $quoteItems = [$this->quote];
        $searchResults->expects($this->once())->method('getItems')->willReturn($quoteItems);

        $userId = 23;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);

        $this->scheduleBulk->expects($this->once())->method('execute')->with($quoteItems, $userId);

        $this->model->recalculateTax(true);
    }
}
