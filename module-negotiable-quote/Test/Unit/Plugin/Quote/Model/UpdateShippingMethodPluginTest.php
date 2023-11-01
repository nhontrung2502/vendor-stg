<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Plugin\Quote\Model\UpdateShippingMethodPlugin;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Plugin\Quote\Model\UpdateShippingMethodPlugin class.
 */
class UpdateShippingMethodPluginTest extends TestCase
{
    /**
     * @var ShippingMethodManagementInterface|MockObject
     */
    private $shippingMethodManagement;

    /**
     * @var UpdateShippingMethodPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shippingMethodManagement = $this
            ->getMockBuilder(ShippingMethodManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            UpdateShippingMethodPlugin::class,
            [
                'shippingMethodManagement' => $this->shippingMethodManagement
            ]
        );
    }

    /**
     * Test afterLoad method.
     *
     * @return void
     */
    public function testAfterLoad()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(LoadHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getId', 'getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'getShippingAssignments'])
            ->getMockForAbstractClass();
        $shippingAssignment = $this->getMockBuilder(ShippingAssignmentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuote);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getShippingAssignments')
            ->willReturn([$shippingAssignment]);
        $shipping = $this->getMockBuilder(ShippingInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $shippingAssignment->expects($this->atLeastOnce())->method('getShipping')->willReturn($shipping);
        $shipping->expects($this->atLeastOnce())->method('getMethod')->willReturn('free_free');
        $shipping->expects($this->once())->method('setMethod');
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $negotiableQuote->expects($this->once())->method('setShippingPrice');
        $shippingMethod = $this->getMockBuilder(ShippingMethodInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $shippingMethod->expects($this->atLeastOnce())->method('getCarrierCode')->willReturn('fix');
        $shippingMethod->expects($this->atLeastOnce())->method('getMethodCode')->willReturn('fix');
        $this->shippingMethodManagement->expects($this->once())->method('getList')->willReturn([$shippingMethod]);

        $this->assertEquals($quote, $this->plugin->afterLoad($subject, $quote));
    }

    /**
     * Test afterLoad method with quote in ordered status.
     *
     * @return void
     */
    public function testAfterLoadWithOrderedQuote()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(LoadHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getId', 'getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'getShippingAssignments'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuote);
        $extensionAttributes->expects($this->never())
            ->method('getShippingAssignments');
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $negotiableQuote->expects($this->once())->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_ORDERED);

        $this->assertEquals($quote, $this->plugin->afterLoad($subject, $quote));
    }
}
