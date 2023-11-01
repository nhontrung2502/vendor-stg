<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\View;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\CustomerGroup;
use Magento\NegotiableQuote\Model\PurgedContentFactory;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\NegotiableQuote\Block\Adminhtml\Quote\View\CustomerGroup class.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerGroupTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CustomerGroup
     */
    private $customerGroup;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagementMock;

    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private $groupRepository;

    /**
     * @var PurgedContentFactory|MockObject
     */
    private $purgedContentFactoryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $jsonSerializerMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->purgedContentFactoryMock = $this->getMockBuilder(PurgedContentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonSerializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteHelper = $this->getMockBuilder(\Magento\NegotiableQuote\Helper\Quote::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->customerGroup = $this->objectManagerHelper->getObject(
            CustomerGroup::class,
            [
                'companyManagement' => $this->companyManagementMock,
                'groupRepository' => $this->groupRepository,
                'purgedContentFactory' => $this->purgedContentFactoryMock,
                'serializer' => $this->jsonSerializerMock,
                'quote' => $this->quoteMock,
                'urlBuilder' => $this->urlBuilder,
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
            ]
        );
    }

    /**
     * Test getGroupName method.
     *
     * @return void
     */
    public function testGetGroupName()
    {
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->getMockForAbstractClass();
        $customerMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('getCustomer')
            ->willReturn($customerMock);
        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->getMockForAbstractClass();
        $this->companyManagementMock->expects($this->atLeastOnce())
            ->method('getByCustomerId')
            ->willReturn($companyMock);
        $companyMock->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn(1);

        $groupMock = $this->getMockBuilder(GroupInterface::class)
            ->getMockForAbstractClass();
        $this->groupRepository->expects($this->atLeastOnce())
            ->method('getById')
            ->willReturn($groupMock);

        $groupMock->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn('Name');

        $this->negotiableQuoteHelper->expects($this->atLeastOnce())
            ->method('resolveCurrentQuote')
            ->willReturn($this->quoteMock);

        $this->assertEquals(
            'Name',
            $this->customerGroup->getGroupName()
        );
    }

    /**
     * Test getGroupUrl method.
     *
     * @return void
     */
    public function testGetGroupUrl()
    {
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->getMockForAbstractClass();
        $customerMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('getCustomer')
            ->willReturn($customerMock);
        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->getMockForAbstractClass();
        $this->companyManagementMock->expects($this->atLeastOnce())
            ->method('getByCustomerId')
            ->willReturn($companyMock);
        $companyMock->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn(1);

        $groupMock = $this->getMockBuilder(GroupInterface::class)
            ->getMockForAbstractClass();
        $this->groupRepository->expects($this->atLeastOnce())
            ->method('getById')
            ->willReturn($groupMock);

        $groupMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->with('customer/group/edit', [GroupInterface::ID => 1])
            ->willReturnArgument(0);

        $this->negotiableQuoteHelper->expects($this->atLeastOnce())
            ->method('resolveCurrentQuote')
            ->willReturn($this->quoteMock);

        $this->assertEquals(
            'customer/group/edit',
            $this->customerGroup->getGroupUrl()
        );
    }
}
