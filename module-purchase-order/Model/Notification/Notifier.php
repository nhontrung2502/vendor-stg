<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface as QueueMessagePublisher;
use Magento\PurchaseOrder\Model\Notification\Config\Provider\Factory;
use Magento\PurchaseOrder\Model\Notification\Notifier\QueueMessage;
use Magento\PurchaseOrder\Model\Notification\Notifier\QueueMessageFactory;

/**
 * Publishes notification messages.
 */
class Notifier implements NotifierInterface
{
    /**
     * @var QueueMessageFactory
     */
    private $queueMessageFactory;

    /**
     * @var QueueMessagePublisher
     */
    private $queueMessagePublisher;

    /**
     * @var array
     */
    private $notificationConfigProviders;

    /**
     * @var Config\Provider\Factory
     */
    private $configProviderFactory;

    /**
     * Notifier constructor.
     * @param QueueMessageFactory $queueMessageFactory
     * @param QueueMessagePublisher $queueMessagePublisher
     * @param Factory $configProviderFactory
     * @param array $notificationConfigProviders
     */
    public function __construct(
        QueueMessageFactory $queueMessageFactory,
        QueueMessagePublisher $queueMessagePublisher,
        Factory $configProviderFactory,
        array $notificationConfigProviders = []
    ) {
        $this->queueMessageFactory = $queueMessageFactory;
        $this->queueMessagePublisher = $queueMessagePublisher;
        $this->notificationConfigProviders = $notificationConfigProviders;
        $this->configProviderFactory = $configProviderFactory;
    }

    /**
     * @inheritDoc
     */
    public function notifyOnAction(
        int $subjectEntityId,
        string $actionNotificationClass
    ) : void {
        if (!isset($this->notificationConfigProviders[$actionNotificationClass])) {
            if (isset($this->notificationConfigProviders[ActionNotificationInterface::class])
                && in_array(ActionNotificationInterface::class, class_implements($actionNotificationClass))) {
                $notificationConfigProvider = $this->notificationConfigProviders[ActionNotificationInterface::class];
            } else {
                throw new LocalizedException(__('Configuration provider is not set for %1', $actionNotificationClass));
            }
        } else {
            $notificationConfigProvider = $this->notificationConfigProviders[$actionNotificationClass];
        }
        $configProvider = $this->configProviderFactory->create($notificationConfigProvider);
        if (!$configProvider->isEnabledForEntity($subjectEntityId)) {
            return;
        }
        /** @var QueueMessage $queueMessage */
        $queueMessage = $this->queueMessageFactory->create();
        $queueMessage->setActionClass($actionNotificationClass);
        $queueMessage->setSubjectEntityId($subjectEntityId);

        $this->queueMessagePublisher->publish(
            'purchaseorder.transactional.email',
            $queueMessage->getPublicationData()
        );
    }
}
