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
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategyInterface;

/**
 * Content source for approval request notification if payment details are required.
 */
class ApproveAndPaymentDetailsRequiredAction implements ContentSourceInterface
{
    /**
     * Path to config value for template.
     */
    private const XML_PATH_TO_TEMPLATE =
        'sales_email/purchase_order_notification/purchase_order_approved_payment_details';

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
     * @var DeferredPaymentStrategyInterface|null
     */
    private $deferredPaymentStrategy;

    /**
     * ApproveAndPaymentDetailsRequiredAction constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int $recipientId
     * @param DeferredPaymentStrategyInterface $deferredPaymentStrategy
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        PurchaseOrderInterface $purchaseOrder,
        int $recipientId,
        DeferredPaymentStrategyInterface $deferredPaymentStrategy
    ) {
        $this->customerRepository = $customerRepository;
        $this->purchaseOrder = $purchaseOrder;
        $this->recipientId = $recipientId;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateVars(): DataObject
    {
        $approver = $this->customerRepository->getById(current($this->purchaseOrder->getApprovedBy()));
        $buyer = $this->customerRepository->getById($this->purchaseOrder->getCreatorId());
        $emailDataObject = new DataObject();

        $emailData = [
            'approver_full_name' => $approver->getFirstname() . ' ' . $approver->getLastname(),
            'buyer_full_name' => $buyer->getFirstname() . ' ' . $buyer->getLastname(),
            'recipient_email' => $buyer->getEmail(),
            'recipient_full_name' => $buyer->getFirstname() . ' ' . $buyer->getLastname(),
            'purchase_order_id' => $this->purchaseOrder->getEntityId(),
            'purchase_order_increment_id' => $this->purchaseOrder->getIncrementId()
        ];
        $emailDataObject->setData($emailData);

        return $emailDataObject;
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
