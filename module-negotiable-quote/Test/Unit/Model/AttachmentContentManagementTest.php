<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\AttachmentContentInterface;
use Magento\NegotiableQuote\Api\Data\AttachmentContentInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\CommentAttachmentInterface;
use Magento\NegotiableQuote\Model\AttachmentContentManagement;
use Magento\NegotiableQuote\Model\CommentManagement;
use Magento\NegotiableQuote\Model\ResourceModel\CommentAttachment\Collection;
use Magento\NegotiableQuote\Model\ResourceModel\CommentAttachment\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AttachmentContentManagement model.
 */
class AttachmentContentManagementTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $attachmentCollectionFactory;

    /**
     * @var AttachmentContentInterfaceFactory|MockObject
     */
    private $attachmentContentFactory;

    /**
     * @var File|MockObject
     */
    private $fileDriver;

    /**
     * @var WriteInterface|MockObject
     */
    private $writeInterface;

    /**
     * @var DriverInterface|MockObject
     */
    private $driverInterface;

    /**
     * @var AttachmentContentManagement
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->attachmentCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->attachmentContentFactory = $this->getMockBuilder(
            AttachmentContentInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->fileDriver = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->writeInterface = $this->getMockForAbstractClass(WriteInterface::class);
        $this->driverInterface = $this->getMockForAbstractClass(DriverInterface::class);

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            AttachmentContentManagement::class,
            [
                'attachmentCollectionFactory' => $this->attachmentCollectionFactory,
                'attachmentContentFactory' => $this->attachmentContentFactory,
                'fileDriver' => $this->fileDriver,
                'mediaDirectory' => $this->writeInterface,
            ]
        );
    }

    /**
     * Test get method.
     *
     * @return void
     */
    public function testGet()
    {
        $attachmentIds = [1];
        $attachmentCollection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $attachment = $this->getMockBuilder(
            CommentAttachmentInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $attachmentContent = $this->getMockBuilder(
            AttachmentContentInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attachmentCollectionFactory->expects($this->once())->method('create')->willReturn($attachmentCollection);
        $attachmentCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('attachment_id', ['in' => $attachmentIds])
            ->willReturnSelf();
        $attachmentCollection->expects($this->once())->method('getItems')->willReturn([$attachment]);
        $this->writeInterface->expects($this->once())
            ->method('getAbsolutePath')
            ->with(CommentManagement::ATTACHMENTS_FOLDER)
            ->willReturn('pub/media/negotiable_quotes_attachment/');
        $this->writeInterface->expects($this->once())
            ->method('getDriver')
            ->willReturn($this->driverInterface);
        $attachment->expects($this->once())->method('getFilePath')->willReturn('2/3/test.txt');
        $this->driverInterface->expects($this->once())
            ->method('fileGetContents')
            ->with('pub/media/negotiable_quotes_attachment/2/3/test.txt')
            ->willReturn('file content');
        $this->attachmentContentFactory->expects($this->once())
            ->method('create')
            ->willReturn($attachmentContent);
        $attachment->expects($this->once())->method('getFileType')->willReturn('text/plain');
        $attachment->expects($this->once())->method('getFileName')->willReturn('test.txt');
        $attachmentCollection->expects($this->once())->method('getAllIds')->willReturn([1]);

        $this->assertSame([$attachmentContent], $this->model->get($attachmentIds));
    }

    /**
     * Test get method if some attachments don't exist.
     *
     * @return void
     */
    public function testGetWithException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Cannot obtain the requested data. You must fix the errors listed below first.');
        $attachmentIds = [1, 2, 3];
        $attachmentCollection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $attachment = $this->getMockBuilder(
            CommentAttachmentInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $attachmentContent = $this->getMockBuilder(
            AttachmentContentInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attachmentCollectionFactory->expects($this->once())->method('create')->willReturn($attachmentCollection);
        $attachmentCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('attachment_id', ['in' => $attachmentIds])
            ->willReturnSelf();
        $attachmentCollection->expects($this->once())->method('getItems')->willReturn([$attachment]);
        $this->writeInterface->expects($this->once())
            ->method('getAbsolutePath')
            ->with(CommentManagement::ATTACHMENTS_FOLDER)
            ->willReturn('pub/media/negotiable_quotes_attachment/');
        $this->writeInterface->expects($this->once())
            ->method('getDriver')
            ->willReturn($this->driverInterface);
        $attachment->expects($this->once())->method('getFilePath')->willReturn('2/3/test.txt');
        $this->driverInterface->expects($this->once())
            ->method('fileGetContents')
            ->with('pub/media/negotiable_quotes_attachment/2/3/test.txt')
            ->willReturn('file content');
        $this->attachmentContentFactory->expects($this->once())
            ->method('create')
            ->willReturn($attachmentContent);
        $attachment->expects($this->once())->method('getFileType')->willReturn('text/plain');
        $attachment->expects($this->once())->method('getFileName')->willReturn('test.txt');
        $attachmentCollection->expects($this->once())->method('getAllIds')->willReturn([1]);

        $this->model->get($attachmentIds);
    }
}
