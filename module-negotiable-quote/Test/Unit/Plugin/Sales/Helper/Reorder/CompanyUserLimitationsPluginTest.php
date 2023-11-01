<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Sales\Helper\Reorder;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Plugin\Sales\Helper\Reorder\CompanyUserLimitationsPlugin;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Helper\Reorder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for company limitations plugin check.
 */
class CompanyUserLimitationsPluginTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var CompanyUserLimitationsPlugin
     */
    private $companyUserLimitationsPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCurrentUserCompanyUser'])
            ->getMockForAbstractClass();
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->companyUserLimitationsPlugin = $objectManager->getObject(
            CompanyUserLimitationsPlugin::class,
            [
                'userContext' => $this->userContext,
                'companyContext' => $this->companyContext,
                'orderRepository' => $this->orderRepository,
            ]
        );
    }

    /**
     * Test aroundCanReorder plugin method.
     *
     * @return void
     */
    public function testAroundCanReorder()
    {
        $orderId = 7;
        $customerId = 17;

        $this->companyContext->expects($this->once())
            ->method('isCurrentUserCompanyUser')
            ->willReturn(true);

        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMockForAbstractClass();
        $order->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($order);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($customerId);

        $subject = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return true;
        };

        $this->assertTrue($this->companyUserLimitationsPlugin->aroundCanReorder($subject, $proceed, $orderId));
    }

    /**
     * Test aroundCanReorder method for non company users.
     *
     * @return void
     */
    public function testAroundCanReorderForNonCompanyUser()
    {
        $orderId = 7;

        $this->companyContext->expects($this->once())
            ->method('isCurrentUserCompanyUser')
            ->willReturn(false);

        $subject = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return true;
        };

        $this->assertTrue($this->companyUserLimitationsPlugin->aroundCanReorder($subject, $proceed, $orderId));
    }
}
