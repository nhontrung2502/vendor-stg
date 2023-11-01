<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Action;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Notification\Action\Recipient\ResolverInterface;
use Magento\PurchaseOrder\Model\Notification\ActionNotificationInterface;
use Magento\PurchaseOrder\Model\Notification\Email\ContentSource\ApprovalRequiredAction;
use Magento\PurchaseOrder\Model\Notification\Email\ContentSource\Factory as ContentSourceFactory;
use Magento\PurchaseOrder\Model\Notification\SenderInterface;

/**
 * Action to notify that approval is required for purchase order.
 */
class ApprovalRequired implements ActionNotificationInterface
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var ResolverInterface
     */
    private $recipientResolver;

    /**
     * @var SenderInterface
     */
    private $sender;

    /**
     * @var ContentSourceFactory
     */
    private $contentSourceFactory;

    /**
     * @var string
     */
    private $contentSourceType;

    /**
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param ResolverInterface $recipientResolver
     * @param SenderInterface $sender
     * @param ContentSourceFactory $contentSourceFactory
     * @param string $contentSourceType
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        ResolverInterface $recipientResolver,
        SenderInterface $sender,
        ContentSourceFactory $contentSourceFactory,
        string $contentSourceType = ApprovalRequiredAction::class
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->recipientResolver = $recipientResolver;
        $this->sender = $sender;
        $this->contentSourceFactory = $contentSourceFactory;
        $this->contentSourceType = $contentSourceType;
    }

    /**
     * @inheritDoc
     */
    public function notify(int $subjectEntityId)
    {
        $purchaseOrder = $this->purchaseOrderRepository->getById($subjectEntityId);

        if ($purchaseOrder->getStatus() === PurchaseOrderInterface::STATUS_APPROVED) {
            return;
        }

        $recipientIds = $this->recipientResolver->getRecipients($purchaseOrder);

        foreach ($recipientIds as $recipientId) {
            $contentSource = $this->contentSourceFactory->create(
                $this->contentSourceType,
                $purchaseOrder,
                (int)$recipientId
            );
            $this->sender->send($contentSource);
        }
    }
}
