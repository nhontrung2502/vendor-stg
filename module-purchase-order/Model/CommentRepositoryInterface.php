<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model;

use Magento\PurchaseOrder\Api\Data\CommentInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface CommentRepositoryInterface
 *
 * @api
 */
interface CommentRepositoryInterface
{
    /**
     * Set the comment for a negotiable quote.
     *
     * @param \Magento\PurchaseOrder\Api\Data\CommentInterface $comment comment.
     * @return bool
     * @throws CouldNotSaveException
     */
    public function save(CommentInterface $comment);

    /**
     * Get comment by ID
     *
     * @param int $id
     * @return \Magento\PurchaseOrder\Api\Data\CommentInterface
     * @throws NoSuchEntityException
     */
    public function get($id);

    /**
     * Get list of comments
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws \InvalidArgumentException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
