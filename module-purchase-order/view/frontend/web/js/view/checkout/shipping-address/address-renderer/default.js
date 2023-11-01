/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_NegotiableQuote/js/view/shipping-address/address-renderer/default'
], function (AddressRendererView) {
    'use strict';

    return AddressRendererView.extend({
        defaults: {
            template: 'Magento_PurchaseOrder/checkout/shipping-address/address-renderer/default'
        }
    });
});
