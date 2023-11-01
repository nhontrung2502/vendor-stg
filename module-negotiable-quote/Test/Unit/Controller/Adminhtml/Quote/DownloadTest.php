<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Download;
use Magento\NegotiableQuote\Model\Attachment\DownloadProvider;
use Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests Magento\NegotiableQuote\Controller\Adminhtml\Quote\Download.
 */
class DownloadTest extends TestCase
{
    /**
     * @var DownloadProviderFactory|MockObject
     */
    protected $downloadProviderFactory;

    /**
     * @var Download|MockObject
     */
    protected $download;

    /**
     * @var DownloadProvider|MockObject
     */
    protected $downloadProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $requestId = 1;
        $this->downloadProvider = $this->getMockBuilder(DownloadProvider::class)
            ->addMethods(['canDownload'])
            ->onlyMethods(['getAttachmentContents'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->downloadProvider->expects($this->any())->method('getAttachmentContents')->willReturn('data');
        $this->downloadProviderFactory = $this->createPartialMock(
            DownloadProviderFactory::class,
            ['create']
        );
        $objectManager = new ObjectManager($this);
        $request = $this->createPartialMock(Http::class, ['getParam']);
        $request->expects($this->any())->method('getParam')->with('attachmentId')->willReturn($requestId);
        $this->downloadProviderFactory->expects($this->once())
            ->method('create')
            ->with(['attachmentId' => $requestId])
            ->willReturn($this->downloadProvider);
        $this->download = $objectManager->getObject(
            Download::class,
            [
                '_request' => $request,
                'downloadProviderFactory' => $this->downloadProviderFactory,
            ]
        );
    }

    /**
     * Test execute()
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->downloadProvider->expects($this->once())
            ->method('getAttachmentContents')
            ->willReturn('data');

        $this->download->execute();
    }

    /**
     * Test execute()
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->expectExceptionMessage('Attachment not found.');
        $this->downloadProvider->expects($this->once())
            ->method('getAttachmentContents')
            ->willThrowException(new NotFoundException(__('Attachment not found.')));

        $this->download->execute();
    }
}
