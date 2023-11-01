<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Action;

use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\CommentRepositoryInterface;
use Magento\PurchaseOrder\Model\Notification\Action\Recipient\ResolverInterface;
use Magento\PurchaseOrder\Model\Notification\ActionNotificationInterface;
use Magento\PurchaseOrder\Model\Notification\Email\ContentSource\CommentAction;
use Magento\PurchaseOrder\Model\Notification\Email\ContentSource\Factory as ContentSourceFactory;
use Magento\PurchaseOrder\Model\Notification\SenderInterface;

/**
 * Action to notify that comment has been added to Purchase Order.
 */
class CommentAdded implements ActionNotificationInterface
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
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * CommentAdded constructor.
     *
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param ResolverInterface $recipientResolver
     * @param SenderInterface $sender
     * @param ContentSourceFactory $contentSourceFactory
     * @param CommentRepositoryInterface $commentRepository
     * @param string $contentSourceType
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        ResolverInterface $recipientResolver,
        SenderInterface $sender,
        ContentSourceFactory $contentSourceFactory,
        CommentRepositoryInterface $commentRepository,
        string $contentSourceType = CommentAction::class
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->recipientResolver = $recipientResolver;
        $this->sender = $sender;
        $this->contentSourceFactory = $contentSourceFactory;
        $this->contentSourceType = $contentSourceType;
        $this->commentRepository = $commentRepository;
    }

    /**
     * @inheritDoc
     */
    public function notify(int $subjectEntityId)
    {
        $comment = $this->commentRepository->get($subjectEntityId);
        $purchaseOrder = $this->purchaseOrderRepository->getById($comment->getPurchaseOrderId());
        $recipientIds = $this->recipientResolver->getRecipients($purchaseOrder);
        foreach ($recipientIds as $recipientId) {
            if ((int)$comment->getCreatorId() === (int)$recipientId) { // do not notify author of new comment
                continue;
            }
            $contentSource = $this->contentSourceFactory->create(
                $this->contentSourceType,
                $purchaseOrder,
                (int)$recipientId,
                [
                    'comment' => $comment
                ]
            );
            $this->sender->send($contentSource);
        }
    }
}
