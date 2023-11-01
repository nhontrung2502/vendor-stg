<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Purchase Order Repository Interface handles purchase order CRUD
 *
 * @api
 */
interface PurchaseOrderRepositoryInterface
{
    /**
     * Get the purchase order for a specified purchase order ID.
     *
     * @param int $id
     * @return PurchaseOrderInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get the purchase order for a specified quote ID.
     *
     * @param int $quoteId
     * @return PurchaseOrderInterface
     */
    public function getByQuoteId($quoteId);

    /**
     * Get the purchase order for a specified order ID.
     *
     * @param int $orderId
     * @return PurchaseOrderInterface
     */
    public function getByOrderId($orderId);

    /**
     * Save the purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function save(PurchaseOrderInterface $purchaseOrder);

    /**
     * Delete the purchase order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @throws LocalizedException
     */
    public function delete(PurchaseOrderInterface $purchaseOrder);

    /**
     * Get list of purchase orders
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
