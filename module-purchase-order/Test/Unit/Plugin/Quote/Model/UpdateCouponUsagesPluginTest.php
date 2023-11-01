<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test\Unit\Plugin\Quote\Model;

use Magento\Quote\Model\Quote;
use Magento\PurchaseOrder\Plugin\Quote\Model\UpdateCouponUsagesPlugin;
use Magento\SalesRule\Model\Coupon\Quote\UpdateCouponUsages;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UpdateCouponUsagesPlugin.
 */
class UpdateCouponUsagesPluginTest extends TestCase
{
    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var PurchaseOrderRepositoryInterface|MockObject
     */
    private $purchaseOrderRepository;

    /**
     * @var PurchaseOrderInterface|MockObject
     */
    private $purchaseOrder;

    /**
     * @var RuleRepositoryInterface|MockObject
     */
    private $ruleRepository;

    /**
     * @var UpdateCouponUsages|MockObject
     */
    private $updateCouponUsages;

    /**
     * @var UpdateCouponUsagesPlugin
     */
    private $updateCouponUsagesPlugin;

    /**
     * @var RuleInterface|MockObject
     */
    private $rule;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAppliedRuleIds', 'setAppliedRuleIds'])
            ->getMock();

        $this->purchaseOrder = $this->getMockBuilder(PurchaseOrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->purchaseOrderRepository = $this->getMockBuilder(PurchaseOrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ruleRepository = $this->getMockBuilder(RuleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rule = $this->getMockBuilder(RuleInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateCouponUsages = $this->getMockBuilder(UpdateCouponUsages::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->updateCouponUsagesPlugin = $objectManager->getObject(
            UpdateCouponUsagesPlugin::class,
            [
                'purchaseOrderRepository' => $this->purchaseOrderRepository,
                'ruleRepository' => $this->ruleRepository
            ]
        );
    }

    /**
     * Test for beforeExecute method.
     *
     * @return void
     */
    public function testBeforeExecute(): void
    {
        $appliedRuleIds = '10,12';
        $this->purchaseOrder->expects($this->once())->method('getEntityId')->willReturn(1);
        $this->purchaseOrderRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->purchaseOrder);
        $this->quote->expects($this->atLeastOnce())->method('getAppliedRuleIds')->willReturn($appliedRuleIds);
        $this->quote->expects($this->atLeastOnce())
            ->method('setAppliedRuleIds')
            ->with($appliedRuleIds)
            ->willReturnSelf();
        $this->ruleRepository->expects($this->atLeastOnce())->method('getById')->willReturn($this->rule);
        $this->rule->method('getRuleId')
            ->willReturnOnConsecutiveCalls(10, 12);
        $this->updateCouponUsagesPlugin->beforeExecute($this->updateCouponUsages, $this->quote, true);
    }
}
