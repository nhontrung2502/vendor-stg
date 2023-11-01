<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CommentInterface;
use Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory;
use Magento\NegotiableQuote\Model\Comment\SearchProvider;
use Magento\NegotiableQuote\Model\CommentRepository;
use Magento\NegotiableQuote\Model\ResourceModel\Comment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for \Magento\NegotiableQuote\Model\CommentRepository class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CommentRepositoryTest extends TestCase
{
    /**
     * @var Comment|MockObject
     */
    private $commentResource;

    /**
     * @var CommentInterfaceFactory|MockObject
     */
    private $commentFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var SearchProvider
     */
    private $searchProvider;

    /**
     * @var CommentRepository
     */
    protected $commentRepository;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->commentResource = $this->getMockBuilder(Comment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commentFactory = $this->getMockBuilder(CommentInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $comment = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Comment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $comment->expects($this->any())->method('load')->willReturnSelf();
        $comment->expects($this->any())->method('getId')->willReturn(14);
        $comment->expects($this->any())->method('delete')->willReturnSelf();

        $this->searchProvider = $this->getMockBuilder(SearchProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMock();

        $this->commentFactory->expects($this->any())->method('create')->willReturn($comment);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->commentRepository = $objectManager->getObject(
            CommentRepository::class,
            [
                'commentResource' => $this->commentResource,
                'commentFactory'  => $this->commentFactory,
                'logger'          => $this->logger,
                'searchProvider'  => $this->searchProvider,
            ]
        );
    }

    /**
     * Test for method Save
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function testSave()
    {
        $comment = $this->getMockBuilder(CommentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $comment->expects($this->any())->method('getEntityId')->willReturn(1);
        $this->commentResource->expects($this->once())->method('saveCommentData');
        $this->assertTrue($this->commentRepository->save($comment));
    }

    /**
     * Test for method save
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function testSaveEmpty()
    {
        $comment = $this->getMockBuilder(CommentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $comment->expects($this->any())->method('getEntityId')->willReturn(null);
        $this->commentResource->expects($this->never())->method('saveCommentData');
        $this->assertFalse($this->commentRepository->save($comment));
    }

    /**
     * Test for method save with exeption
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function testSaveExeption()
    {
        $comment = $this->getMockBuilder(CommentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $comment->expects($this->any())->method('getEntityId')->willReturn(1);
        $this->commentResource->expects($this->once())
            ->method('saveCommentData')->willThrowException(new \Exception());
        $this->logger->expects($this->once())->method('critical');
        $this->expectException(CouldNotSaveException::class);
        $this->assertFalse($this->commentRepository->save($comment));
    }

    /**
     * Test for method \Magento\NegotiableQuote\Model\CommentRepository::get
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGet()
    {
        $id = 14;
        $comment = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Comment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($this->commentRepository->get($id), $comment);
    }

    /**
     * Test for method \Magento\NegotiableQuote\Model\CommentRepository::delete
     *
     * @return void
     * @throws StateException
     */
    public function testDelete()
    {
        $comment = $this->getMockBuilder(\Magento\NegotiableQuote\Model\Comment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commentResource->expects($this->once())
            ->method('delete')
            ->with($comment)
            ->willReturnSelf();

        $this->assertTrue($this->commentRepository->delete($comment));
    }

    /**
     * Test for method \Magento\NegotiableQuote\Model\CommentRepository::deleteById
     *
     * @return void
     */
    public function testDeleteById()
    {
        $commentId = 14;
        $this->assertTrue($this->commentRepository->deleteById($commentId));
    }

    /**
     * Test for method \Magento\NegotiableQuote\Model\CommentRepository::getList
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchResults = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchProvider->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $this->assertEquals($searchResults, $this->commentRepository->getList($searchCriteria));
    }
}
