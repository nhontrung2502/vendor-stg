<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\ResourceModel\Comment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Comment|MockObject
     */
    protected $comment;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connection;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $resource = $this->createMock(ResourceConnection::class);
        $this->connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $resource->expects($this->any())->method('getConnection')->willReturn($this->connection);
        $this->context->expects($this->any())->method('getResources')->willReturn($resource);
        $objectManager = new ObjectManager($this);
        $this->comment = $objectManager->getObject(
            Comment::class,
            [
                'context' => $this->context,
            ]
        );
    }

    /**
     * Test saveNegotiatedQuoteData()
     */
    public function testSaveCommentData()
    {
        $comment = $this->createMock(\Magento\NegotiableQuote\Model\Comment::class);
        $comment->expects($this->any())->method('getData')->willReturn(['id' => 1, 'comment' => 'submitted']);

        $this->assertInstanceOf(
            Comment::class,
            $this->comment->saveCommentData($comment)
        );
    }

    /**
     * Test saveNegotiatedQuoteData() with exception
     */
    public function testSaveCommentDataException()
    {
        $comment = $this->createMock(\Magento\NegotiableQuote\Model\Comment::class);
        $this->connection->expects($this->any())
            ->method('insertOnDuplicate')
            ->willThrowException(new \Exception(''));
        $comment->expects($this->any())->method('getData')->willReturn(['id' => 1, 'comment' => 'submitted']);

        $this->expectException(CouldNotSaveException::class);
        $this->comment->saveCommentData($comment);
    }
}
