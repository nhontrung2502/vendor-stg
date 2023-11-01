<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Company;

use Magento\Framework\Model\AbstractModel;
use Magento\PurchaseOrder\Model\ResourceModel\Company\Config as ConfigResourceModel;

/**
 * Purchase Order Company Config Model
 */
class Config extends AbstractModel implements ConfigInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ConfigResourceModel::class);
        parent::_construct();
    }

    /**
     * @inheritDoc
     */
    public function getCompanyId()
    {
        return $this->getData(self::COMPANY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCompanyId($id)
    {
        return $this->setData(self::COMPANY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function isPurchaseOrderEnabled()
    {
        return (bool) $this->getData(self::IS_PURCHASE_ORDER_ENABLED);
    }

    /**
     * @inheritDoc
     */
    public function setIsPurchaseOrderEnabled(bool $isEnabled)
    {
        return $this->setData(self::IS_PURCHASE_ORDER_ENABLED, $isEnabled);
    }
}
