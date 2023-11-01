<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Interface PurchaseOrderLogRepositoryInterface
 *
 * @api
 */
interface PurchaseOrderLogRepositoryInterface
{
    /**
     * Set the comment for a negotiable quote.
     *
     * @param \Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface $log log.
     * @return bool
     * @throws CouldNotSaveException
     */
    public function save(PurchaseOrderLogInterface $log);

    /**
     * Return the log for a specified Log ID.
     *
     * @param int $id Log ID.
     * @return \Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface Log.
     */
    public function getById($id);

    /**
     * Get list of log
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws \InvalidArgumentException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
