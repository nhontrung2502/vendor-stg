<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrderLog;

/**
 * Purchase order log collection class
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\PurchaseOrder\Model\PurchaseOrderLog::class,
            \Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrderLog::class
        );
    }
}
