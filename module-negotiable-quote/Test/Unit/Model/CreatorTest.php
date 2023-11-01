<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\Integration;
use Magento\NegotiableQuote\Model\Creator;
use Magento\NegotiableQuote\Model\Purged\Provider;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Creator.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatorTest extends TestCase
{
    /**
     * @var User|MockObject
     */
    private $userResource;

    /**
     * @var UserInterfaceFactory|MockObject
     */
    private $userFactory;

    /**
     * @var IntegrationServiceInterface|MockObject
     */
    private $integration;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerNameGeneration;

    /**
     * @var Provider|MockObject
     */
    private $provider;

    /**
     * @var Creator
     */
    private $creator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userResource = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userFactory = $this->getMockBuilder(UserInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->integration = $this->getMockBuilder(IntegrationServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerNameGeneration = $this->getMockBuilder(CustomerNameGenerationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->provider = $this->getMockBuilder(Provider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->creator = $objectManagerHelper->getObject(
            Creator::class,
            [
                'userResource' => $this->userResource,
                'userFactory' => $this->userFactory,
                'integration' => $this->integration,
                'customerRepository' => $this->customerRepository,
                'customerNameGeneration' => $this->customerNameGeneration,
                'provider' => $this->provider
            ]
        );
    }

    /**
     * Test for retrieveCreatorName() for admin user type.
     *
     * @return void
     */
    public function testRetrieveCreatorNameUserTypeAdmin()
    {
        $type = UserContextInterface::USER_TYPE_ADMIN;
        $id = 1;
        $firstName = 'First';
        $lastName = 'Last';
        $user = $this->getMockBuilder(\Magento\User\Model\User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->atLeastOnce())->method('getFirstName')->willReturn($firstName);
        $user->expects($this->atLeastOnce())->method('getLastName')->willReturn($lastName);
        $this->userFactory->expects($this->atLeastOnce())->method('create')->willReturn($user);
        $this->userResource->expects($this->atLeastOnce())->method('load')->with($user, $id)->willReturn($user);
        $name = $firstName . ' ' . $lastName;

        $this->assertEquals($name, $this->creator->retrieveCreatorName($type, $id));
    }

    /**
     * Test for retrieveCreatorName() for admin user type with NoSuchEntityException.
     *
     * @return void
     */
    public function testRetrieveCreatorNameUserTypeAdminWithNoSuchEntityException()
    {
        $type = UserContextInterface::USER_TYPE_ADMIN;
        $id = 1;
        $quoteId = 1;
        $name = 'Peter Parker';
        $this->userFactory->expects($this->atLeastOnce())->method('create')
            ->willThrowException(new NoSuchEntityException());
        $this->provider->expects($this->once())->method('getSalesRepresentativeName')->with($quoteId)
            ->willReturn($name);

        $this->assertEquals($name, $this->creator->retrieveCreatorName($type, $id, $quoteId));
    }

    /**
     * Test for retrieveCreatorName() for integration user type.
     *
     * @return void
     */
    public function testRetrieveCreatorNameUserTypeIntegration()
    {
        $type = UserContextInterface::USER_TYPE_INTEGRATION;
        $id = 1;
        $name = 'Name';
        $integration = $this->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName'])
            ->getMock();
        $integration->expects($this->atLeastOnce())->method('getName')->willReturn($name);
        $this->integration->expects($this->atLeastOnce())->method('get')->with($id)->willReturn($integration);

        $this->assertEquals($name, $this->creator->retrieveCreatorName($type, $id));
    }

    /**
     * Test for retrieveCreatorName() for customer user type.
     *
     * @return void
     */
    public function testRetrieveCreatorNameUserTypeCustomer()
    {
        $type = UserContextInterface::USER_TYPE_CUSTOMER;
        $id = 1;
        $name = 'Name';
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository->expects($this->once())->method('getById')->with($id)->willReturn($customerMock);
        $this->customerNameGeneration->expects($this->once())->method('getCustomerName')->with($customerMock)
            ->willReturn($name);

        $this->assertEquals($name, $this->creator->retrieveCreatorName($type, $id));
    }

    /**
     * Test for retrieveCreatorName() for customer user type with NoSuchEntityException.
     *
     * @return void
     */
    public function testRetrieveCreatorNameUserTypeCustomerWithNoSuchEntityException()
    {
        $type = UserContextInterface::USER_TYPE_CUSTOMER;
        $id = 1;
        $quoteId = 1;
        $name = 'Name';
        $this->customerRepository->expects($this->once())->method('getById')->with($id)
            ->willThrowException(new NoSuchEntityException());
        $this->provider->expects($this->once())->method('getCustomerName')->with($quoteId)
            ->willReturn($name);

        $this->assertEquals($name, $this->creator->retrieveCreatorName($type, $id, $quoteId));
    }

    /**
     * Test for retrieveCreatorName() for customer user type is not supported.
     *
     * @return void
     */
    public function testRetrieveCreatorNameUserTypeNotSupported()
    {
        $type = 'dummy type';
        $id = 1;
        $name = '';

        $this->assertEquals($name, $this->creator->retrieveCreatorName($type, $id));
    }
}
