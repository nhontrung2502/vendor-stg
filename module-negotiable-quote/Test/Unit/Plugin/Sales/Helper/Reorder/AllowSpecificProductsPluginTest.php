<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Sales\Helper\Reorder;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Plugin\Sales\Helper\Reorder\AllowSpecificProductsPlugin;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for AllowSpecificProductsPlugin.
 */
class AllowSpecificProductsPluginTest extends TestCase
{
    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var AllowSpecificProductsPlugin
     */
    private $allowSpecificProductsPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->allowSpecificProductsPlugin = $objectManager->getObject(
            AllowSpecificProductsPlugin::class,
            [
                'companyContext' => $this->companyContext,
                'orderRepository' => $this->orderRepository,
            ]
        );
    }

    /**
     * Test aroundCanReorder method for customer without company.
     *
     * @return void
     */
    public function testAroundCanReorderForB2cUser()
    {
        $subject = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return true;
        };
        $this->companyContext->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(false);
        $this->orderRepository->expects($this->never())->method('get');

        $this->assertTrue(
            $this->allowSpecificProductsPlugin->aroundCanReorder($subject, $proceed, 1)
        );
    }

    /**
     * Test aroundCanReorder method for customer with company for order that can unhold.
     *
     * @return void
     */
    public function testAroundCanReorderForUnholdOrder()
    {
        $subject = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return true;
        };
        $this->companyContext->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(true);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->willReturn($order);
        $order->expects($this->once())->method('canUnhold')->willReturn(true);

        $this->assertFalse(
            $this->allowSpecificProductsPlugin->aroundCanReorder($subject, $proceed, 1)
        );
    }

    /**
     * Test aroundCanReorder method for customer with company for order that have no reorder flag.
     *
     * @return void
     */
    public function testAroundCanReorderWithActionFlag()
    {
        $subject = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return true;
        };
        $this->companyContext->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(true);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->willReturn($order);
        $order->expects($this->once())->method('canUnhold')->willReturn(false);
        $order->expects($this->once())->method('isPaymentReview')->willReturn(false);
        $order->expects($this->once())->method('getActionFlag')
            ->with(Order::ACTION_FLAG_REORDER)->willReturn(false);

        $this->assertFalse(
            $this->allowSpecificProductsPlugin->aroundCanReorder($subject, $proceed, 1)
        );
    }

    /**
     * Test aroundCanReorder method for customer with company for order that available for reorder.
     *
     * @return void
     */
    public function testAroundCanReorderWithAvailableForReorder()
    {
        $subject = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return true;
        };
        $this->companyContext->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(true);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->willReturn($order);
        $order->expects($this->once())->method('canUnhold')->willReturn(false);
        $order->expects($this->once())->method('isPaymentReview')->willReturn(false);
        $order->expects($this->once())->method('getActionFlag')
            ->with(Order::ACTION_FLAG_REORDER)->willReturn(true);
        $subject->expects($this->once())->method('isAllowed')
            ->willReturn(true);

        $this->assertTrue(
            $this->allowSpecificProductsPlugin->aroundCanReorder($subject, $proceed, 1)
        );
    }

    /**
     * Test aroundCanReorder method for customer with company for order that
     * is not available for reorder from config settings
     *
     * @return void
     */
    public function testAroundCanReorderWithFalseAllowReorder()
    {
        $subject = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return false;
        };
        $this->companyContext->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(true);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->willReturn($order);
        $order->expects($this->once())->method('canUnhold')->willReturn(false);
        $order->expects($this->once())->method('isPaymentReview')->willReturn(false);
        $order->expects($this->once())->method('getActionFlag')
            ->with(Order::ACTION_FLAG_REORDER)->willReturn(true);
        $subject->expects($this->once())->method('isAllowed')
            ->willReturn(false);
        $this->assertFalse(
            $this->allowSpecificProductsPlugin->aroundCanReorder($subject, $proceed, 1)
        );
    }
}
