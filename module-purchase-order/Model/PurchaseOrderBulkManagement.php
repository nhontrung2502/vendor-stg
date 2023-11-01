<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Processor\ApprovalProcessorInterface;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder\Collection;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder\CollectionFactory;
use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorLocator;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class manages bulk purchase orders.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PurchaseOrderBulkManagement
{
    /**#@+
     * Constant defined for key of array, makes typos less likely
     */
    const FAILED_KEY = 'failed';

    /**
     * @var CollectionFactory
     */
    private $purchaseOrderCollectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var ApprovalProcessorInterface
     */
    private $purchaseOrderApprovalsProcessor;

    /**
     * @var PurchaseOrderManagement
     */
    private $purchaseOrderManagement;

    /**
     * @var Validator\ActionReady\ValidatorLocator
     */
    private $validatorLocator;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * PurchaseOrderBulkManagement constructor.
     *
     * @param CollectionFactory $purchaseOrderCollectionFactory
     * @param Filter $filter
     * @param ApprovalProcessorInterface $purchaseOrderApprovalsProcessor
     * @param PurchaseOrderManagement $purchaseOrderManagement
     * @param ValidatorLocator $validatorLocator
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CollectionFactory $purchaseOrderCollectionFactory,
        Filter $filter,
        ApprovalProcessorInterface $purchaseOrderApprovalsProcessor,
        PurchaseOrderManagement $purchaseOrderManagement,
        ValidatorLocator $validatorLocator,
        CompanyContext $companyContext,
        Authorization $authorization
    ) {
        $this->purchaseOrderCollectionFactory = $purchaseOrderCollectionFactory;
        $this->filter = $filter;
        $this->purchaseOrderApprovalsProcessor = $purchaseOrderApprovalsProcessor;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->validatorLocator = $validatorLocator;
        $this->companyContext = $companyContext;
        $this->authorization = $authorization;
    }

    /**
     * Approve purchase order
     *
     * @param int $approverCustomerId
     *
     * @return array
     * @throws LocalizedException
     */
    public function approvePurchaseOrders(int $approverCustomerId): array
    {
        /** @var Collection $collection */
        $collection = $this->filter->getCollection($this->purchaseOrderCollectionFactory->create());
        $collection->addFieldToFilter('status', ['in'=> [
            PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED,
            PurchaseOrderInterface::STATUS_PENDING
        ]]);
        $orders = [];

        foreach ($collection as $item) {
            try {
                $this->validateCanBeApproved($item);
                $this->purchaseOrderApprovalsProcessor->processApproval($item, $approverCustomerId);
                $orders[PurchaseOrderInterface::STATUS_APPROVED][] = $item->getIncrementId();
            } catch (LocalizedException $e) {
                $orders[self::FAILED_KEY][] = $item->getIncrementId();
            }
        }

        return $orders;
    }

    /**
     * Reject purchase orders
     *
     * @param int $approverCustomerId
     *
     * @return array
     * @throws LocalizedException
     */
    public function rejectPurchaseOrders(int $approverCustomerId): array
    {
        /** @var Collection $collection */
        $collection = $this->filter->getCollection($this->purchaseOrderCollectionFactory->create());
        $collection->addFieldToFilter('status', ['in'=> [
            PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED,
            PurchaseOrderInterface::STATUS_PENDING
        ]]);
        $orders = [];

        foreach ($collection as $item) {
            try {
                $this->validateCanBeRejected($item);
                $this->purchaseOrderManagement->rejectPurchaseOrder($item, $approverCustomerId);
                $orders[PurchaseOrderInterface::STATUS_REJECTED][] = $item->getIncrementId();
            } catch (LocalizedException $e) {
                $orders[self::FAILED_KEY][] = $item->getIncrementId();
            }
        }

        return $orders;
    }

    /**
     * Determine if a purchase order can be approved.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     *
     * @return void
     * @throws PurchaseOrderValidationException
     */
    private function validateCanBeApproved(PurchaseOrderInterface $purchaseOrder): void
    {
        if (!$this->isAllowedAction('approve', $purchaseOrder)) {
            throw new PurchaseOrderValidationException(
                __(
                    "Purchase order %1 couldn't be approved.",
                    $purchaseOrder->getIncrementId()
                )
            );
        }
    }

    /**
     * Determine if a purchase order can be rejected.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     *
     * @return void
     * @throws PurchaseOrderValidationException
     */
    private function validateCanBeRejected(PurchaseOrderInterface $purchaseOrder): void
    {
        if (!$this->isAllowedAction('reject', $purchaseOrder)) {
            throw new PurchaseOrderValidationException(
                __(
                    "Purchase order %1 couldn't be rejected.",
                    $purchaseOrder->getIncrementId()
                )
            );
        }
    }

    /**
     * Check is action allowed on a purchase order.
     *
     * @param string $action
     * @param PurchaseOrderInterface $purchaseOrder
     *
     * @return bool
     */
    private function isAllowedAction(string $action, PurchaseOrderInterface $purchaseOrder) : bool
    {
        return $this->validatorLocator->getValidator($action)->validate($purchaseOrder)
            && $this->authorization->isAllowed($action, $purchaseOrder);
    }
}
