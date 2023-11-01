<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Company;

/**
 * Interface for Purchase Order Company Config Data Layer
 *
 * @api
 */
interface ConfigInterface
{
    /**#@+*/
    const COMPANY_ID = 'company_entity_id';
    const IS_PURCHASE_ORDER_ENABLED = 'is_purchase_order_enabled';
    /**#@-*/

    /**
     * Get company id
     *
     * @return string|null
     */
    public function getCompanyId();

    /**
     * Set company id
     *
     * @param string $id
     * @return $this
     */
    public function setCompanyId($id);

    /**
     * Get status of purchase order enablement for company
     *
     * @return bool
     */
    public function isPurchaseOrderEnabled();

    /**
     * Set status of purchase order enablement for company
     *
     * @param bool $isEnabled
     * @return $this
     */
    public function setIsPurchaseOrderEnabled(bool $isEnabled);
}
