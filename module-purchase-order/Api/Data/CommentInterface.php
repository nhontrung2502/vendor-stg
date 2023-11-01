<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface CommentInterface
 *
 * @api
 * @since 100.2.0
 */
interface CommentInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants
     */
    const ENTITY_ID = 'entity_id';
    const PURCHASE_ORDER_ID = 'purchase_order_id';
    const CREATOR_ID = 'creator_id';
    const COMMENT = 'comment';
    const CREATED_AT = 'created_at';
    /**#@-*/

    /**
     * Get comment ID.
     *
     * @return int
     * @since 100.2.0
     */
    public function getEntityId();

    /**
     * Set comment ID.
     *
     * @param int $id
     * @return $this
     * @since 100.2.0
     */
    public function setEntityId($id);

    /**
     * Get purchase order ID that this comment belongs to.
     *
     * @return int
     * @since 100.2.0
     */
    public function getPurchaseOrderId();

    /**
     * Set purchase order ID that this comment belongs to.
     *
     * @param int $id
     * @return $this
     * @since 100.2.0
     */
    public function setPurchaseOrderId($id);

    /**
     * Get comment creator ID.
     *
     * @return int
     * @since 100.2.0
     */
    public function getCreatorId();

    /**
     * Set comment creator ID.
     *
     * @param int $creatorId
     * @return $this
     * @since 100.2.0
     */
    public function setCreatorId($creatorId);

    /**
     * Get comment.
     *
     * @return string
     * @since 100.2.0
     */
    public function getComment();

    /**
     * Set comment.
     *
     * @param string $comment
     * @return $this
     * @since 100.2.0
     */
    public function setComment($comment);

    /**
     * Get comment created at.
     *
     * @return string
     * @since 100.2.0
     */
    public function getCreatedAt();

    /**
     * Set comment created at.
     *
     * @param int $timestamp
     * @return $this
     * @since 100.2.0
     */
    public function setCreatedAt($timestamp);

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\PurchaseOrder\Api\Data\CommentExtensionInterface|null
     * @since 100.2.0
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\PurchaseOrder\Api\Data\CommentExtensionInterface $extensionAttributes
     * @return $this
     * @since 100.2.0
     */
    public function setExtensionAttributes(
        \Magento\PurchaseOrder\Api\Data\CommentExtensionInterface $extensionAttributes
    );
}
