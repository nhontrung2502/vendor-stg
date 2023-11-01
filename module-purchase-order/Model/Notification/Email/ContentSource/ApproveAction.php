<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email\ContentSource;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address\Config as CustomerAddressConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategyInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Content source for approval request notification.
 */
class ApproveAction implements ContentSourceInterface
{
    /**
     * Path to config value for template.
     */
    private const XML_PATH_TO_TEMPLATE = 'sales_email/purchase_order_notification/purchase_order_approved';

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
     * ApproveAction constructor.
     *
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
        $approverCustomer = $this->customerRepository->getById(current($this->purchaseOrder->getApprovedBy()));
        $buyerCustomer = $this->customerRepository->getById($this->purchaseOrder->getCreatorId());
        $data = [
            'recipient_email' => $buyerCustomer->getEmail(),
            'recipient_full_name' => $buyerCustomer->getFirstname() . ' ' . $buyerCustomer->getLastname(),
            'approver_full_name' => $approverCustomer->getFirstname() . ' ' . $approverCustomer->getLastname(),
            'buyer_full_name' => $buyerCustomer->getFirstname() . ' ' . $buyerCustomer->getLastname(),
            'purchase_order_id' => $this->purchaseOrder->getEntityId(),
            'purchase_order_increment_id' => $this->purchaseOrder->getIncrementId(),
        ];
        $emailData = new \Magento\Framework\DataObject();
        $emailData->setData($data);
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
}
