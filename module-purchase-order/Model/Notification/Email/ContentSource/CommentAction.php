<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email\ContentSource;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\PurchaseOrder\Api\Data\CommentInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;

/**
 * Content source for comment addition notification.
 */
class CommentAction implements ContentSourceInterface
{
    /**
     * Path to config value for template.
     */
    private const XML_PATH_TO_TEMPLATE = 'sales_email/purchase_order_notification/purchase_order_comment_added';

    /**
     * @var PurchaseOrderInterface
     */
    private $purchaseOrder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var int
     */
    private $recipientId;

    /**
     * @var array
     */
    private $data;

    /**
     * CommentAction constructor.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param CustomerRepositoryInterface $customerRepository
     * @param int $recipientId
     * @param array $data
     */
    public function __construct(
        PurchaseOrderInterface $purchaseOrder,
        CustomerRepositoryInterface $customerRepository,
        int $recipientId,
        array $data = []
    ) {
        $this->recipientId = $recipientId;
        $this->purchaseOrder = $purchaseOrder;
        $this->customerRepository = $customerRepository;
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateVars(): DataObject
    {
        $recipient = $this->customerRepository->getById($this->recipientId);

        $comment = $this->getComment();

        $commenter = $this->customerRepository->getById($comment->getCreatorId());

        $data = [
            'comment' => $comment->getComment(),
            'recipient_email' => $recipient->getEmail(),
            'recipient_full_name' => $recipient->getFirstname() . ' ' . $recipient->getLastname(),
            'commenter_full_name' => $commenter->getFirstname() . ' ' . $commenter->getLastname(),
            'purchase_order_id' => $this->purchaseOrder->getEntityId(),
            'purchase_order_increment_id' => $this->purchaseOrder->getIncrementId(),
        ];

        $emailData = new DataObject($data);

        return $emailData;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateConfigPath(): string
    {
        return self::XML_PATH_TO_TEMPLATE;
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): int
    {
        return (int)$this->purchaseOrder->getSnapshotQuote()->getStoreId();
    }

    /**
     * Get comment object.
     *
     * @return CommentInterface
     */
    private function getComment() : CommentInterface
    {
        return $this->data['comment'];
    }
}
