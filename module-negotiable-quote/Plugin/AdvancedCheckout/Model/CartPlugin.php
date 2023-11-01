<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Plugin\AdvancedCheckout\Model;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Store\Model\Store;

/**
 * Class CartPlugin
 *
 * Validate current store id with the quote store id
 */
class CartPlugin
{
    /**
     * After get current store plugin
     *
     * @param Cart $cart
     * @param Store $currentStore
     * @return Store
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCurrentStore(
        Cart $cart,
        Store $currentStore
    ): Store {
        if (!$cart->getQuote()->hasStoreId()) {
            return $currentStore;
        }

        $cartStore = $cart->getQuote()->getStore();
        $quoteStoreId = $cartStore->getStoreId();
        if ($quoteStoreId !== $currentStore->getStoreId()) {
            $currentStore = $cartStore;
        }
        return $currentStore;
    }
}
