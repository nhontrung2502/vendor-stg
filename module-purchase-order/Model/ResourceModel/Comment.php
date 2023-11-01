<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model\ResourceModel;

use Magento\PurchaseOrder\Api\Data\CommentInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Comment resource model
 */
class Comment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**#@+
     * Order Approval quote comment table
     */
    private const PURCHASE_ORDER_COMMENT_TABLE = 'purchase_order_comment';
    /**#@-*/

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::PURCHASE_ORDER_COMMENT_TABLE, 'entity_id');
    }

    /**
     * Assign comment data
     *
     * @param \Magento\PurchaseOrder\Api\Data\CommentInterface $comment
     * @return $this
     * @throws CouldNotSaveException
     */
    public function saveCommentData(
        CommentInterface $comment
    ) {
        $commentData = $comment->getData();

        if ($commentData) {
            try {
                $this->getConnection()->insertOnDuplicate(
                    $this->getTable(self::PURCHASE_ORDER_COMMENT_TABLE),
                    $commentData,
                    array_keys($commentData)
                );
            } catch (\Exception $e) {
                throw new CouldNotSaveException(__('There is an error while saving comment.'));
            }
        }

        return $this;
    }
}
