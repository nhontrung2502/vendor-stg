<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\RemoveAllFailed;
use Magento\NegotiableQuote\Model\Cart;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveAllFailedTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Cart|MockObject
     */
    private $cart;

    /**
     * @var RemoveAllFailed
     */
    private $controller;

    /**
     * @var Raw|MockObject
     */
    private $resultRawFactory;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->cart = $this->createMock(Cart::class);
        $this->resultRawFactory =
            $this->createPartialMock(RawFactory::class, ['create']);
        $resultRaw =
            $this->createPartialMock(Raw::class, ['setContents', 'setHeader']);
        $resultRaw->expects($this->once())->method('setContents')->willReturn($resultRaw);
        $resultRaw->expects($this->once())->method('setHeader')->willReturn($resultRaw);
        $this->resultRawFactory->expects($this->once())->method('create')->willReturn($resultRaw);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            RemoveAllFailed::class,
            [
                'logger' => $this->logger,
                'messageManager' => $this->messageManager,
                'cart' => $this->cart,
                'resultRawFactory' => $this->resultRawFactory
            ]
        );
    }

    /**
     * Test for execute() method
     */
    public function testExecute()
    {
        $this->cart->expects($this->once())
            ->method('removeAllFailed');

        $this->assertInstanceOf(Raw::class, $this->controller->execute());
    }

    /**
     * Test for execute() method with exception
     */
    public function testExecuteWithException()
    {
        $this->cart->expects($this->once())
            ->method('removeAllFailed')
            ->willThrowException(new \Exception());
        $this->logger->expects($this->once())->method('critical');
        $this->messageManager->expects($this->once())->method('addError');

        $this->assertInstanceOf(Raw::class, $this->controller->execute());
    }
}
