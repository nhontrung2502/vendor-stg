<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Order;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\StatusServiceInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Block\Order\OwnerFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OwnerFilterTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OwnerFilter|MockObject
     */
    private $ownerFilter;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var UserContextInterface|MockObject
     */
    private $customerContextMock;

    /**
     * @var Structure|MockObject
     */
    private $structureMock;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var StatusServiceInterface|MockObject
     */
    private $companyModuleConfig;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->customerContextMock = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserId'])
            ->getMockForAbstractClass();
        $this->structureMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllowedChildrenIds'])
            ->getMock();
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMockForAbstractClass();

        $this->companyModuleConfig = $this->getMockBuilder(StatusServiceInterface::class)
            ->setMethods(['isActive'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ownerFilter = $this->objectManagerHelper->getObject(
            OwnerFilter::class,
            [
                'customerContext' => $this->customerContextMock,
                'structure' => $this->structureMock,
                'authorization' => $this->authorization,
                'request' => $this->requestMock,
                'companyModuleConfig' => $this->companyModuleConfig
            ]
        );
    }

    /**
     * Test for isViewAll() method.
     *
     * @return void
     */
    public function testIsViewAllOrders()
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('created_by')
            ->willReturn('all');

        $this->assertTrue($this->ownerFilter->isViewAll());
    }

    /**
     * Test for canShow() method.
     *
     * @param bool $expected
     * @param bool $companyModuleIsActive
     * @param array $calls
     * @dataProvider canShowDataProvider
     * @return void
     */
    public function testCanShow($expected, $companyModuleIsActive, array $calls)
    {
        $subCustomers = [1,2];

        $customerId = 1;
        $this->customerContextMock->expects($this->any())
            ->method('getUserId')
            ->willReturn($customerId);

        $this->companyModuleConfig->expects($this->exactly(1))->method('isActive')->willReturn($companyModuleIsActive);

        $this->structureMock->expects($this->exactly($calls['structure_getAllowedChildrenIds']))
            ->method('getAllowedChildrenIds')
            ->with($customerId)
            ->willReturn($subCustomers);
        $this->authorization->expects($this->any())->method('isAllowed')->willReturn($expected);

        $this->assertEquals($expected, $this->ownerFilter->canShow());
    }

    /**
     * DataProvider for testAdminSend.
     *
     * @return array
     */
    public function canShowDataProvider()
    {
        return [
            [
                false, false, ['structure_getAllowedChildrenIds' => 0]
            ],
            [
                true, true, ['structure_getAllowedChildrenIds' => 1]
            ]
        ];
    }
}
