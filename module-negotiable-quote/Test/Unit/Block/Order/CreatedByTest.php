<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Order;

use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Block\Order\CreatedBy;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreatedByTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CreatedBy|MockObject
     */
    private $createdBy;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerViewHelperMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->customerViewHelperMock =
            $this->getMockBuilder(CustomerNameGenerationInterface::class)
                ->disableOriginalConstructor()
                ->setMethods(['getCustomerName'])
                ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->createdBy = $this->objectManagerHelper->getObject(
            CreatedBy::class,
            [
                'customerRepository' => $this->customerRepositoryMock,
                'customerViewHelper' => $this->customerViewHelperMock
            ]
        );
    }

    /**
     * Test for getCreatedBy() method
     *
     * @return void
     */
    public function testGetCreatedBy()
    {
        $customerName = 'Peter Parker';
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMockForAbstractClass();
        $orderMock->expects($this->once())->method('getCustomerId')
            ->willReturn(1);
        $this->createdBy->setOrder($orderMock);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($customerMock);
        $this->customerViewHelperMock->expects($this->once())
            ->method('getCustomerName')
            ->willReturn($customerName);

        $this->assertEquals($customerName, $this->createdBy->getCreatedBy());
    }
}
