<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Email\Provider;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Email\Provider\SalesRepresentative;
use Magento\Quote\Api\Data\CartInterface;
use Magento\User\Api\Data\UserInterface;
use Magento\User\Api\Data\UserInterfaceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SalesRepresentativeTest extends TestCase
{
    /**
     * @var UserInterfaceFactory|MockObject
     */
    private $userFactory;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var SalesRepresentative
     */
    private $salesRepresentative;

    /**
     * Set up.
     * @return void
     */
    protected function setUp(): void
    {
        $this->userFactory = $this->getMockBuilder(UserInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->salesRepresentative = $objectManager->getObject(
            SalesRepresentative::class,
            [
                'userFactory' => $this->userFactory,
                'companyManagement' => $this->companyManagement,
                'logger' => $this->logger,
            ]
        );
    }

    /**
     * Test getSalesRepresentativeForQuote().
     * @return void
     */
    public function testGetSalesRepresentativeForQuote()
    {
        $customer = $this->getMockBuilder(
            CustomerInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $quote = $this->getMockBuilder(
            CartInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $company = $this->getMockBuilder(
            CompanyInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getSalesRepresentativeId')->willReturn(1);
        $this->companyManagement->expects($this->atLeastOnce())->method('getByCustomerId')->willReturn($company);
        $user = $this->getMockBuilder(
            UserInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMockForAbstractClass();
        $user->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $this->userFactory->expects($this->atLeastOnce())->method('create')->willReturn($user);
        $this->salesRepresentative->getSalesRepresentativeForQuote($quote);
    }

    /**
     * Test getSalesRepresentativeForQuote() execution with exception thrown.
     * @return void
     */
    public function testGetSalesRepresentativeForQuoteWithException()
    {
        $customer = $this->getMockBuilder(
            CustomerInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $quote = $this->getMockBuilder(
            CartInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $company = $this->getMockBuilder(
            CompanyInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->never())->method('getSalesRepresentativeId');
        $this->companyManagement->expects($this->atLeastOnce())
            ->method('getByCustomerId')
            ->willThrowException(new NoSuchEntityException());
        $user = $this->getMockBuilder(
            UserInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMockForAbstractClass();
        $user->expects($this->never())->method('load');
        $this->userFactory->expects($this->never())->method('create');
        $this->assertNull($this->salesRepresentative->getSalesRepresentativeForQuote($quote));
    }
}
