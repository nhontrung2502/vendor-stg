<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;
use Magento\PurchaseOrder\Model\Notification\SenderInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends email notifications.
 */
class Sender implements SenderInterface
{
    /**
     * Paths to config values for sender information.
     */
    const XML_PATH_STORE_NAME = 'trans_email/ident_general/name';
    const XML_PATH_STORE_EMAIL = 'trans_email/ident_general/email';

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Sender constructor.
     *
     * @param TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Escaper $escaper
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Escaper $escaper,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function send(ContentSourceInterface $contentSource): void
    {
        $this->sendNotification(
            $contentSource->getTemplateConfigPath(),
            $contentSource->getTemplateVars(),
            $contentSource->getStoreId()
        );
    }

    /**
     * Send notification.
     *
     * @param string $templateId
     * @param DataObject $vars
     * @param int $storeId
     * @return void
     */
    private function sendNotification(string $templateId, DataObject $vars, int $storeId) : void
    {
        try {
            $this->inlineTranslation->suspend();

            $sender = [
                'name' => $this->escaper->escapeHtml($this->getSenderName($storeId)),
                'email' => $this->escaper->escapeHtml($this->getSenderEmail($storeId)),
            ];

            $this->transportBuilder
                ->setTemplateIdentifier($this->getConfigValue($templateId, $storeId))
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars(['data' => $vars])
                ->setFromByScope($sender, $storeId)
                ->addTo($vars->getRecipientEmail(), $vars->getRecipientFullName());

            if ($vars->hasData('reply_email') && $vars->hasData('reply_name')) {
                $this->transportBuilder->setReplyTo($vars->getReplyEmail(), $vars->getReplyName());
            }

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Get sender email.
     *
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSenderEmail(int $storeId): string
    {
        return $this->getConfigValue(self::XML_PATH_STORE_EMAIL, $storeId);
    }

    /**
     * Get sender name.
     *
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSenderName(int $storeId): string
    {
        return $this->getConfigValue(self::XML_PATH_STORE_NAME, $storeId);
    }

    /**
     * Get config value from store config.
     *
     * @param string $path
     * @param int $storeId
     * @return string|null
     */
    private function getConfigValue($path, int $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
