/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    'map': {
        '*': {
            'Magento_OfflinePayments/template/payment/checkmo.html':
                'Magento_PurchaseOrder/template/payment/checkmo.html',
            'Magento_OfflinePayments/template/payment/cashondelivery.html':
                'Magento_PurchaseOrder/template/payment/cashondelivery.html',
            'Magento_OfflinePayments/template/payment/banktransfer.html':
                'Magento_PurchaseOrder/template/payment/banktransfer.html',
            'Magento_OfflinePayments/template/payment/purchaseorder-form.html':
                'Magento_PurchaseOrder/template/payment/purchaseorder-form.html',
            'Magento_CompanyCredit/template/payment/companycredit-form.html':
                'Magento_PurchaseOrder/template/payment/companycredit-form.html',
            'Magento_Payment/template/payment/free.html':
                'Magento_PurchaseOrder/template/payment/free.html',
            'Magento_Checkout/template/billing-address/details.html':
                'Magento_PurchaseOrder/template/checkout/billing-address/details.html',
            'Magento_Checkout/template/shipping-information/address-renderer/default.html':
                'Magento_PurchaseOrder/template/checkout/shipping-information/address-renderer/default.html'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/step-navigator': {
                'Magento_PurchaseOrder/js/model/step-navigator-mixins': true
            },
            'Magento_Checkout/js/view/payment/default': {
                'Magento_PurchaseOrder/js/view/payment/default-mixins': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Magento_PurchaseOrder/js/view/shipping-mixins': true
            },
            'Magento_Checkout/js/view/payment': {
                'Magento_PurchaseOrder/js/view/payment-mixins': true
            },
            'Magento_Checkout/js/action/set-payment-information-extended': {
                'Magento_PurchaseOrder/js/action/set-payment-information-extended-mixin': true
            },
            'Magento_Checkout/js/model/resource-url-manager': {
                'Magento_PurchaseOrder/js/model/resource-url-manager-mixin': true
            },
            'Magento_Checkout/js/action/get-payment-information': {
                'Magento_PurchaseOrder/js/action/get-payment-information-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Magento_PurchaseOrder/js/action/place-order-mixin': true
            },
            'Magento_GiftCardAccount/js/action/remove-gift-card-from-quote': {
                'Magento_PurchaseOrder/js/action/remove-gift-card-from-quote-mixin': true
            },
            'Magento_Checkout/js/view/shipping-information': {
                'Magento_PurchaseOrder/js/view/shipping-information-mixin': true
            },
            'Magento_Checkout/js/action/set-billing-address': {
                'Magento_PurchaseOrder/js/action/set-billing-address-mixin': true
            },
            'Magento_Checkout/js/model/payment/method-converter': {
                'Magento_PurchaseOrder/js/model/payment/method-converter-mixin': true
            }
        }
    }
};
