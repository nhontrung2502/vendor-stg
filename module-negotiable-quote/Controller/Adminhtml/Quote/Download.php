<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\NegotiableQuote\Model\Attachment\DownloadProvider;
use Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory;
use Psr\Log\LoggerInterface;

/**
 * Handles downloading of quote attachments in admin area.
 */
class Download extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_NegotiableQuote::view_quotes';

    /**
     * Download provider factory
     *
     * @var DownloadProviderFactory
     */
    private $downloadProviderFactory;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param DownloadProviderFactory $downloadProviderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        DownloadProviderFactory $downloadProviderFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->downloadProviderFactory = $downloadProviderFactory;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @return void
     * @throws NotFoundException
     */
    public function execute(): void
    {
        $attachmentId = $this->getRequest()->getParam('attachmentId');
        /** @var DownloadProvider $downloadProvider */
        $downloadProvider = $this->downloadProviderFactory->create(['attachmentId' => $attachmentId]);

        try {
            $downloadProvider->getAttachmentContents();
        } catch (\Throwable $e) {
            $this->logger->critical($e);
            throw new NotFoundException(__('Attachment not found.'));
        }
    }
}
