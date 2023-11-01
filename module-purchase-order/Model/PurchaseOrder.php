<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderExtensionInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder\Collection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Purchase Order model
 */
class PurchaseOrder extends AbstractExtensibleModel implements PurchaseOrderInterface
{
    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var Json
     */
    private $serializerJson;

    /**
     * @var PurchaseOrderQuoteConverter
     */
    private $purchaseOrderQuoteConverter;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param ResourceModel\PurchaseOrder $resource
     * @param Collection $resourceCollection
     * @param DateTime $date
     * @param Json $serializerJson
     * @param PurchaseOrderQuoteConverter $purchaseOrderQuoteConverter
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        ResourceModel\PurchaseOrder $resource,
        Collection $resourceCollection,
        DateTime $date,
        Json $serializerJson,
        PurchaseOrderQuoteConverter $purchaseOrderQuoteConverter,
        array $data = []
    ) {
        $this->date = $date;
        $this->serializerJson = $serializerJson;
        $this->purchaseOrderQuoteConverter = $purchaseOrderQuoteConverter;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Initialize resource.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\PurchaseOrder::class);
        parent::_construct();
    }

    /**
     * @inheritDoc
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritdoc
     */
    public function setSnapshotQuote(CartInterface $quote)
    {
        $quoteArr = $this->purchaseOrderQuoteConverter->convertToArray($quote);
        $serializedSnapshot = $this->serializerJson->serialize($quoteArr);
        return $this->setData(self::SNAPSHOT, $serializedSnapshot);
    }

    /**
     * @inheritDoc
     */
    public function getSnapshotQuote()
    {
        $snapshot = $this->getData(self::SNAPSHOT);
        $quoteArr = $this->serializerJson->unserialize($snapshot);
        return $this->purchaseOrderQuoteConverter->convertArrayToQuote($quoteArr);
    }

    /**
     * @inheritdoc
     */
    public function getCreatorId()
    {
        return $this->getData(self::CREATOR_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCreatorId($id)
    {
        return $this->setData(self::CREATOR_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getCompanyId()
    {
        return $this->getData(self::COMPANY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCompanyId($id)
    {
        return $this->setData(self::COMPANY_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($id)
    {
        return $this->setData(self::ORDER_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * @inheritdoc
     */
    public function getApprovedBy() : array
    {
        if (!$this->getId()) {
            return [];
        }

        $approvedBy = $this->getData(self::APPROVED_BY);
        if ($approvedBy === null) {
            $approvedBy = $this->getResource()->getApprovedBy((int) $this->getId());
            $this->setData(self::APPROVED_BY, $approvedBy);
        }

        return $approvedBy;
    }

    /**
     * @inheritdoc
     */
    public function setApprovedBy(array $customerIds)
    {
        return $this->setData(self::APPROVED_BY, $customerIds);
    }

    /**
     * @inheritDoc
     */
    public function getAutoApproved()
    {
        return $this->getData(self::AUTO_APPROVED);
    }

    /**
     * @inheritDoc
     */
    public function setAutoApproved(bool $autoApproved)
    {
        return $this->setData(self::AUTO_APPROVED, $autoApproved);
    }

    /**
     * @inheritdoc
     */
    public function getShippingMethod()
    {
        return $this->getData(self::SHIPPING_METHOD);
    }

    /**
     * @inheritdoc
     */
    public function setShippingMethod($shipMethod)
    {
        return $this->setData(self::SHIPPING_METHOD, $shipMethod);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethod()
    {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * @inheritdoc
     */
    public function setPaymentMethod($payMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, $payMethod);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->getData(self::PO_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::PO_STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getIsValidate()
    {
        return $this->getData(self::IS_VALIDATE);
    }

    /**
     * @inheritdoc
     */
    public function setIsValidate($isValidate)
    {
        return $this->setData(self::IS_VALIDATE, $isValidate);
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
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getGrandTotal()
    {
        return $this->getData(self::GRAND_TOTAL);
    }

    /**
     * @inheritdoc
     */
    public function setGrandTotal($total)
    {
        return $this->setData(self::GRAND_TOTAL, $total);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(PurchaseOrderExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        parent::beforeSave();
        $this->setUpdatedAt($this->date->gmtDate());
        /**
         * Generate increment ID (if needed).
         */
        if (empty($this->getIncrementId())) {
            $resource = $this->_getResource();
            $this->setIncrementId(
                $resource->reserveIncrementId($this->getQuoteId())
            );
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function afterSave()
    {
        // Save the approved by IDs into their associated table
        if ($this->getApprovedBy() !== null) {
            $this->getResource()->saveApprovedBy((int) $this->getId(), $this->getApprovedBy());
        }
        return parent::afterSave();
    }
}
