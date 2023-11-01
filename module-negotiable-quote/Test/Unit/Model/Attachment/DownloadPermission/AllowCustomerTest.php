<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Attachment\DownloadPermission;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Attachment\DownloadPermission\AllowCustomer;
use Magento\NegotiableQuote\Model\Comment;
use Magento\NegotiableQuote\Model\CommentAttachment;
use Magento\NegotiableQuote\Model\CommentAttachmentFactory;
use Magento\NegotiableQuote\Model\CommentRepository;
use Magento\NegotiableQuote\Model\CommentRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\Attachment\DownloadPermission\AllowCustomer method.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AllowCustomerTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CommentAttachmentFactory|MockObject
     */
    private $commentAttachmentFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var CommentRepositoryInterface|MockObject
     */
    private $commentRepository;

    /**
     * @var AllowCustomer
     */
    private $allowCustomer;

    /**
     * @var CommentAttachment|MockObject
     */
    private $attachment;

    /**
     * @var Structure|MockObject
     */
    private $structureMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attachment = $this->getMockBuilder(CommentAttachment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commentAttachmentFactory = $this->getMockBuilder(
            CommentAttachmentFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->commentRepository = $this->getMockBuilder(CommentRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $this->structureMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->allowCustomer = $objectManager->getObject(
            AllowCustomer::class,
            [
                'userContext' => $this->userContext,
                'commentAttachmentFactory' => $this->commentAttachmentFactory,
                'quoteRepository' => $this->quoteRepository,
                'commentRepository' => $this->commentRepository,
                'structure' => $this->structureMock
            ]
        );
    }

    /**
     * Test isAllowed method.
     *
     * @return void
     */
    public function testIsAllowed()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $comment = $this->getMockBuilder(Comment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentId'])
            ->getMock();
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn(333);
        $this->commentAttachmentFactory->expects($this->once())->method('create')->willReturn($this->attachment);
        $this->attachment->expects($this->once())->method('load')->willReturnSelf();
        $this->attachment->expects($this->once())->method('getAttachmentId')->willReturn(1);
        $this->structureMock->expects($this->once())
            ->method('getAllowedChildrenIds')
            ->willReturn([1, 2]);
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($quote);
        $quote->expects($this->once())->method('getCustomer')->willReturnSelf();
        $quote->expects($this->once())->method('getId')->willReturn(333);
        $this->commentRepository->expects($this->once())->method('get')->willReturn($comment);
        $comment->expects($this->atLeastOnce())->method('getParentId')->willReturn(3);

        $this->assertTrue($this->allowCustomer->isAllowed(1));
    }

    /**
     * Test isAllowed method with exception.
     *
     * @return void
     */
    public function testIsAllowedWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $exceptionMessage = 'An error occurred.';
        $exception = new NoSuchEntityException(__($exceptionMessage));
        $this->commentAttachmentFactory->expects($this->once())->method('create')->willReturn($this->attachment);
        $this->attachment->expects($this->once())->method('load')->willThrowException($exception);
        $this->assertTrue($this->allowCustomer->isAllowed(1));
    }
}
