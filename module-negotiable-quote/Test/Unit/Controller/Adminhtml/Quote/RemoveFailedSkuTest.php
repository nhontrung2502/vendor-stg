<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\RemoveFailedSku;
use Magento\NegotiableQuote\Model\Cart;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveFailedSkuTest extends TestCase
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
     * @var RemoveFailedSku
     */
    private $controller;

    /**
     * @var Raw|MockObject
     */
    private $resultRawFactory;

    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Raw|MockObject
     */
    private $resultRaw;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->cart = $this->createMock(Cart::class);
        $this->resultRawFactory = $this->createPartialMock(
            RawFactory::class,
            ['create']
        );
        $this->resultRaw = $this->createPartialMock(
            Raw::class,
            ['setContents', 'setHeader']
        );
        $this->resultRaw->expects($this->once())->method('setContents')->willReturn($this->resultRaw);
        $this->resultRaw->expects($this->once())->method('setHeader')->willReturn($this->resultRaw);
        $this->resultRawFactory->expects($this->once())->method('create')->willReturn($this->resultRaw);

        $this->request = $this->getMockForAbstractClass(
            RequestInterface::class,
            ['getParam'],
            '',
            false,
            false,
            true,
            []
        );
        $this->context = $this->createPartialMock(
            Context::class,
            ['getRequest']
        );
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
    }

    /**
     * Creates an instance of subject under test
     */
    private function createInstance()
    {
        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            RemoveFailedSku::class,
            [
                'context' => $this->context,
                'logger' => $this->logger,
                'messageManager' => $this->messageManager,
                'cart' => $this->cart,
                'resultRawFactory' => $this->resultRawFactory
            ]
        );
    }

    /**
     * Test for execute() method with exception
     */
    public function testExecuteWithException()
    {
        $this->cart->expects($this->once())
            ->method('removeFailedSku')
            ->willThrowException(new \Exception());
        $this->logger->expects($this->once())->method('critical');
        $this->messageManager->expects($this->once())->method('addError');
        $this->createInstance();
        $result = $this->controller->execute();

        $this->assertSame($result, $this->resultRaw);
    }

    /**
     * Test for execute() method
     */
    public function testExecute()
    {
        $sku = 'test_sku';
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->request->expects(($this->once()))
            ->method('getParam')
            ->with('remove_sku')
            ->willReturn($sku);
        $this->cart->expects($this->once())
            ->method('removeFailedSku')
            ->with($sku);
        $this->createInstance();
        $result = $this->controller->execute();

        $this->assertSame($result, $this->resultRaw);
    }
}
