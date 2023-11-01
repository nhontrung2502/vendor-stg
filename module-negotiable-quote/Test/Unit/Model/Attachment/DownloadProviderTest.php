<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Attachment;

use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CommentAttachmentInterface;
use Magento\NegotiableQuote\Model\Attachment\DownloadPermission\AllowInterface;
use Magento\NegotiableQuote\Model\Attachment\DownloadProvider;
use Magento\NegotiableQuote\Model\Attachment\File;
use Magento\NegotiableQuote\Model\CommentAttachment;
use Magento\NegotiableQuote\Model\CommentAttachmentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DownloadProviderTest extends TestCase
{
    /**
     * @var CommentAttachmentFactory|MockObject
     */
    protected $commentAttachmentFactory;

    /**
     * @var FileFactory|MockObject
     */
    protected $fileFactory;

    /**
     * @var AllowInterface|PHPUnitFrameworkMockObjectMockObject
     */
    protected $allowDownload;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var File|MockObject
     */
    protected $file;

    /**
     * @var DownloadProvider|MockObject
     */
    protected $downloadProvider;

    /**
     * @var CommentAttachmentInterface|MockObject
     */
    private $attachment;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->attachment = $this->createPartialMock(
            CommentAttachment::class,
            ['load', 'getAttachmentId']
        );
        $this->attachment->expects($this->any())->method('load')->willReturnSelf();
        $this->commentAttachmentFactory =
            $this->createPartialMock(CommentAttachmentFactory::class, ['create']);
        $this->commentAttachmentFactory->expects($this->any())->method('create')->willReturn($this->attachment);
        $this->fileFactory =
            $this->createPartialMock(FileFactory::class, ['create']);
        $this->allowDownload = $this->createPartialMock(
            AllowInterface::class,
            ['isAllowed']
        );

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->file = $this->createPartialMock(
            File::class,
            ['downloadContents']
        );
        $objectManager = new ObjectManager($this);
        $this->downloadProvider = $objectManager->getObject(
            DownloadProvider::class,
            [
                'commentAttachmentFactory' => $this->commentAttachmentFactory,
                'fileFactory' => $this->fileFactory,
                'allowDownload' => $this->allowDownload,
                'logger' => $this->logger,
                'file' => $this->file,
                'attachmentId' => 1

            ]
        );
    }

    /**
     * Test getAttachmentContents()
     */
    public function testGetAttachmentContents()
    {
        $this->allowDownload->expects($this->any())->method('isAllowed')->willReturn(true);
        $this->attachment->expects($this->any())->method('getAttachmentId')->willReturn(1);
        $this->file->expects($this->once())->method('downloadContents')->willReturn('contents');
        $this->downloadProvider->getAttachmentContents();
    }

    /**
     * Test getAttachmentContents() with exception
     */
    public function testGetAttachmentContentsWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->allowDownload->expects($this->any())->method('isAllowed')->willReturn(true);
        $this->attachment->expects($this->any())->method('getAttachmentId')->willReturn(null);
        $this->downloadProvider->getAttachmentContents();
    }
}
