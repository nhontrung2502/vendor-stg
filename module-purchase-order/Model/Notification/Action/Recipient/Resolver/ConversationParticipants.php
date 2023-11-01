<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Action\Recipient\Resolver;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Comment;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\Notification\Action\Recipient\ResolverInterface;

/**
 * Resolves comment authors in purchase order and purchase order creator.
 */
class ConversationParticipants implements ResolverInterface
{
    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * ConversationParticipants constructor.
     * @param CommentManagement $commentManagement
     */
    public function __construct(
        CommentManagement $commentManagement
    ) {
        $this->commentManagement = $commentManagement;
    }

    /**
     * @inheritDoc
     */
    public function getRecipients(PurchaseOrderInterface $purchaseOrder): array
    {
        $recipients = [];
        $commentsCollection = $this->commentManagement->getPurchaseOrderComments($purchaseOrder->getEntityId());
        /** @var Comment $commentItem */
        foreach ($commentsCollection as $commentItem) {
            $recipients[] = $commentItem->getCreatorId();
        }
        $recipients[] = $purchaseOrder->getCreatorId();
        return array_unique($recipients);
    }
}
