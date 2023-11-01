<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Expired;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\EmailSenderInterface;
use Magento\NegotiableQuote\Model\Expired\MerchantNotifier;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MerchantNotifierTest extends TestCase
{
    /**
     * @var EmailSenderInterface|MockObject
     */
    private $emailSender;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var MerchantNotifier
     */
    private $merchantNotifier;

    /**
     * Set up.
     * @return void
     */
    protected function setUp(): void
    {
        $this->emailSender = $this->getMockBuilder(EmailSenderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagement = $this->getMockBuilder(
            NegotiableQuoteManagementInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->merchantNotifier = $objectManager->getObject(
            MerchantNotifier::class,
            [
                'emailSender' => $this->emailSender,
                'scopeConfig' => $this->scopeConfig,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
            ]
        );
    }

    /**
     * Test sendNotification().
     * @return void
     */
    public function testSendNotification()
    {
        $expiredQuoteId = 1;
        $this->scopeConfig->expects($this->atLeastOnce())->method('getValue')->willReturn(true);
        $quote = $this->getMockBuilder(
            CartInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteManagement->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->with($expiredQuoteId)
            ->willReturn($quote);

        $this->merchantNotifier->sendNotification($expiredQuoteId);
    }
}
