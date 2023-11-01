<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model\Expired;

use Magento\Store\Model\ScopeInterface;

/**
 * Class sends notification email to merchant about expired quote.
 */
class MerchantNotifier
{
    /**
     * @var string
     */
    private $configQuoteEmailNotificationsEnabled = 'sales_email/quote/enabled';

    /**
     * @var string
     */
    private $expireResetTemplate = 'sales_email/quote/expire_reset_template';

    /**
     * @var \Magento\NegotiableQuote\Model\EmailSenderInterface
     */
    private $emailSender;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface
     */
    private $negotiableQuoteManagement;

    /**
     * @param \Magento\NegotiableQuote\Model\EmailSenderInterface $emailSender
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement
     */
    public function __construct(
        \Magento\NegotiableQuote\Model\EmailSenderInterface $emailSender,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement
    ) {
        $this->emailSender = $emailSender;
        $this->scopeConfig = $scopeConfig;
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
    }

    /**
     * Send email notification.
     *
     * @param int $expiredQuoteId
     * @return void
     */
    public function sendNotification($expiredQuoteId): void
    {
        if ($this->scopeConfig->getValue(
            $this->configQuoteEmailNotificationsEnabled,
            ScopeInterface::SCOPE_STORE
        )) {
            $this->emailSender->sendChangeQuoteEmailToMerchant(
                $this->negotiableQuoteManagement->getNegotiableQuote($expiredQuoteId),
                $this->expireResetTemplate
            );
        }
    }
}
