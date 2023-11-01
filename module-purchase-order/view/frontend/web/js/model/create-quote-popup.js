/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data',
    'mage/dataPost',
    'mage/translate'
], function ($, modal, customerData, dataPost) {
    'use strict';

    $.widget('mage.createQuotePopup', {
        options: {
            popupTitle: $.mage.__('The shopping cart is not empty'),
            cacheStorageName: 'mage-cache-storage',
            postData: {},
            mergeLink: 'replace_cart/1',
            popupSelector: '[data-role="add-item-quote-popup"]',
            buttonAddItemSelector: '.action.additem',
            popupMainText: $.mage.__('You have items in your shopping cart. Would you like to merge the items in this Purchase Order with the items in the shopping cart or replace them?'), //eslint-disable-line max-len
            popupSecondaryText: $.mage.__('Select Cancel to stay on the current page.'),
            mainTextSelector: '.text-main',
            secondaryTextSelector: '.text-secondary',
            buttonMergeSelector: '.action.merge',
            buttonReplaceSelector: '.action.replace',
            buttonCancelSelector: '.action.cancel'
        },

        /**
         *
         * @private
         */
        _create: function () {
            var self = this,
                options = {
                    'type': 'popup',
                    'modalClass': 'popup-add-item-quote',
                    'responsive': true,
                    'innerScroll': true,
                    'title': this.options.popupTitle,
                    'buttons': []
                };

            $(this.element).modal(options);
            $(this.options.popupSelector + ' ' +
            this.options.mainTextSelector).text(this.options.popupMainText);
            $(this.options.popupSelector + ' ' +
            this.options.secondaryTextSelector).text(this.options.popupSecondaryText);
            $(this.options.buttonAddItemSelector).off('click');
            $(this.options.buttonAddItemSelector).on('click', function (event) {
                event.stopImmediatePropagation();
                event.preventDefault();
                self.options.postData = $(this).data('post');

                if (self._isCartEmpty()) {
                    customerData.invalidate(['cart']);
                    self._runAddItem(self.options.postData, '');
                } else {
                    self._showCartNotEmptyModal();
                }
            });
            $(this.options.buttonMergeSelector).on('click', $.proxy(function () {
                customerData.invalidate(['cart']);
                this._runAddItem(this.options.postData, '');
            }, this));
            $(this.options.buttonReplaceSelector).on('click', $.proxy(function () {
                customerData.invalidate(['cart']);
                this._runAddItem(this.options.postData, this.options.mergeLink);
            }, this));
            $(this.options.buttonCancelSelector).on('click', $.proxy(function (e) {
                $(this.element).modal('closeModal');
                e.preventDefault();
            }, this));
        },

        /**
         *
         * @private
         */
        _isCartEmpty: function () {
            var cacheStorage = localStorage.getItem(this.options.cacheStorageName);

            cacheStorage = JSON.parse(cacheStorage);

            return cacheStorage.cart['summary_count'] === 0;
        },

        /**
         *
         * @private
         */
        _showCartNotEmptyModal: function () {
            $(this.element).modal('openModal');
        },

        /**
         * Add purchase order items to the shopping cart
         *
         * @param {Object} data
         * @param {String} linkArguments
         * @private
         */
        _runAddItem: function (data, linkArguments) {
            var params = data;

            params.action += linkArguments;
            dataPost().postData(params);
        }
    });

    return $.mage.createQuotePopup;
});
