<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Processor;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Dummy approval processor class; does nothing
 */
class DummyApprovalProcessor implements ApprovalProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function processApproval(PurchaseOrderInterface $purchaseOrder, int $customerId)
    {
        // phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
        return;
    }
}
