<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Index;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IndexTest extends TestCase
{
    /**
     * @var Index
     */
    private $controller;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Index::class,
            [
                'resultFactory' => $this->resultFactory,
                'logger' => $logger,
                'quoteRepository' => $this->quoteRepository,
            ]
        );
    }

    public function testExecute()
    {
        $page = $this->getMockBuilder(Page::class)
            ->addMethods(['setActiveMenu', 'addBreadcrumb'])
            ->onlyMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($page);
        $title = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $page->expects($this->any())->method('getConfig')->willReturn($config);
        $config->expects($this->any())->method('getTitle')->willReturn($title);
        $result = $this->controller->execute();
        $this->assertInstanceOf(Page::class, $result);
    }
}
