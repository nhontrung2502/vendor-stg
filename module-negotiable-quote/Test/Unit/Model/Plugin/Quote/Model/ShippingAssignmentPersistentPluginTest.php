<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Quote\Model;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Plugin\Quote\Model\ShippingAssignmentPersisterPlugin;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentPersister;
use PHPUnit\Framework\TestCase;

class ShippingAssignmentPersistentPluginTest extends TestCase
{
    /**
     * @var ShippingAssignmentPersisterPlugin
     */
    private $shippingAssignmentPersistentPlugin;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->shippingAssignmentPersistentPlugin =
            new ShippingAssignmentPersisterPlugin();
    }

    /**
     * Test for method aroundSave
     */
    public function testAroundSave()
    {
        $subject = $this->createMock(ShippingAssignmentPersister::class);
        $quote = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false
        );
        $shippingAssignment = $this->getMockForAbstractClass(
            ShippingAssignmentInterface::class,
            [],
            '',
            false
        );
        $proceed = function () use ($quote, $shippingAssignment) {
            return [
                $quote,
                $shippingAssignment
            ];
        };
        $quote->expects($this->once())->method('getIsActive')->willReturn(true);

        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getNegotiableQuote']
        );
        $quote->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $negotiableQuote = $this->getMockForAbstractClass(
            NegotiableQuoteInterface::class,
            [],
            '',
            false
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $quote->expects($this->any())->method('setIsActive')->willReturn(true);

        $this->shippingAssignmentPersistentPlugin->aroundSave($subject, $proceed, $quote, $shippingAssignment);
    }
}
