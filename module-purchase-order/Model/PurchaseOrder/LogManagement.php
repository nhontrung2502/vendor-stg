<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\PurchaseOrder;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;
use Magento\PurchaseOrder\Model\PurchaseOrderLogFactory;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrderLog\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Implementation for log management.
 */
class LogManagement implements LogManagementInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var PurchaseOrderLogFactory
     */
    private $purchaseOrderLogFactory;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var array
     */
    private $actions;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * PurchaseOrderLogManagement constructor.
     * @param CollectionFactory $collectionFactory
     * @param PurchaseOrderLogFactory $purchaseOrderLogFactory
     * @param UserContextInterface $userContext
     * @param Json $jsonSerializer
     * @param array $actions
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        PurchaseOrderLogFactory $purchaseOrderLogFactory,
        UserContextInterface $userContext,
        Json $jsonSerializer,
        array $actions = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->purchaseOrderLogFactory = $purchaseOrderLogFactory;
        $this->userContext = $userContext;
        $this->actions = $actions;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritDoc
     */
    public function getPurchaseOrderLogs($purchaseOrderId)
    {
        $logCollection = $this->collectionFactory->create();
        $logCollection->addFieldToFilter('request_id', $purchaseOrderId)->setOrder('id', 'DESC');
        return $logCollection->getItems();
    }

    /**
     * Log action on purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param string $action
     * @param array $params
     * @param int|null $userId
     * @return void
     * @throws CouldNotSaveException
     */
    public function logAction(
        PurchaseOrderInterface $purchaseOrder,
        string $action,
        array $params = [],
        $userId = null
    ) {
        $logEntry = [
            'action' => $action,
            'params' => $params,
        ];
        $logEntry = $this->jsonSerializer->serialize($logEntry);
        $this->savePurchaseOrderLog($purchaseOrder, $logEntry, $action, $userId);
    }

    /**
     * Save purchase order log.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param string $logEntry
     * @param string $actionType
     * @param string|null $ownerId
     * @throws CouldNotSaveException
     */
    private function savePurchaseOrderLog(
        PurchaseOrderInterface $purchaseOrder,
        string $logEntry,
        string $actionType,
        $ownerId = null
    ) {
        $purchaseOrderLog = $this->purchaseOrderLogFactory->create();

        $purchaseOrderLog->addData([
            'request_id' => $purchaseOrder->getEntityId(),
            'request_log' => $logEntry,
            'activity_type' => $actionType,
            'owner_id' => $ownerId,
        ]);

        try {
            $purchaseOrderLog->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        }
    }
}
