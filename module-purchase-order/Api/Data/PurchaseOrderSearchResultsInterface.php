<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface PurchaseOrderSearchResultsInterface
 *
 * @api
 */
interface PurchaseOrderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return PurchaseOrderInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param PurchaseOrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
