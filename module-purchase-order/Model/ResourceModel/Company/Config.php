<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\ResourceModel\Company;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Purchase Order Company Config Resource Model
 */
class Config extends AbstractDb
{
    /**#@+*/
    private const TABLE = 'purchase_order_company_config';
    private const PRIMARY_KEY = 'company_entity_id';
    /**#@-*/

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_isPkAutoIncrement = false;
        $this->_useIsObjectNew = true;

        $this->_init(
            self::TABLE,
            self::PRIMARY_KEY
        );
    }
}
