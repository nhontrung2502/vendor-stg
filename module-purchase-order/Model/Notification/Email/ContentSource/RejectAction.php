<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email\ContentSource;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;
use Magento\PurchaseOrder\Model\PurchaseOrderLog;
use Magento\PurchaseOrder\Model\PurchaseOrderLogRepositoryInterface;

/**
 * Content source for rejection notification.
 */
class RejectAction implements ContentSourceInterface
{
    /**
     * Path to config value for template.
     */
    private const XML_PATH_TO_TEMPLATE = 'sales_email/purchase_order_notification/purchase_order_rejected';

    /**
     * @var PurchaseOrderInterface
     */
    private $purchaseOrder;

    /**
     * @var int
     */
    private $recipientId;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var PurchaseOrderLogRepositoryInterface
     */
    private $purchaseOrderLogRepository;

    /**
     * RejectAction constructor.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param CustomerRepositoryInterface $customerRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PurchaseOrderLogRepositoryInterface $purchaseOrderLogRepository
     * @param int $recipientId
     */
    public function __construct(
        PurchaseOrderInterface $purchaseOrder,
        CustomerRepositoryInterface $customerRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PurchaseOrderLogRepositoryInterface $purchaseOrderLogRepository,
        int $recipientId
    ) {
        $this->recipientId = $recipientId;
        $this->customerRepository = $customerRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->purchaseOrder = $purchaseOrder;
        $this->purchaseOrderLogRepository = $purchaseOrderLogRepository;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateVars(): DataObject
    {
        $recipientCustomer = $this->customerRepository->getById($this->recipientId);

        $idFilter = $this->filterBuilder
            ->setField(PurchaseOrderLogInterface::REQUEST_ID)
            ->setConditionType('eq')
            ->setValue($this->purchaseOrder->getEntityId())
            ->create();

        $actionFilter = $this->filterBuilder
            ->setField(PurchaseOrderLogInterface::ACTIVITY_TYPE)
            ->setConditionType('eq')
            ->setValue('reject')
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->addFilters([$idFilter, $actionFilter])->create();
        $logEntries = array_values($this->purchaseOrderLogRepository->getList($searchCriteria)->getItems());

        /** @var PurchaseOrderLog $logEntry */
        $logEntry = $logEntries[count($logEntries) - 1];

        $rejecterId = $logEntry->getOwnerId();
        $rejecterCustomer = $this->customerRepository->getById($rejecterId);

        $data = [
            'purchase_order_increment_id' => $this->purchaseOrder->getIncrementId(),
            'purchase_order_id' => $this->purchaseOrder->getEntityId(),
            'recipient_full_name' => $recipientCustomer->getFirstname() . ' ' . $recipientCustomer->getLastname(),
            'recipient_email' => $recipientCustomer->getEmail(),
            'rejecter_full_name' => $rejecterCustomer->getFirstname() . ' ' . $rejecterCustomer->getLastname(),
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
        return (int) $this->purchaseOrder->getSnapshotQuote()->getStoreId();
    }
}
