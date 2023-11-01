<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Filesystem\File\ReadInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\Data\AttachmentContentInterface;
use Magento\NegotiableQuote\Api\Data\AttachmentContentInterfaceFactory;
use Magento\NegotiableQuote\Controller\FileProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for FileProcessor.
 */
class FileProcessorTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var AttachmentContentInterfaceFactory|MockObject
     */
    private $attachmentFactory;

    /**
     * @var ReadFactory|MockObject
     */
    private $readFactory;

    /**
     * @var FileProcessor
     */
    private $fileProcessor;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFiles'])
            ->getMockForAbstractClass();
        $this->attachmentFactory = $this
            ->getMockBuilder(AttachmentContentInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->readFactory = $this->getMockBuilder(ReadFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->fileProcessor = $objectManagerHelper->getObject(
            FileProcessor::class,
            [
                'request' => $this->request,
                'attachmentFactory' => $this->attachmentFactory,
                'readFactory' => $this->readFactory,
            ]
        );
    }

    /**
     * Test for getFiles().
     *
     * @return void
     */
    public function testGetFiles()
    {
        $fileContent = 'file_content';
        $filesData = [
            [
                'tmp_name' => 'file_name_temp.txt',
                'name' => 'file_name.txt',
                'size' => 10,
                'type' => 'txt'
            ]
        ];
        $this->request->expects($this->atLeastOnce())->method('getFiles')->with('files')->willReturn($filesData);
        $fileReader = $this->getMockBuilder(ReadInterface::class)
            ->disableArgumentCloning()
            ->getMockForAbstractClass();
        $fileReader->expects($this->atLeastOnce())->method('read')->willReturn($fileContent);
        $this->readFactory->expects($this->atLeastOnce())->method('create')->willReturn($fileReader);
        $result = [
            'data' => [
                AttachmentContentInterface::BASE64_ENCODED_DATA => base64_encode($fileContent),
                AttachmentContentInterface::TYPE => $filesData[0]['type'],
                AttachmentContentInterface::NAME => $filesData[0]['name'],
            ]
        ];
        $this->attachmentFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);

        $this->assertEquals([$result], $this->fileProcessor->getFiles());
    }
}
