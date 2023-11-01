<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Model\CommentFactory;
use Magento\PurchaseOrder\Api\Data\CommentInterfaceFactory;
use Magento\PurchaseOrder\Model\Notification\Action\CommentAdded;
use Magento\PurchaseOrder\Model\Notification\NotifierInterface;
use Magento\PurchaseOrder\Model\ResourceModel\Comment\Collection as CommentCollection;
use Magento\PurchaseOrder\Model\ResourceModel\Comment\CollectionFactory as CommentCollectionFactory;

/**
 * Class manages purchase order comments.
 */
class CommentManagement
{
    /**
     * @var CommentCollectionFactory
     */
    private $commentCollectionFactory;

    /**
     * @var CommentFactory
     */
    private $commentFactory;

    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * @param CommentInterfaceFactory $commentFactory
     * @param CommentCollectionFactory $commentCollectionFactory
     * @param NotifierInterface $notifier
     */
    public function __construct(
        CommentInterfaceFactory $commentFactory,
        CommentCollectionFactory $commentCollectionFactory,
        NotifierInterface $notifier
    ) {
        $this->commentFactory = $commentFactory;
        $this->commentCollectionFactory = $commentCollectionFactory;
        $this->notifier = $notifier;
    }

    /**
     * Add comment to purchase order.
     *
     * @param int $purchaseOrderId
     * @param int $creatorId
     * @param string $commentText
     * @throws LocalizedException
     */
    public function addComment(
        $purchaseOrderId,
        $creatorId,
        $commentText
    ) {
        $comment = $this->commentFactory->create();
        $comment
            ->setCreatorId($creatorId)
            ->setPurchaseOrderId($purchaseOrderId)
            ->setComment($commentText);

        $comment->save();

        $this->notifier->notifyOnAction($comment->getEntityId(), CommentAdded::class);
    }

    /**
     * Get purchase order comments by purchase order id.
     *
     * @param int $purchaseOrderId
     * @return CommentCollection
     */
    public function getPurchaseOrderComments($purchaseOrderId)
    {
        $commentCollection = $this->commentCollectionFactory->create();
        $commentCollection->addFieldToFilter('purchase_order_id', $purchaseOrderId)->setOrder('created_at', 'DESC');
        return $commentCollection;
    }
}
