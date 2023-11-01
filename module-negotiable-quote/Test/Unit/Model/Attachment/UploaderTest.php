<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Attachment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Attachment\Uploader;
use Magento\NegotiableQuote\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Uploader.
 */
class UploaderTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $negotiableQuoteConfig;

    /**
     * @var Uploader|MockObject
     */
    protected $uploader;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setupFiles();
        $this->negotiableQuoteConfig = $this->createMock(
            Config::class
        );
        $this->negotiableQuoteConfig->expects($this->any())->method('getMaxFileSize')->willReturn(1);
        $this->negotiableQuoteConfig->expects($this->any())
            ->method('getAllowedExtensions')
            ->willReturn('doc,txt,jpg,png');
        $this->objectManager = new ObjectManager($this);
        $class = new \ReflectionObject($this);
        $this->filename = $class->getFilename();
        $this->setupTestData();
    }

    /**
     * Setup global variable $_FILES.
     *
     * @param int $fileSize [optional]
     * @param string $fileName [optional]
     * @param string $fileType [optional]
     * @return void
     */
    private function setupFiles(
        $fileSize = 1234567890123,
        $fileName = 'sample-file.doc',
        $fileType = 'application/msword'
    ) {
        $_FILES = [
            'file[0]' => [
                'name' => $fileName,
                'type' => $fileType,
                'tmp_name' => $this->filename,
                'error' => 0,
                'size' => $fileSize,
            ]
        ];
    }

    /**
     * Setup config.
     *
     * @param int $fileSize [optional]
     * @param string $fileName [optional]
     * @param string $fileType [optional]
     * @return void
     */
    private function setupTestData(
        $fileSize = 1234567890123,
        $fileName = 'sample-file.doc',
        $fileType = 'application/msword'
    ) {
        $this->uploader = $this->objectManager->getObject(
            Uploader::class,
            [
                'negotiableQuoteConfig' => $this->negotiableQuoteConfig,
                'fileId' => [
                    'name' => $fileName,
                    'type' => $fileType,
                    'tmp_name' => $this->filename,
                    'error' => 0,
                    'size' => $fileSize,
                ]
            ]
        );
        $this->uploader->processFileAttributes([
            'name' => $fileName,
            'type' => $fileType,
            'tmp_name' => $this->filename,
            'error' => 0,
            'size' => $fileSize,
        ]);
    }

    /**
     * Test validateSize().
     *
     * @return void
     */
    public function testValidateSizeFailed()
    {
        $this->assertFalse($this->uploader->validateSize());
    }

    /**
     * Test validateSize() - failed.
     *
     * @return void
     */
    public function testValidateSizePassed()
    {
        $this->setupFiles(123456);
        $this->setupTestData(123456);
        $this->assertTrue($this->uploader->validateSize());
    }

    /**
     * Test validateNameLength.
     *
     * @return void
     */
    public function testValidateNameLengthPassed()
    {
        $this->assertTrue($this->uploader->validateNameLength());
    }

    /**
     * Test validateNameLength - failed.
     *
     * @return void
     */
    public function testValidateNameLengthFailed()
    {
        $this->setupFiles(1234567890123, 'sample-file-with-veeeeery-looooong-title.doc');
        $this->setupTestData(1234567890123, 'sample-file-with-veeeeery-looooong-title.doc');
        $this->assertFalse($this->uploader->validateNameLength());
    }

    /**
     * Test checkAllowedExtension() - failed.
     *
     * @return void
     */
    public function testCheckAllowedExtensionFailed()
    {
        $this->setupFiles(1234567890123, 'sample-file.zip', 'application/x-zip-compressed');
        $this->setupTestData(1234567890123, 'sample-file.zip', 'application/x-zip-compressed');
        $this->assertFalse($this->uploader->checkAllowedExtension($this->uploader->getFileExtension()));
    }

    /**
     * TearDown.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($_FILES['file[0]']);
    }
}
