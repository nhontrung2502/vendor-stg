<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Order\Info;

use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Block\Order\Info\CreationInfo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreationInfoTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CreationInfo|MockObject
     */
    private $creationInfo;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerViewHelperMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDateMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerViewHelperMock =
            $this->getMockBuilder(CustomerNameGenerationInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->localeDateMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['formatDateTime'])
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->creationInfo = $this->objectManagerHelper->getObject(
            CreationInfo::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'customerViewHelper' => $this->customerViewHelperMock,
                'request' => $this->requestMock,
                'localeDate' => $this->localeDateMock,
                'data' => []
            ]
        );
    }

    /**
     * Test for getCreationInfo() method
     *
     * @return void
     */
    public function testGetCreationInfo()
    {
        $createdAt = date('Y-m-d H:i:s');
        $customerName = 'Peter Parker';
        $result = $createdAt . ' (' . $customerName . ')';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(1);
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'getEntityId', 'getCreatedAt'])
            ->getMockForAbstractClass();
        $orderMock->expects($this->any())->method('getCustomerId')->willReturn(1);
        $orderMock->expects($this->any())->method('getEntityId')->willReturn(1);
        $orderMock->expects($this->any())->method('getCreatedAt')->willReturn($createdAt);
        $this->orderRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($orderMock);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($customerMock);
        $this->customerViewHelperMock->expects($this->once())
            ->method('getCustomerName')
            ->willReturn($customerName);
        $this->localeDateMock->expects($this->any())
            ->method('formatDateTime')
            ->willReturn($createdAt);

        $this->assertEquals($result, $this->creationInfo->getCreationInfo());
    }
}
