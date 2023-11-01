<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Exception;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\NegotiableQuote\Api\Data\CommentAttachmentInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\CommentInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Save;
use Magento\NegotiableQuote\Controller\FileProcessor;
use Magento\NegotiableQuote\Model\CommentManagement;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\ResourceModel\Comment\Collection;
use Magento\NegotiableQuote\Model\ResourceModel\CommentAttachment as CommentAttachmentResource;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Save.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var Save
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

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
     * @var CommentAttachmentInterfaceFactory|MockObject
     */
    private $commentAttachmentFactory;

    /**
     * @var CommentAttachmentResource|MockObject
     */
    private $commentAttachmentResource;

    /**
     * @var CommentManagement|MockObject
     */
    private $commentManagement;

    /**
     * Setup tests.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $actionFlag = $this->createMock(ActionFlag::class);
        $quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $fileProcessor = $this->getMockBuilder(FileProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFiles'])
            ->getMockForAbstractClass();
        $fileProcessor->expects($this->atLeastOnce())
            ->method('getFiles')
            ->willReturn([]);
        $redirectFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $redirect = $this->getMockBuilder(ResultInterface::class)
            ->addMethods(['setData'])
            ->onlyMethods(['setHttpResponseCode', 'setHeader', 'renderResult'])
            ->getMockForAbstractClass();
        $redirect->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $redirectFactory->expects($this->any())
            ->method('create')
            ->willReturn($redirect);
        $dataObjectHelper = $this->createMock(
            DataObjectHelper::class
        );
        $negotiableQuoteRepository = $this->createMock(
            NegotiableQuoteRepositoryInterface::class
        );
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes', 'getId'])
            ->getMock();
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $quoteNegotiation->expects($this->any())
            ->method('getIsRegularQuote')
            ->willReturn(true);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionAttributes->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $this->quote->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $quoteRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->quote);
        $this->negotiableQuoteManagement = $this->createMock(
            NegotiableQuoteManagementInterface::class
        );
        $this->commentAttachmentFactory = $this->createPartialMock(
            CommentAttachmentInterfaceFactory::class,
            ['create']
        );
        $this->commentAttachmentResource = $this->createPartialMock(
            CommentAttachmentResource::class,
            ['delete']
        );
        $this->commentManagement = $this->createPartialMock(
            CommentManagement::class,
            ['hasDraftComment', 'getQuoteComments', 'getCommentAttachments']
        );

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Save::class,
            [
                'messageManager' => $this->messageManager,
                'actionFlag' => $actionFlag,
                'resultFactory' => $redirectFactory,
                'request' => $this->request,
                'logger' => $logger,
                'quoteRepository' => $quoteRepository,
                'dataObjectHelper' => $dataObjectHelper,
                'negotiableQuoteRepository' => $negotiableQuoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'attachmentInterfaceFactory' => $this->commentAttachmentFactory,
                'commentAttachmentResource' => $this->commentAttachmentResource,
                'fileProcessor' => $fileProcessor,
                'commentManagement' => $this->commentManagement
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
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote'], ['dataSend'], ['comment'], ['delFiles'])
            ->willReturnOnConsecutiveCalls(1, [], json_encode([]), [], '1,2');
        $page = $this->getMockBuilder(Page::class)
            ->addMethods(['setActiveMenu', 'addBreadcrumb'])
            ->onlyMethods(['getConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $title = $this->createMock(Title::class);
        $config = $this->createMock(Config::class);
        $page->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $config->expects($this->any())
            ->method('getTitle')
            ->willReturn($title);
        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->negotiableQuoteManagement->expects($this->any())
            ->method('saveAsDraft')
            ->with(1, [], ['message' => [], 'files' => []])
            ->willReturnSelf();
        $commentAttachment = $this->getMockForAbstractClass(
            AbstractModel::class,
            [],
            '',
            false,
            false,
            true,
            ['load', 'getFileName', 'getAttachmentId']
        );
        $this->commentAttachmentFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($commentAttachment);
        $commentAttachment->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnSelf();
        $commentAttachment->expects($this->atLeastOnce())
            ->method('getFileName')
            ->willReturn('filename.doc');
        $commentAttachment->expects($this->atLeastOnce())
            ->method('getAttachmentId')
            ->willReturn(2);

        $this->commentAttachmentResource->expects($this->atLeastOnce())
            ->method('delete');
        $this->commentManagement->expects($this->once())
            ->method('hasDraftComment')
            ->with(1)
            ->willReturn(true);
        $comment = $this->getMockForAbstractClass(
            CommentInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getEntityId']
        );
        $comment->expects($this->once())
            ->method('getEntityId')
            ->willReturn(1);
        $commentCollection = $this->createPartialMock(
            Collection::class,
            ['getFirstItem']
        );
        $commentCollection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($comment);
        $this->commentManagement->expects($this->once())
            ->method('getQuoteComments')
            ->with(1, true)
            ->willReturn($commentCollection);
        $this->commentManagement->expects($this->once())
            ->method('getCommentAttachments')
            ->with(1)
            ->willReturn([$commentAttachment]);

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote'], ['dataSend'])
            ->willReturnOnConsecutiveCalls(1, [], json_encode([]));
        $this->messageManager->expects($this->once())
            ->method('addError');
        $this->negotiableQuoteManagement->expects($this->any())
            ->method('saveAsDraft')
            ->willThrowException(new Exception());

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException(): void
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote'], ['dataSend'], ['comment'], ['delFiles'])
            ->willReturnOnConsecutiveCalls(1, [], json_encode([]), [], '1,2');
        $this->messageManager->expects($this->once())
            ->method('addError');
        $commentAttachment = $this->getMockForAbstractClass(
            AbstractModel::class,
            [],
            '',
            false,
            false,
            true,
            ['load']
        );
        $this->commentAttachmentFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($commentAttachment);
        $commentAttachment->expects($this->atLeastOnce())
            ->method('load')
            ->willThrowException(new NoSuchEntityException());

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }
}
