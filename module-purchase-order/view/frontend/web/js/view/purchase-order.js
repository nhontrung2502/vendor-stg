/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Company/js/authorization'
], function (Component, customerData, authorization) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.config = customerData.get('purchase_order');
        },

        /**
         * Checks if the purchase order root resource is allowed.
         *
         * @returns {Boolean}
         */
        isPurchaseOrderAllAllowed: function () {
            return authorization.isAllowed('Magento_PurchaseOrder::view_purchase_orders');
        }
    });
});
