<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email\ContentSource;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;

/**
 * Content source for purchase order auto approved notification.
 */
class AutoApproveAction implements ContentSourceInterface
{
    /**
     * Path to config value for auto approved email template..
     */
    private const XML_PATH_TO_TEMPLATE =
        'sales_email/purchase_order_notification/purchase_order_auto_approved';

    /**
     * @var PurchaseOrderInterface
     */
    private $purchaseOrder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var int
     */
    private $recipientId;

    /**
     * AutoApproveAction constructor.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param CustomerRepositoryInterface $customerRepository
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param int $recipientId
     */
    public function __construct(
        PurchaseOrderInterface $purchaseOrder,
        CustomerRepositoryInterface $customerRepository,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        int $recipientId
    ) {
        $this->recipientId = $recipientId;
        $this->purchaseOrder = $purchaseOrder;
        $this->customerRepository = $customerRepository;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateVars(): DataObject
    {
        $recipient = $this->customerRepository->getById($this->recipientId);
        $negotiableQuote = $this->negotiableQuoteRepository->getById($this->purchaseOrder->getQuoteId());
        $data = [
            'recipient_email' => $recipient->getEmail(),
            'recipient_full_name' => $recipient->getFirstname() . ' ' . $recipient->getLastname(),
            'purchase_order_id' => $this->purchaseOrder->getEntityId(),
            'purchase_order_increment_id' => $this->purchaseOrder->getIncrementId(),
            'quote_id' => $negotiableQuote->getQuoteId(),
            'quote_name' => $negotiableQuote->getQuoteName()
        ];

        return new DataObject($data);
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
}
