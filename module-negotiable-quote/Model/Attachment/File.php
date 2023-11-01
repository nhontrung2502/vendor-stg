<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Model\Attachment;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\NegotiableQuote\Model\CommentManagement;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\NegotiableQuote\Api\Data\CommentAttachmentInterface;

/**
 * File download processing.
 */
class File
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * Media directory
     *
     * @var Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * @param FileDriver $fileDriver
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        FileDriver $fileDriver,
        FileFactory $fileFactory,
        Filesystem $filesystem
    ) {
        $this->fileFactory = $fileFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Get contents
     *
     * @param CommentAttachmentInterface $attachment
     * @return void
     * @throws Exception
     * @throws FileSystemException
     */
    public function downloadContents(CommentAttachmentInterface $attachment)
    {
        $attachmentPath = $this->mediaDirectory->getAbsolutePath(CommentManagement::ATTACHMENTS_FOLDER)
            . $attachment->getFilePath();
        $driver = $this->mediaDirectory->getDriver();

        $this->fileFactory->create(
            $attachment->getFileName(),
            $driver->fileGetContents($attachmentPath),
            DirectoryList::VAR_DIR,
            'application/octet-stream',
            $driver->stat($attachmentPath)['size'] ?? 0
        );
    }
}
