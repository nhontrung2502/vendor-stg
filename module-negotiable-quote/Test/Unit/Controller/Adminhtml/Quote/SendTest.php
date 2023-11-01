<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface as NegotiableQuote;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Send;
use Magento\NegotiableQuote\Controller\FileProcessor;
use Magento\NegotiableQuote\Model\CommentRepositoryInterface;
use Magento\NegotiableQuote\Model\Restriction\Admin;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SendTest extends TestCase
{
    /**
     * @var Send
     */
    private $controller;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $resource = $this->createMock(Http::class);
        $resource->expects($this->exactly(4))
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['comment'], ['quote'], ['dataSend'])
            ->willReturnOnConsecutiveCalls(1, [], json_encode([]), 'comment text');
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $fileProcessor = $this->getMockBuilder(FileProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFiles'])
            ->getMockForAbstractClass();
        $fileProcessor->expects($this->atLeastOnce())->method('getFiles')->willReturn([]);
        $quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $redirect = $this->getMockBuilder(ResultInterface::class)
            ->addMethods(['setData'])
            ->onlyMethods(['setHttpResponseCode', 'setHeader', 'renderResult'])
            ->getMockForAbstractClass();
        $redirect->expects($this->any())->method('setData')->willReturnSelf();
        $resultFactory->expects($this->any())->method('create')->willReturn($redirect);
        $dataObjectHelper = $this->createMock(DataObjectHelper::class);
        $negotiableQuoteRepository =
            $this->getMockForAbstractClass(NegotiableQuoteRepositoryInterface::class);
        $this->quote = $this->createPartialMock(Quote::class, ['getExtensionAttributes', 'getId']);
        $quoteNegotiation = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $quoteNegotiation->expects($this->any())->method('getIsRegularQuote')->willReturn(true);
        $quoteNegotiation->expects($this->any())->method('getStatus')->willReturn(NegotiableQuote::STATUS_CREATED);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $this->quote->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $quoteRepository->expects($this->any())->method('get')->willReturn($this->quote);

        $this->negotiableQuoteManagement =
            $this->getMockForAbstractClass(NegotiableQuoteManagementInterface::class);
        $commentRepository =
            $this->getMockBuilder(CommentRepositoryInterface::class)
                ->setMethods(['create'])
                ->getMockForAbstractClass();
        $commentRepository->expects($this->any())->method('create')->willReturn(true);
        $restriction = $this->createMock(Admin::class);
        $restriction->setQuote($this->quote);
        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Send::class,
            [
                'request' => $resource,
                'resultFactory' => $resultFactory,
                'messageManager' => $this->messageManager,
                'logger' => $logger,
                'quoteRepository' => $quoteRepository,
                'restriction' => $restriction,
                'dataObjectHelper' => $dataObjectHelper,
                'fileProcessor' => $fileProcessor,
                'negotiableQuoteRepository' => $negotiableQuoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $page = $this->getMockBuilder(Page::class)
            ->addMethods(['setActiveMenu', 'addBreadcrumb'])
            ->onlyMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $title = $this->createMock(Title::class);
        $config = $this->createMock(Config::class);
        $page->expects($this->any())->method('getConfig')->willReturn($config);
        $config->expects($this->any())->method('getTitle')->willReturn($title);
        $this->quote->expects($this->any())->method('getId')
            ->willReturn(1);

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->messageManager->expects($this->once())->method('addErrorMessage');
        $this->negotiableQuoteManagement->expects($this->any())
            ->method('adminSend')->willThrowException(new \Exception());

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }
}
