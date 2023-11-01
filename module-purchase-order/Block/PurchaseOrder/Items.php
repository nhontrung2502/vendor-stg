<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection as QuoteItemCollection;

/**
 * Block class for the items information for a purchase order.
 *
 * @api
 * @since 100.2.0
 */
class Items extends AbstractPurchaseOrder
{
    const DEFAULT_TYPE = 'default';

    /**
     * @var QuoteItemCollection
     */
    private $items;

    /**
     * Get the items included in the current purchase order.
     *
     * This is based on its associated quote.
     *
     * @return mixed
     * @since 100.2.0
     */
    public function getItems()
    {
        if ($this->items === null) {
            $this->items = $this->getQuote()->getItemsCollection();
        }

        return $this->items;
    }

    /**
     * Get the item html.
     *
     * @param CartItemInterface $item
     * @return mixed
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getItemHtml(CartItemInterface $item)
    {
        $renderer = $this->getItemRenderer($item->getProductType())->setItem($item);

        return $renderer->toHtml();
    }

    /**
     * Get the item renderer.
     *
     * @param string $type
     * @return mixed
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getItemRenderer($type = self::DEFAULT_TYPE)
    {
        $rendererList = $this->_getRendererList();

        if (!$rendererList) {
            throw new LocalizedException(__('Renderer list for block "%1" is not defined', $this->getNameInLayout()));
        }

        $overriddenTemplates = $this->getOverriddenTemplates() ?? [];
        $template = $overriddenTemplates[$type] ?? $this->getRendererTemplate();

        return $rendererList->getRenderer($type, self::DEFAULT_TYPE, $template);
    }

    /**
     * Get the renderer block.
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function _getRendererList()
    {
        return $this->getRendererListName()
            ? $this->getLayout()->getBlock($this->getRendererListName())
            : $this->getChildBlock('renderer.list');
    }
}
