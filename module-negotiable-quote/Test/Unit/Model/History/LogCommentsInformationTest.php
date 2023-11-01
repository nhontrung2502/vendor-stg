<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\History;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\Data\CommentInterface;
use Magento\NegotiableQuote\Api\Data\HistoryInterface;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\CommentRepositoryInterface;
use Magento\NegotiableQuote\Model\History\LogCommentsInformation;
use Magento\NegotiableQuote\Model\ResourceModel\CommentAttachment\Collection;
use Magento\NegotiableQuote\Model\Status\LabelProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for LogCommentsInformation.
 */
class LogCommentsInformationTest extends TestCase
{
    /**
     * @var CommentManagementInterface|MockObject
     */
    private $commentManagement;

    /**
     * @var CommentRepositoryInterface|MockObject
     */
    private $commentRepository;

    /**
     * @var LabelProviderInterface|MockObject
     */
    private $labelProvider;

    /**
     * @var LogCommentsInformation
     */
    private $logCommentsInformation;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->commentManagement = $this
            ->getMockBuilder(CommentManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->commentRepository = $this
            ->getMockBuilder(CommentRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->labelProvider = $this
            ->getMockBuilder(LabelProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->logCommentsInformation = $objectManagerHelper->getObject(
            LogCommentsInformation::class,
            [
                'commentManagement' => $this->commentManagement,
                'commentRepository' => $this->commentRepository,
                'labelProvider' => $this->labelProvider,
            ]
        );
    }

    /**
     * Test for getLogAuthor.
     *
     * @return void
     */
    public function testGetLogAuthor()
    {
        $authorId = 1;
        $quoteId = 2;
        $authorName = 'name';
        $historyLog = $this->getMockBuilder(HistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $historyLog->expects($this->atLeastOnce())->method('getAuthorId')->willReturn($authorId);
        $historyLog->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn(HistoryInterface::STATUS_CLOSED);
        $this->commentManagement->expects($this->atLeastOnce())
            ->method('checkCreatorLogExists')
            ->with($authorId)
            ->willReturn(true);
        $this->commentManagement->expects($this->atLeastOnce())
            ->method('getCreatorName')
            ->willReturn($authorName);
        $historyLog->expects($this->atLeastOnce())->method('getIsSeller')->willReturn(false);

        $this->assertEquals($authorName, $this->logCommentsInformation->getLogAuthor($historyLog, $quoteId));
    }

    /**
     * Test for getLogAuthor() with author name='System'.
     *
     * @param int|null $authorId
     * @param bool $isCreatorLogExists
     * @param int $getStatusInvokesCount
     * @param int $checkCreatorLogExistsInvokesCount
     * @return void
     * @dataProvider getLogAuthorWithSystemNameDataProvider
     */
    public function testGetLogAuthorWithSystemName(
        $authorId,
        $isCreatorLogExists,
        $getStatusInvokesCount,
        $checkCreatorLogExistsInvokesCount
    ) {
        $quoteId = 2;
        $historyLog = $this->getMockBuilder(HistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $historyLog->expects($this->atLeastOnce())->method('getAuthorId')->willReturn($authorId);
        $historyLog->expects($this->exactly($getStatusInvokesCount))
            ->method('getStatus')
            ->willReturn(HistoryInterface::STATUS_CLOSED);
        $this->commentManagement->expects($this->exactly($checkCreatorLogExistsInvokesCount))
            ->method('checkCreatorLogExists')
            ->with($authorId)
            ->willReturn($isCreatorLogExists);

        $this->assertEquals(__('System'), $this->logCommentsInformation->getLogAuthor($historyLog, $quoteId));
    }

    /**
     * Test for getCommentAttachments().
     *
     * @return void
     */
    public function testGetCommentAttachments()
    {
        $commentId = 1;
        $commentAttachments = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commentManagement->expects($this->atLeastOnce())->method('getCommentAttachments')->with($commentId)
            ->willReturn($commentAttachments);

        $this->assertInstanceOf(
            Collection::class,
            $this->logCommentsInformation->getCommentAttachments($commentId)
        );
    }

    /**
     * Test for getCommentText().
     *
     * @param string|null $commentText
     * @dataProvider getCommentTextDataProvider
     * @return void
     */
    public function testGetCommentText($commentText)
    {
        $commentId = 1;
        $comment = $this->getMockBuilder(CommentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $comment->expects($this->atLeastOnce())->method('getComment')->willReturn($commentText);
        $this->commentRepository->expects($this->atLeastOnce())->method('get')->with($commentId)->willReturn($comment);

        $this->assertEquals($commentText, $this->logCommentsInformation->getCommentText($commentId));
    }

    /**
     * Test for getStatusLabel().
     *
     * @return void
     */
    public function testGetStatusLabel()
    {
        $label = 'label';
        $this->labelProvider->expects($this->atLeastOnce())->method('getLabelByStatus')->willReturn($label);

        $this->assertEquals($label, $this->logCommentsInformation->getStatusLabel('status'));
    }

    /**
     * DataProvider for testGetLogAuthor().
     *
     * @return array
     */
    public function getLogAuthorWithSystemNameDataProvider()
    {
        return [
            [1, false, 1, 1],
            [null, true, 0, 0],
            [null, false, 0, 0]
        ];
    }

    /**
     * DataProvider for testGetCommentText().
     *
     * @return array
     */
    public function getCommentTextDataProvider()
    {
        return [
            ['comment'],
            [null]
        ];
    }
}
