<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface PurchaseOrderLogInterface
 *
 * @api
 * @since 100.2.0
 */
interface PurchaseOrderLogInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants
     */
    const ID = 'id';
    const REQUEST_ID = 'request_id';
    const REQUEST_LOG = 'request_log';
    const OWNER_ID = 'owner_id';
    const CREATED_AT = 'created_at';
    const ACTIVITY_TYPE = 'activity_type';
    /**#@-*/

    /**
     * Get log ID.
     *
     * @return int
     * @since 100.2.0
     */
    public function getId();

    /**
     * Set log ID.
     *
     * @param int $id
     * @return $this
     * @since 100.2.0
     */
    public function setId($id);

    /**
     * Get purchase order ID, that this log belongs to.
     *
     * @return int
     * @since 100.2.0
     */
    public function getRequestId();

    /**
     * Set purchase order ID, that this log belongs to.
     *
     * @param int $id
     * @return $this
     * @since 100.2.0
     */
    public function setRequestId($id);

    /**
     * Get log owner ID.
     *
     * @return int
     * @since 100.2.0
     */
    public function getOwnerId();

    /**
     * Set log owner ID.
     *
     * @param int $ownerId
     * @return $this
     * @since 100.2.0
     */
    public function setOwnerId($ownerId);

    /**
     * Get Request log.
     *
     * @return string
     * @since 100.2.0
     */
    public function getRequestLog();

    /**
     * Set Request log.
     *
     * @param string $requestLog
     * @return $this
     * @since 100.2.0
     */
    public function setRequestLog($requestLog);

    /**
     * Get type.
     *
     * @return string
     * @since 100.2.0
     */
    public function getActivityType();

    /**
     * Set type.
     *
     * @param string $activityType
     * @return $this
     * @since 100.2.0
     */
    public function setActivityType($activityType);

    /**
     * Get log created at.
     *
     * @return string
     * @since 100.2.0
     */
    public function getCreatedAt();

    /**
     * Set log created at.
     *
     * @param int $timestamp
     * @return $this
     * @since 100.2.0
     */
    public function setCreatedAt($timestamp);

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\PurchaseOrder\Api\Data\PurchaseOrderLogExtensionInterface|null
     * @since 100.2.0
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\PurchaseOrder\Api\Data\PurchaseOrderLogExtensionInterface $extensionAttributes
     * @return $this
     * @since 100.2.0
     */
    public function setExtensionAttributes(
        \Magento\PurchaseOrder\Api\Data\PurchaseOrderLogExtensionInterface $extensionAttributes
    );
}
