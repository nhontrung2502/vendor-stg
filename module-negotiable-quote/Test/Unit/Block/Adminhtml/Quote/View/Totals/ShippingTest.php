<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\View\Totals;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals\Shipping;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingTest extends TestCase
{
    /**
     * @var Shipping
     */
    protected $shipping;

    /**
     * @var Quote|MockObject
     */
    protected $negotiableQuoteHelper;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteHelper = $this->createMock(Quote::class);

        $objectManager = new ObjectManager($this);
        $this->shipping = $objectManager->getObject(
            Shipping::class,
            [
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
            ]
        );
    }

    /**
     * Test canEdit
     *
     * @return void
     */
    public function testCanEdit()
    {
        $this->negotiableQuoteHelper->expects($this->once())->method('isSubmitAvailable')->willReturn(true);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAddress'])
            ->getMockForAbstractClass();
        $address = $this->createMock(Address::class);
        $address->expects($this->atLeastOnce())->method('getPostcode')->willReturn(11001);
        $quote->expects($this->atLeastOnce())->method('getShippingAddress')->willReturn($address);
        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $layout->expects($this->atLeastOnce())->method('getParentName')->willReturn('parent');
        $parent = $this->createMock(Totals::class);
        $parent->expects($this->atLeastOnce())->method('getQuote')->willReturn($quote);
        $layout->expects($this->atLeastOnce())->method('getBlock')->willReturn($parent);

        $this->shipping->setLayout($layout);
        $this->assertTrue($this->shipping->canEdit());
    }
}
