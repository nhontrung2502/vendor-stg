/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'mage/utils/wrapper',
    'Magento_NegotiableQuote/js/action/set-payment-information-negotiable-quote'
], function (wrapper, setPaymentInformationNegotiableQuote) {
    'use strict';

    return function (setPaymentInformation) {
        return wrapper.wrap(
            setPaymentInformation,
            function (originalAction, messageContainer, paymentData) {
                if (window.checkoutConfig.isNegotiableQuote) {
                    return setPaymentInformationNegotiableQuote(messageContainer, paymentData);
                }

                return originalAction(messageContainer, paymentData);
            }
        );
    };
});
