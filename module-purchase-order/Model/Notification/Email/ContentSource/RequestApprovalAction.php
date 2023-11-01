<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email\ContentSource;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;

/**
 * Content source for approval request notification.
 */
class RequestApprovalAction implements ContentSourceInterface
{
    /**
     * Path to config value for template.
     */
    private const XML_PATH_TO_TEMPLATE = 'sales_email/purchase_order_notification/purchase_order_approval_request';

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
     * @param PurchaseOrderInterface $purchaseOrder
     * @param CustomerRepositoryInterface $customerRepository
     * @param int $recipientId
     */
    public function __construct(
        PurchaseOrderInterface $purchaseOrder,
        CustomerRepositoryInterface $customerRepository,
        int $recipientId
    ) {
        $this->recipientId = $recipientId;
        $this->purchaseOrder = $purchaseOrder;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateVars(): DataObject
    {
        $recipient = $this->customerRepository->getById($this->recipientId);
        $buyerCustomer = $this->customerRepository->getById($this->purchaseOrder->getCreatorId());
        $data = [
            'recipient_email' => $recipient->getEmail(),
            'recipient_full_name' => $recipient->getFirstname() . ' ' . $recipient->getLastname(),
            'buyer_full_name' => $buyerCustomer->getFirstname() . ' ' . $buyerCustomer->getLastname(),
            'purchase_order_id' => $this->purchaseOrder->getEntityId(),
            'purchase_order_increment_id' => $this->purchaseOrder->getIncrementId(),
        ];
        return new \Magento\Framework\DataObject($data);
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
        return (int)$this->purchaseOrder->getSnapshotQuote()
            ->getStoreId();
    }
}
