<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Purchase Order Log class holds purchase order log entity
 */
class PurchaseOrderLog extends AbstractExtensibleModel implements PurchaseOrderLogInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(\Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrderLog::class);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getRequestId()
    {
        return $this->getData(self::REQUEST_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRequestId($id)
    {
        return $this->setData(self::REQUEST_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getOwnerId()
    {
        return $this->getData(self::OWNER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOwnerId($ownerId)
    {
        return $this->setData(self::OWNER_ID, $ownerId);
    }

    /**
     * @inheritdoc
     */
    public function getRequestLog()
    {
        return $this->getData(self::REQUEST_LOG);
    }

    /**
     * @inheritdoc
     */
    public function setRequestLog($requestLog)
    {
        return $this->setData(self::REQUEST_LOG, $requestLog);
    }

    /**
     * @inheritdoc
     */
    public function getActivityType()
    {
        return $this->getData(self::ACTIVITY_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setActivityType($activityType)
    {
        return $this->setData(self::ACTIVITY_TYPE, $activityType);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($timestamp)
    {
        return $this->setData(self::CREATED_AT, $timestamp);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(
        \Magento\PurchaseOrder\Api\Data\PurchaseOrderLogExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
