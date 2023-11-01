/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.purchaseOrderActionButton', {
        options: {
            actionButtonSelector: 'button[type="submit"]'
        },

        /** @inheritdoc */
        _create: function () {
            // Initialize event listeners
            this._bindDisablePurchaseOrderActionsListener();
            this._bindCommentFormSubmitListener();
        },

        /**
         * Bind an event listener to disable purchase order actions once 'disablePurchaseOrderActions' is triggered.
         *
         * @private
         */
        _bindDisablePurchaseOrderActionsListener: function () {
            $(document).on('disablePurchaseOrderActions', function () {
                this._disableActionButton(true);
            }.bind(this));
        },

        /**
         * Bind an event listener for comment form submission and include a comment if provided, in the request.
         *
         * @private
         */
        _bindCommentFormSubmitListener: function () {
            var $commentElement;

            if (!this.options.commentSelector) {
                return;
            }

            $commentElement = $(this.options.commentSelector);

            $(this.element).on('submit', function () {
                var commentValue = $commentElement.val(),
                    commentName = $commentElement.attr('name');

                if (commentValue) {
                    $(this.element).append(
                        $('<input />').prop('type', 'hidden').prop('name', commentName).val(commentValue)
                    );
                }

                // Trigger an event to disable purchase order actions
                $(document).trigger('disablePurchaseOrderActions');
            }.bind(this));
        },

        /**
         * Disable action button.
         *
         * @param {Boolean} isDisabled
         * @private
         */
        _disableActionButton: function (isDisabled) {
            var $actionButton = this.element.find(this.options.actionButtonSelector);

            $actionButton.prop('disabled', isDisabled);
        }
    });

    return $.mage.purchaseOrderActionButton;
});
