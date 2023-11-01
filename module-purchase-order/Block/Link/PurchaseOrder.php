<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\Link;

use Magento\Framework\View\Element\Html\Link;
use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * Block class for 'My Purchase Orders' link in header menu.
 *
 * @api
 * @since 100.2.0
 */
class PurchaseOrder extends Link implements SortLinkInterface
{
    /**
     * Get href.
     *
     * @return string
     * @since 100.2.0
     */
    public function getHref()
    {
        return $this->getUrl('purchaseorder/purchaseorder');
    }

    /**
     * Get label.
     *
     * @return \Magento\Framework\Phrase
     * @since 100.2.0
     */
    public function getLabel()
    {
        return __('My Purchase Orders');
    }

    /**
     * @inheritdoc
     * @since 100.2.0
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }
}
