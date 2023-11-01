<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Controller\Quote;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\NegotiableQuote\Model\Attachment\DownloadProvider;
use Magento\NegotiableQuote\Model\Attachment\DownloadProviderFactory;
use Psr\Log\LoggerInterface;

/**
 * Handles downloading of quote attachments on storefront.
 */
class Download extends Action implements HttpGetActionInterface
{
    /**
     * Download handler factory
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
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param Context $context
     * @param DownloadProviderFactory $downloadProviderFactory
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        DownloadProviderFactory $downloadProviderFactory,
        LoggerInterface $logger,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->downloadProviderFactory = $downloadProviderFactory;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute
     *
     * @return ResponseInterface|void
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addNoticeMessage(__('Please sign in to download.'));

            return $this->_redirect(CustomerUrl::ROUTE_ACCOUNT_LOGIN);
        }

        $attachmentId = $this->getRequest()->getParam('attachmentId');
        /** @var DownloadProvider $downloadProvider */
        $downloadProvider = $this->downloadProviderFactory->create(['attachmentId' => $attachmentId]);
        $this->getResponse()->setNoCacheHeaders();

        try {
            $downloadProvider->getAttachmentContents();
        } catch (\Throwable $e) {
            $this->logger->critical($e);
            $this->messageManager->addNoticeMessage(__('We can\'t find the file you requested.'));

            return $this->_redirect('negotiable_quote/quote');
        }
    }
}
