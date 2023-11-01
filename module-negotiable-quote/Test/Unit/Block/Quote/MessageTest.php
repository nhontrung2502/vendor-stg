<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Block\Quote\Message;
use Magento\NegotiableQuote\Helper\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Message|MockObject
     */
    private $message;

    /**
     * @var Quote|MockObject
     */
    private $quoteHelperMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteHelperMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLockMessageDisplayed', 'isViewedByOwner', 'isExpiredMessageDisplayed'])
            ->getMockForAbstractClass();
    }

    /**
     * Create instance
     *
     * @return void
     */
    private function createInstance()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->message = $this->objectManagerHelper->getObject(
            Message::class,
            [
                'quoteHelper' => $this->quoteHelperMock
            ]
        );
    }

    /**
     * Test for getMessages() method
     *
     * @return void
     */
    public function testGetMessages()
    {
        $expectedMessages = [
            __('This quote is currently locked for editing. It will become available once released by the Merchant.'),
            __(
                'Your quote has expired and the product prices have been updated as per the latest prices in your'
                . ' catalog. You can either re-submit the quote to seller for further negotiation or go to checkout.'
            ),
            __('You are not an owner of this quote. You cannot edit it or take any actions on it.')
        ];

        $this->quoteHelperMock->expects($this->any())
            ->method('isLockMessageDisplayed')
            ->willReturn(true);
        $this->quoteHelperMock->expects($this->any())
            ->method('isViewedByOwner')
            ->willReturn(false);
        $this->quoteHelperMock->expects($this->any())
            ->method('isExpiredMessageDisplayed')
            ->willReturn(true);

        $this->createInstance();
        $this->assertEquals($expectedMessages, $this->message->getMessages());
    }
}
