<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Status config source
 */
class Status implements OptionSourceInterface
{
    /**
     * Get status labels.
     *
     * @return string[]
     */
    public function getStatusLabels()
    {
        return [
            PurchaseOrderInterface::STATUS_PENDING => __('Pending'),
            PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED => __('Approval Required'),
            PurchaseOrderInterface::STATUS_APPROVED => __('Approved'),
            PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT => __('Approved - Pending Payment'),
            PurchaseOrderInterface::STATUS_ORDER_PLACED => __('Approved - Ordered'),
            PurchaseOrderInterface::STATUS_ORDER_FAILED => __('Approved - Order Failed'),
            PurchaseOrderInterface::STATUS_REJECTED => __('Rejected'),
            PurchaseOrderInterface::STATUS_CANCELED => __('Canceled')
        ];
    }

    /**
     * To option array.
     *
     * @return string[]
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->getStatusLabels() as $status => $label) {
            $options[] = ['label' => $label, 'value' => $status];
        }

        return $options;
    }

    /**
     * Get label by status.
     *
     * @param string $status
     * @return Phrase|null
     */
    public function getLabelByStatus($status) : ?Phrase
    {
        $labels = $this->getStatusLabels();
        return $labels[$status] ?? null;
    }
}
