<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\User\Model;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Plugin\User\Model\User;
use Magento\NegotiableQuote\Model\Purged\Extractor;
use Magento\NegotiableQuote\Model\Purged\Handler;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid;
use Magento\User\Api\Data\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserTest extends TestCase
{
    /**
     * @var QuoteGrid|MockObject
     */
    private $quoteGrid;

    /**
     * @var Extractor|MockObject
     */
    private $extractor;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var Customer|MockObject
     */
    private $customerResource;

    /**
     * @var Handler|MockObject
     */
    private $purgedContentsHandler;

    /**
     * @var UserInterface|MockObject
     */
    private $userMock;

    /**
     * @var User
     */
    private $user;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteGrid = $this
            ->createMock(QuoteGrid::class);

        $this->extractor = $this->getMockBuilder(Extractor::class)
            ->setMethods(['extractUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods([
                'addFilter',
                'create'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerResource = $this->getMockBuilder(Customer::class)
            ->setMethods(['getCustomerIdsByCompanyId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->purgedContentsHandler = $this->getMockBuilder(Handler::class)
            ->setMethods(['process'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->userMock = $this->getMockBuilder(UserInterface::class)
            ->setMethods([
                'getId',
                'hasDataChanges',
                'dataHasChangedFor'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->user = $objectManager->getObject(
            User::class,
            [
                'quoteGrid' => $this->quoteGrid,
                'extractor' => $this->extractor,
                'companyRepository' => $this->companyRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'customerResource' => $this->customerResource,
                'purgedContentsHandler' => $this->purgedContentsHandler
            ]
        );
    }

    /**
     * Test aroundSave() method.
     *
     * @param int $userId
     * @param bool $hasChanges
     * @param bool $hasChangesName
     * @param $expectCall
     * @return void
     * @dataProvider aroundSaveDataProvider
     */
    public function testAroundSave(
        $userId,
        $hasChanges,
        $hasChangesName,
        $expectCall
    ) {
        $user = $this->userMock;
        $closure = function () use ($user) {
            return $user;
        };
        $user->expects($this->any())->method('getId')->willReturn($userId);
        $user->expects($this->any())->method('hasDataChanges')->willReturn($hasChanges);
        if ($hasChanges) {
            $user->expects($this->any())->method('dataHasChangedFor')->willReturn($hasChangesName);
        }
        $this->quoteGrid->expects($expectCall)->method('refreshValue');
        $this->assertInstanceOf(UserInterface::class, $this->user->aroundSave($user, $closure));
    }

    /**
     * Data provider for aroundSave() method.
     *
     * @return array
     */
    public function aroundSaveDataProvider()
    {
        return [
            [0, false, false, $this->never()],
            [1, false, false, $this->never()],
            [1, true, false, $this->never()],
            [1, true, true, $this->once()]
        ];
    }

    /**
     * Test beforeDelete method.
     *
     * @return void
     */
    public function testBeforeDelete()
    {
        $this->userMock->expects($this->once())->method('getId')->willReturn(27);

        $associatedCustomerData = [1,2,3];
        $this->extractor->expects($this->once())->method('extractUser')->willReturn($associatedCustomerData);

        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
            ->willReturn($this->searchCriteriaBuilder);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getId')->willReturn(35);
        $companyList = [$company];

        $searchResults = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())->method('getItems')->willReturn($companyList);

        $this->companyRepository->expects($this->once())->method('getList')->willReturn($searchResults);

        $customerIds = [36];
        $this->customerResource->expects($this->once())->method('getCustomerIdsByCompanyId')->willReturn($customerIds);

        $this->purgedContentsHandler->expects($this->once())->method('process')->willReturn(null);

        $this->assertNull($this->user->beforeDelete($this->userMock));
    }
}
