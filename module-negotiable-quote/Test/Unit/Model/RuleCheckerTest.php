<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Applier;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\RuleChecker;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\Rule\Customer;
use Magento\SalesRule\Model\Rule\CustomerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for rule checker model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleCheckerTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var RuleChecker
     */
    private $ruleChecker;

    /**
     * @var Quote|MockObject
     */
    private $quoteHelperMock;

    /**
     * @var CustomerFactory|MockObject
     */
    private $customerFactoryMock;

    /**
     * @var HistoryManagementInterface|MockObject
     */
    private $historyManagementMock;

    /**
     * @var RuleRepositoryInterface|MockObject
     */
    private $ruleRepositoryMock;

    /**
     * @var Applier|MockObject
     */
    private $messageApplierMock;

    /**
     * @var CartInterface|MockObject
     */
    private $quoteMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteHelperMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['formatPrice'])
            ->getMock();
        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->historyManagementMock = $this->getMockBuilder(HistoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addCustomLog'])
            ->getMockForAbstractClass();
        $this->ruleRepositoryMock = $this->getMockBuilder(RuleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageApplierMock = $this->getMockBuilder(Applier::class)
            ->disableOriginalConstructor()
            ->setMethods(['setIsDiscountRemovedLimit', 'setIsDiscountRemoved'])
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'getExtensionAttributes',
                'getCustomer'
            ])
            ->getMockForAbstractClass();
        $this->quoteMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);

        $this->objectManagerHelper = new ObjectManager($this);
        $this->ruleChecker = $this->objectManagerHelper->getObject(
            RuleChecker::class,
            [
                'quoteHelper' => $this->quoteHelperMock,
                'customerFactory' => $this->customerFactoryMock,
                'historyManagement' => $this->historyManagementMock,
                'ruleRepository' => $this->ruleRepositoryMock,
                'messageApplier' => $this->messageApplierMock
            ]
        );
    }

    /**
     * Test for checkIsDiscountRemoved() method.
     *
     * @dataProvider checkIsDiscountRemovedDataProvider
     * @param bool $isUsageLimitReached
     * @param bool $discountByPercent
     * @param int $formatPriceCalls
     * @return void
     */
    public function testCheckIsDiscountRemoved($isUsageLimitReached, $discountByPercent, $formatPriceCalls)
    {
        $getTimesUsed = 1;
        $getUsedPerCustomer = 2;
        $discountType = 'dummy';
        $oldRuleIds = '1,2';
        $newRuleIdsArray = '3';
        $result = true;

        if ($isUsageLimitReached) {
            $getTimesUsed = 100;
            $this->messageApplierMock->expects($this->once())->method('setIsDiscountRemovedLimit');
        } else {
            $this->messageApplierMock->expects($this->once())->method('setIsDiscountRemoved');
        }

        if ($discountByPercent) {
            $discountType = RuleInterface::DISCOUNT_ACTION_BY_PERCENT;
        }

        $negotiableQuoteMock = $this->getNegotiableQuoteMock();
        $negotiableQuoteMock->expects($this->atLeastOnce())->method('getAppliedRuleIds')->willReturn($newRuleIdsArray);
        $ruleMock = $this->getMockBuilder(RuleInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRuleId', 'getUsesPerCustomer', 'getSimpleAction', 'getDiscountAmount'])
            ->getMockForAbstractClass();
        $this->ruleRepositoryMock->expects($this->atLeastOnce())->method('getById')->willReturn($ruleMock);
        $ruleMock->expects($this->atLeastOnce())->method('getRuleId')->willReturn(1);
        $ruleMock->expects($this->atLeastOnce())->method('getUsesPerCustomer')->willReturn($getUsedPerCustomer);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $this->quoteMock->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customerMock);
        $customerMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $ruleCustomerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByCustomerRule', 'getId', 'getTimesUsed'])
            ->getMock();
        $this->customerFactoryMock->expects($this->atLeastOnce())->method('create')->willReturn($ruleCustomerMock);
        $ruleCustomerMock->expects($this->atLeastOnce())->method('loadByCustomerRule')->willReturnSelf();
        $ruleCustomerMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $ruleCustomerMock->expects($this->atLeastOnce())->method('getTimesUsed')->willReturn($getTimesUsed);
        $ruleMock->expects($this->atLeastOnce())->method('getSimpleAction')
            ->willReturn($discountType);
        $ruleMock->expects($this->atLeastOnce())->method('getDiscountAmount')->willReturn(10);
        $this->quoteHelperMock->expects($this->exactly($formatPriceCalls))->method('formatPrice')->willReturn('5');
        $this->historyManagementMock->expects($this->once())->method('addCustomLog');

        $this->assertEquals($result, $this->ruleChecker->checkIsDiscountRemoved($this->quoteMock, $oldRuleIds, true));
    }

    /**
     * Test for checkIsDiscountRemoved method with exception.
     *
     * @return void
     */
    public function testCheckIsDiscountRemovedWithException()
    {
        $oldRuleIds = '1,2';
        $newRuleIdsArray = '3';
        $negotiableQuoteMock = $this->getNegotiableQuoteMock();
        $negotiableQuoteMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($newRuleIdsArray);
        $this->ruleRepositoryMock->expects($this->once())->method('getById')
            ->willThrowException(new NoSuchEntityException());
        $this->messageApplierMock->expects($this->once())
            ->method('setIsDiscountRemoved')->with($this->quoteMock)->willReturnSelf();
        $this->historyManagementMock->expects($this->once())->method('addCustomLog')->with(
            1,
            [
                [
                    'field_title' => 'Quote Discount',
                    'field_id' => 'discount',
                    'values' => [['new_value' => 'Cart rule deleted.', 'field_id' => 'rule_remove']]
                ]
            ],
            false,
            true
        );
        $this->assertTrue($this->ruleChecker->checkIsDiscountRemoved($this->quoteMock, $oldRuleIds, true));
    }

    /**
     * Get negotiable quote mock.
     *
     * @return NegotiableQuoteInterface|MockObject
     */
    private function getNegotiableQuoteMock()
    {
        $quoteExtensionAttributesMock = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuoteMock = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAppliedRuleIds'])
            ->getMockForAbstractClass();
        $quoteExtensionAttributesMock->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuoteMock);
        $this->quoteMock->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($quoteExtensionAttributesMock);

        return $negotiableQuoteMock;
    }

    /**
     * Data provider for testCheckIsDiscountRemoved test.
     *
     * @return array
     */
    public function checkIsDiscountRemovedDataProvider()
    {
        return [
            [true, true, 0],
            [false, false, 1]
        ];
    }
}
