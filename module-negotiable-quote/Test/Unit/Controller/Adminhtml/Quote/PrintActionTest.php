<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\PrintAction;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrintActionTest extends TestCase
{
    /**
     * @var PrintAction
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Page|MockObject
     */
    private $resultPage;

    /**
     * @var Config|MockObject
     */
    private $pageConfig;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var Session|MockObject
     */
    private $session;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var ActionFlag|MockObject
     */
    private $actionFlag;

    /**
     * @var int
     */
    private $quoteId;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteId = 42;

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam', 'initForward', 'setActionName', 'setDispatched'])
            ->getMockForAbstractClass();

        $this->resultFactory = $this->createMock(
            ResultFactory::class
        );
        $this->resultPage = $this->getMockBuilder(Page::class)
            ->addMethods(['addBreadcrumb'])
            ->onlyMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageConfig = $this->createPartialMock(
            Config::class,
            ['getTitle']
        );

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuoteManagement = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setIsUrlNotice'])
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->actionFlag = $this->getMockBuilder(ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->controller = $objectManagerHelper->getObject(
            PrintAction::class,
            [
                'resultFactory' => $this->resultFactory,
                'logger' => $this->logger,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                '_request' => $this->request,
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
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->resultPage);
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($this->quoteId);
        $this->resultPage->expects($this->any())->method('addBreadcrumb')->willReturnSelf();

        $this->resultPage->expects($this->once())->method('getConfig')->willReturn($this->pageConfig);

        $title = $this->createMock(
            Title::class
        );
        $this->pageConfig->expects($this->once())->method('getTitle')->willReturn($title);
        $title->expects($this->once())->method('prepend');

        $this->controller->execute();
    }

    /**
     * Test for method execute with null quote.
     *
     * @return void
     */
    public function testExecuteNoQuote()
    {
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn(null);

        $this->session->expects($this->any())->method('setIsUrlNotice');
        $this->request->expects($this->once())->method('initForward');
        $this->request->expects($this->once())->method('setActionName');
        $this->request->expects($this->once())->method('setDispatched');

        $this->controller->execute();
    }

    /**
     * Test for method execute throwing NoSuchEntityException.
     *
     * @return void
     */
    public function testExecuteNoSuchEntityException()
    {
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->resultPage);
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($this->quoteId);
        $this->resultPage->expects($this->any())->method('addBreadcrumb')->willReturnSelf();

        $exception = new NoSuchEntityException();
        $this->resultPage->expects($this->once())->method('getConfig')->willThrowException($exception);
        $this->messageManager->expects($this->any())->method('addError')->with('Quote not found');
        $this->actionFlag->expects($this->any())->method('set');

        $this->controller->execute();
    }

    /**
     * Test for method execute throwing Exception.
     *
     * @return void
     */
    public function testExecuteException()
    {
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($this->resultPage);
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($this->quoteId);

        $exception = new \Exception();
        $this->resultPage->expects($this->once())->method('addBreadcrumb')->willThrowException($exception);
        $this->logger->expects($this->any())
            ->method('critical')
            ->with($exception);
        $this->messageManager->expects($this->any())->method('addError')->with('Method is not exists');

        $this->controller->execute();
    }
}
