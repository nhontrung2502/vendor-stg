<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Restriction;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Model\Restriction\Customer;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Customer|MockObject
     */
    private $customer;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    /**
     * @var Structure|MockObject
     */
    private $structureMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userContextMock = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserId'])
            ->getMockForAbstractClass();
        $this->structureMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllowedChildrenIds'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->customer = $this->objectManagerHelper->getObject(
            Customer::class,
            [
                'userContext' => $this->userContextMock,
                'structure' => $this->structureMock
            ]
        );
    }

    /**
     * Test for isOwner() method
     *
     * @return void
     */
    public function testIsOwner()
    {
        $customerId = 1;
        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);
        $quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer'])
            ->getMockForAbstractClass();
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $customerMock->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);
        $quoteMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customerMock);

        $this->customer->setQuote($quoteMock);
        $this->assertTrue($this->customer->isOwner());
    }

    /**
     * Test for isOwner() method
     *
     * @return void
     */
    public function testIsSubUserContent()
    {
        $customerId = 1;
        $this->userContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);
        $quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer'])
            ->getMockForAbstractClass();
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $customerMock->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);
        $quoteMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customerMock);

        $this->customer->setQuote($quoteMock);
        $this->structureMock->expects($this->any())
            ->method('getAllowedChildrenIds')
            ->willReturn([
                1,
                2
            ]);

        $this->assertTrue($this->customer->isSubUserContent());
    }
}
