<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Purchase Order model interface
 *
 * @api
 */
interface PurchaseOrderInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants
     */
    const ENTITY_ID = 'entity_id';
    const INCREMENT_ID = 'increment_id';
    const QUOTE_ID = 'quote_id';
    const PO_STATUS = 'status';
    const CREATOR_ID = 'creator_id';
    const COMPANY_ID = 'company_id';
    const SHIPPING_METHOD = 'shipping_method';
    const PAYMENT_METHOD = 'payment_method';
    const GRAND_TOTAL = 'grand_total';
    const SNAPSHOT = 'snapshot';
    const IS_VALIDATE = 'is_validate';
    const ORDER_ID = 'order_id';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const APPROVED_BY = 'approved_by';
    const AUTO_APPROVED = 'auto_approved';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**#@+
     * Status
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVAL_REQUIRED = 'approval_required';
    const STATUS_APPROVED = 'approved';
    const STATUS_APPROVED_PENDING_PAYMENT = 'approved_pending_payment';
    const STATUS_ORDER_IN_PROGRESS = 'order_in_progress';
    const STATUS_ORDER_PLACED = 'order_placed';
    const STATUS_ORDER_FAILED = 'order_failed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELED = 'canceled';
    /**#@-*/

    /**
     * Get purchase order ID.
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set purchase order ID.
     *
     * @param int $id
     * @return $this
     */
    public function setEntityId($id);

    /**
     * Get Increment ID.
     *
     * @return int
     */
    public function getIncrementId();

    /**
     * Set Increment ID.
     *
     * @param int $incrementId
     * @return $this
     */
    public function setIncrementId($incrementId);

    /**
     * Get quote ID.
     *
     * @return int
     */
    public function getQuoteId();

    /**
     * Set quote ID.
     *
     * @param int $id
     * @return $this
     */
    public function setQuoteId($id);

    /**
     * Set serialized purchase order snapshot string from quote object.
     *
     * @param CartInterface $quote
     * @return $this
     */
    public function setSnapshotQuote(CartInterface $quote);

    /**
     * Get quote from purchase order snapshot
     *
     * @return CartInterface
     */
    public function getSnapshotQuote();

    /**
     * Get Purchase Order status.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set Purchase Order status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Retrieve Purchase Order creator id.
     *
     * @return int
     */
    public function getCreatorId();

    /**
     * Set Purchase Order creator id.
     *
     * @param int $id
     * @return $this
     */
    public function setCreatorId($id);

    /**
     * Retrieve Purchase Order Company id.
     *
     * @return int
     */
    public function getCompanyId();

    /**
     * Set Purchase Order Company id.
     *
     * @param int $id
     * @return $this
     */
    public function setCompanyId($id);

    /**
     * Get shipping method.
     *
     * @return string
     */
    public function getShippingMethod();

    /**
     * Set shipping method.
     *
     * @param string $shippingMethod
     * @return $this
     */
    public function setShippingMethod($shippingMethod);

    /**
     * Get payment method.
     *
     * @return string
     */
    public function getPaymentMethod();

    /**
     * Set payment method.
     *
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * Get Purchase Order total price.
     *
     * @return float
     */
    public function getGrandTotal();

    /**
     * Set Purchase Order total price.
     *
     * @param float $total
     */
    public function setGrandTotal($total);

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\PurchaseOrder\Api\Data\PurchaseOrderExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\PurchaseOrder\Api\Data\PurchaseOrderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\PurchaseOrder\Api\Data\PurchaseOrderExtensionInterface $extensionAttributes
    );

    /**
     * Get is validate flag.
     *
     * @return int
     */
    public function getIsValidate();

    /**
     * Set is validate flag.
     *
     * @param int $isValidate
     * @return $this
     */
    public function setIsValidate($isValidate);

    /**
     * Get order ID that was created from purchase order. Returns null if no order is created.
     *
     * @return int|null
     */
    public function getOrderId();

    /**
     * Set order ID attached to purchase order.
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get order increment ID attached to purchase order.
     *
     * @return string|null
     */
    public function getOrderIncrementId();

    /**
     * Set order increment ID attached to purchase order.
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Set user ID who approved purchase order.
     *
     * @param array $customerIds
     * @return $this
     */
    public function setApprovedBy(array $customerIds);

    /**
     * Get approver ID.
     *
     * @return array|null
     */
    public function getApprovedBy() : ?array;

    /**
     * Get whether the purchase order was auto approved
     *
     * @return mixed
     */
    public function getAutoApproved();

    /**
     * Set whether this order was auto approved or not
     *
     * @param bool $createdAt
     * @return mixed
     */
    public function setAutoApproved(bool $createdAt);

    /**
     * Get create at time
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set create at time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get update at time
     *
     * @return String
     */
    public function getUpdatedAt();

    /**
     * Set update at time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}
