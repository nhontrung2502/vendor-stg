<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Framework\Exception\LocalizedException;

/**
 * Block class for the purchase order view details page.
 *
 * @api
 * @since 100.2.0
 */
class View extends AbstractPurchaseOrder
{
    /**
     * Set page title.
     * @since 100.2.0
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Purchase Order # %1', $this->getPurchaseOrder()->getIncrementId()));
    }

    /**
     * Retrieve the sorted names for the children tabs
     *
     * @return array
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getSortedChildNames(): array
    {
        $childNames = $this->getChildNames();
        $layout = $this->getLayout();
        $childOrder = [];

        foreach ($childNames as $childName) {
            $alias = $layout->getElementAlias($childName);
            $sortOrder = (int) $this->getChildData($alias, 'sort_order') ?? 0;

            $childOrder[$childName] = $sortOrder;
        }

        asort($childOrder, SORT_NUMERIC);

        return array_keys($childOrder);
    }
}
