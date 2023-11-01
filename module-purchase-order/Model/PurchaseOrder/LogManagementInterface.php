<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\PurchaseOrder;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;

/**
 * Responsible for storing and fetching logs for purchase order.
 *
 * @api
 */
interface LogManagementInterface
{
    /**
     * Get purchase order logs.
     *
     * @param int $purchaseOrderId
     * @return PurchaseOrderLogInterface[]
     */
    public function getPurchaseOrderLogs($purchaseOrderId);

    /**
     * Log action on purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param string $action
     * @param array $params
     * @param int|null $userId
     * @return void
     */
    public function logAction(
        PurchaseOrderInterface $purchaseOrder,
        string $action,
        array $params = [],
        $userId = null
    );
}
