/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.purchaseOrderComment', {
        options: {
            inputSelector: '[data-comment-input]',
            submitSelector: '[data-comment-submit]',
            formSelector: '#comments-form'
        },

        /** @inheritdoc */
        _create: function () {
            var $inputEl = this.element.find(this.options.inputSelector),
                $submitEl = this.element.find(this.options.submitSelector),
                $formEl = this.element.find(this.options.formSelector);

            $inputEl.on('keyup',
                this._setSubmitButtonDisabledAttributeBasedOnCommentInput.bind(this, $inputEl, $submitEl)
            );

            $formEl.on('submit',
                this._handleFormSubmit.bind(this, $inputEl, $submitEl)
            );

            $(document).on('disablePurchaseOrderActions',
                this._disableSubmitButton.bind(this, $submitEl, true)
            );

            this._setSubmitButtonDisabledAttributeBasedOnCommentInput($inputEl, $submitEl);
        },

        /**
         * Assign disable attribute to submit button based on input's length
         *
         * @param {jQuery} $inputEl
         * @param {jQuery} $submitEl
         *
         * @private
         */
        _setSubmitButtonDisabledAttributeBasedOnCommentInput: function ($inputEl, $submitEl) {
            var hasContent = $inputEl.val().trim().length;

            this._disableSubmitButton($submitEl, !hasContent);
        },

        /**
         * Handle comment form submission
         *
         * @param {jQuery} $inputEl
         * @param {jQuery} $submitEl
         *
         * @private
         */
        _handleFormSubmit: function ($inputEl, $submitEl) {
            // Trigger an event to disable purchase order actions
            $(document).trigger('disablePurchaseOrderActions');

            this._disableSubmitButton($submitEl, true);
        },

        /**
         * Disable submit button
         *
         * @param {jQuery} $submitEl
         * @param {Boolean} isDisabled
         *
         * @private
         */
        _disableSubmitButton: function ($submitEl, isDisabled) {
            $submitEl.prop('disabled', isDisabled);
        }
    });

    return $.mage.purchaseOrderComment;
});
