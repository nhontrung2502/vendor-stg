<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Attachment;

use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\NegotiableQuote\Api\Data\CommentAttachmentInterface;
use Magento\NegotiableQuote\Model\Attachment\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

/**
 * @see File
 */
class FileTest extends TestCase
{
    /**
     * @var File|MockObject
     */
    private $file;

    /**
     * @var FileFactory|MockObject
     */
    private $fileFactory;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $directory = $this->getMockForAbstractClass(Filesystem\Directory\WriteInterface::class);
        $driverMock = $this->getMockForAbstractClass(Filesystem\DriverInterface::class);

        $directory->method('getDriver')
            ->willReturn($driverMock);

        $this->filesystem->method('getDirectoryWrite')
            ->willReturn($directory);

        $this->file = new File(
            $this->createMock(FileDriver::class),
            $this->fileFactory,
            $this->filesystem
        );
    }

    /**
     * @throws FileSystemException
     */
    public function testGetContents(): void
    {
        $comment = $this->getMockForAbstractClass(CommentAttachmentInterface::class);

        $this->file->downloadContents($comment);
    }

    /**
     * @throws FileSystemException
     */
    public function testGetContentsWithException(): void
    {
        $this->expectException('Exception');

        $comment = $this->getMockForAbstractClass(CommentAttachmentInterface::class);
        $exceptionMessage = 'An error occurred.';
        $exception = new \Exception($exceptionMessage);
        $this->fileFactory
            ->method('create')
            ->willThrowException($exception);

        $this->file->downloadContents($comment);
    }

    /**
     * @throws FileSystemException
     */
    public function testGetContentsWithInvalidArgumentException(): void
    {
        $this->expectException('InvalidArgumentException');
        $comment = $this->getMockForAbstractClass(CommentAttachmentInterface::class);
        $exceptionMessage = 'Invalid arguments. Keys \'type\' and \'value\' are required.';
        $exception = new \InvalidArgumentException($exceptionMessage);

        $this->fileFactory->method('create')
            ->willThrowException($exception);

        $this->file->downloadContents($comment);
    }
}
