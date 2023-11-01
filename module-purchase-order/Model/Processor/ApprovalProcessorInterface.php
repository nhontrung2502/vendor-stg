<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Processor;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Processor\Exception\ApprovalProcessorException;

/**
 * Processor interface for purchase order approvals.
 *
 * @api
 */
interface ApprovalProcessorInterface
{
    /**
     * Process purchase order approvals.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int $customerId
     * @throws ApprovalProcessorException
     * @throws LocalizedException
     */
    public function processApproval(PurchaseOrderInterface $purchaseOrder, int $customerId);
}
