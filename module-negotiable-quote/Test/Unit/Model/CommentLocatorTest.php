<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CommentInterface;
use Magento\NegotiableQuote\Model\Comment;
use Magento\NegotiableQuote\Model\CommentLocator;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\ResourceModel\Comment\CollectionFactory;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for test CommentLocator.
 */
class CommentLocatorTest extends TestCase
{
    /**
     * @var CollectionFactory|PHPUnitFrameworkMockObjectMockObject
     */
    private $commentCollectionFactory;

    /**
     * @var CommentManagementInterface|MockObject
     */
    private $commentManagement;

    /**
     * @var \Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory|PHPUnitFrameworkMockObjectMockObject
     */
    private $negotiableQuoteCollectionFactory;

    /**
     * @var Collection|MockObject
     */
    private $quoteCollection;

    /**
     * @var CommentLocator
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->commentCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->commentManagement = $this->getMockBuilder(
            CommentManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getCommentAttachments'])
            ->getMockForAbstractClass();
        $this->quoteCollection = $this->getMockBuilder(
            Collection::class
        )
            ->setMethods(['addFieldToFilter', 'getSize'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteCollectionFactory = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->negotiableQuoteCollectionFactory->expects($this->once())
            ->method('create')->willReturn($this->quoteCollection);

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            CommentLocator::class,
            [
                'collectionFactory' => $this->commentCollectionFactory,
                'commentManagement' => $this->commentManagement,
                'negotiableQuoteCollectionFactory' => $this->negotiableQuoteCollectionFactory
            ]
        );
    }

    /**
     * Test for getListForQuote() method.
     *
     * @return void
     */
    public function testGetListForQuote()
    {
        $quoteId = 1;
        $this->quoteCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->withConsecutive(['entity_id', $quoteId])
            ->willReturnSelf();
        $this->quoteCollection->expects($this->once())
            ->method('getSize')->willReturn(1);
        $commentCollection = $this
            ->getMockBuilder(\Magento\NegotiableQuote\Model\ResourceModel\Comment\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load'])
            ->getMock();
        $this->commentCollectionFactory->expects($this->atLeastOnce())
            ->method('create')->willReturn($commentCollection);
        $commentCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->withConsecutive(['parent_id', $quoteId])
            ->willReturnSelf();
        $comment = $this->getComment();
        $commentCollection->addItem($comment);
        $attachmentCollection = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\ResourceModel\CommentAttachment\Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMock();
        $attachmentCollection->expects($this->once())->method('getItems')->willReturn([]);
        $this->commentManagement->expects($this->atLeastOnce())
            ->method('getCommentAttachments')
            ->willReturn($attachmentCollection);
        $expected = [4 => $comment];
        $this->assertEquals($expected, $this->model->getListForQuote($quoteId));
    }

    /**
     * Test for getListForQuote() method with exception of not existed quote.
     *
     * @return void
     */
    public function testGetListForQuoteWithException()
    {
        $quoteId = 1;
        $this->quoteCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->withConsecutive(['entity_id', $quoteId])
            ->willReturnSelf();
        $this->quoteCollection->expects($this->once())
            ->method('getSize')->willReturn(0);
        $this->expectException(NoSuchEntityException::class);
        $this->model->getListForQuote($quoteId);
    }

    /**
     * Get mock comment for qoute.
     *
     * @return CommentInterface
     */
    private function getComment()
    {
        $comment = $this->getMockBuilder(Comment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $comment->expects($this->atLeastOnce())->method('getId')->willReturn(4);
        $comment->expects($this->atLeastOnce())->method('getEntityId')->willReturn(4);
        return $comment;
    }
}
