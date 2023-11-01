<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\UpdateErrors;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateErrorsTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var UpdateErrors
     */
    private $controller;

    /**
     * @var RawFactory|MockObject
     */
    private $resultRawFactory;

    /**
     * @inherindoc
     */
    protected function setUp(): void
    {
        $layout = $this->createPartialMock(Layout::class, ['renderElement']);
        $layout->expects($this->once())->method('renderElement')->willReturn('test');
        $resultPage = $this->createPartialMock(Page::class, ['addHandle', 'getLayout']);
        $resultPage->expects($this->exactly(2))
            ->method('addHandle')
            ->withConsecutive(
                ['sales_order_create_load_block_json'],
                ['quotes_quote_update_load_block_errors']
            );
        $resultPage->expects($this->once())->method('getLayout')->willReturn($layout);
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultFactory->expects($this->once())->method('create')->willReturn($resultPage);
        $this->resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $resultRaw = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->setMethods(['setContents'])
            ->getMock();
        $resultRaw->expects($this->once())->method('setContents')->willReturn($resultRaw);
        $this->resultRawFactory->expects($this->once())->method('create')->willReturn($resultRaw);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            UpdateErrors::class,
            [
                'resultFactory' => $this->resultFactory,
                'resultRawFactory' => $this->resultRawFactory
            ]
        );
    }

    /**
     * Test for method execute
     */
    public function testExecute()
    {
        $result = $this->controller->execute();
        get_class($result);
        $this->assertInstanceOf(ResultInterface::class, $result);
    }
}
