<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Test\Unit\Plugin\Quote\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\PurchaseOrder\LogManagementInterface;
use Magento\PurchaseOrder\Plugin\Quote\Model\QuoteManagementPlugin;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QuoteManagementPlugin.
 */
class QuoteManagementPluginTest extends TestCase
{
    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var QuoteManagementPlugin
     */
    private $quoteManagementPlugin;

    /**
     * @var PurchaseOrderRepositoryInterface|MockObject
     */
    private $purchaseOrderRepository;

    /**
     * @var LogManagementInterface|MockObject
     */
    private $purchaseOrderLogManagement;

    /**
     * @var QuoteManagement|MockObject
     */
    private $quoteManagement;

    /**
     * @var PurchaseOrderInterface|MockObject
     */
    private $purchaseOrder;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var CartExtensionInterface|MockObject
     */
    private $quoteExtensionAttributes;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuote;

    /**
     * @var History|MockObject
     */
    private $negotiableQuoteHistory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAppliedRuleIds', 'getExtensionAttributes'])
            ->getMock();
        $this->quoteManagement = $this->createMock(QuoteManagement::class);
        $this->order = $this->createMock(Order::class);
        $this->purchaseOrder = $this->createMock(PurchaseOrderInterface::class);
        $this->purchaseOrderRepository = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $this->purchaseOrderLogManagement = $this->createMock(LogManagementInterface::class);
        $this->negotiableQuote = $this->createMock(NegotiableQuoteInterface::class);
        $this->quoteExtensionAttributes = $this->getCartExtensionInterfaceMock();
        $this->negotiableQuoteHistory = $this->createMock(History::class);
        $this->quoteRepository = $this->createMock(CartRepositoryInterface::class);

        $objectManager = new ObjectManager($this);

        $this->quoteManagementPlugin = $objectManager->getObject(
            QuoteManagementPlugin::class,
            [
                'purchaseOrderRepository' => $this->purchaseOrderRepository,
                'purchaseOrderLogManagement' => $this->purchaseOrderLogManagement,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteHistory' => $this->negotiableQuoteHistory,
            ]
        );
    }

    /**
     * Test for afterSubmit method.
     */
    public function testAfterSubmit()
    {
        // Expects method calls to check if a purchase order exists for the quote.
        $this->purchaseOrder->expects($this->once())
            ->method('getEntityId')
            ->willReturn(1);
        $this->purchaseOrderRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->purchaseOrder);
        $this->quote->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->any())
            ->method('getQuoteId')
            ->willReturn('33');
        $this->negotiableQuote->expects($this->once())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_ORDERED);
        $this->quoteRepository->expects($this->once())
            ->method('save')
            ->with($this->quote);
        $this->negotiableQuoteHistory->expects($this->once())
            ->method('updateLog')
            ->with('33');

        $this->expectsAppliedRuleIds();
        $this->expectsLinkPurchaseOrderToOrder();

        $this->quoteManagementPlugin->afterSubmit($this->quoteManagement, $this->order, $this->quote);
    }

    /**
     * Verifies the expected method calls related to the appliedRuleIds.
     */
    private function expectsAppliedRuleIds()
    {
        // Expects method call to get the AppliedRuleIds from the quote
        $this->quote->expects($this->atLeastOnce())
            ->method('getAppliedRuleIds')
            ->willReturn('53');

        // Expects method call to set those AppliedRuleIds on the newly created order
        $this->order->expects($this->once())
            ->method('setAppliedRuleIds')
            ->willReturnSelf();
    }

    /**
     * Verifies the expected method calls related to linking the purchase order to the order.
     */
    private function expectsLinkPurchaseOrderToOrder()
    {
        // Expects method call to determine if the purchase order is pending payment
        $this->purchaseOrder->expects($this->once())
            ->method('getStatus')
            ->willReturn(PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT);

        // Expects method call to get the id of the newly created order
        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        // Expects method calls to update the purchase order
        $this->purchaseOrder->expects($this->once())
            ->method('setOrderId');
        $this->purchaseOrder->expects($this->once())
            ->method('setOrderIncrementId');
        $this->purchaseOrder->expects($this->once())
            ->method('setStatus');
        $this->purchaseOrderRepository->expects($this->once())
            ->method('save');

        // Expects method call for log entry
        $this->purchaseOrderLogManagement->expects($this->once())
            ->method('logAction');
    }

    /**
     * Build CartExtensionInterface mock.
     *
     * @return MockObject
     */
    private function getCartExtensionInterfaceMock(): MockObject
    {
        $mockBuilder = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor();
        try {
            $mockBuilder->addMethods(['getNegotiableQuote']);
        } catch (RuntimeException $e) {
            // CartExtensionInterface already generated and has all necessary methods.
        }

        return $mockBuilder->getMockForAbstractClass();
    }
}
