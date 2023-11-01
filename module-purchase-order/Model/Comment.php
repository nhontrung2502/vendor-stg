<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Model;

use Magento\PurchaseOrder\Api\Data\CommentExtensionInterface;
use Magento\PurchaseOrder\Api\Data\CommentInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Purchase order comment model
 */
class Comment extends AbstractExtensibleModel implements CommentInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Comment::class);
        parent::_construct();
    }

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(CommentInterface::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($id)
    {
        return $this->setData(CommentInterface::ENTITY_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getPurchaseOrderId()
    {
        return $this->getData(CommentInterface::PURCHASE_ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setPurchaseOrderId($id)
    {
        return $this->setData(CommentInterface::PURCHASE_ORDER_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getCreatorId()
    {
        return $this->getData(CommentInterface::CREATOR_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCreatorId($creatorId)
    {
        return $this->setData(CommentInterface::CREATOR_ID, $creatorId);
    }

    /**
     * @inheritdoc
     */
    public function getComment()
    {
        return $this->getData(CommentInterface::COMMENT);
    }

    /**
     * @inheritdoc
     */
    public function setComment($comment)
    {
        return $this->setData(CommentInterface::COMMENT, $comment);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(CommentInterface::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($timestamp)
    {
        return $this->setData(CommentInterface::CREATED_AT, $timestamp);
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
        CommentExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
