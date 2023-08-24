/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'uiRegistry',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/model/messages',
    'uiLayout',
    'Magento_Checkout/js/action/redirect-on-success'
], function (
    ko,
    $,
    Component,
    placeOrderAction,
    selectPaymentMethodAction,
    quote,
    customer,
    paymentService,
    checkoutData,
    checkoutDataResolver,
    registry,
    additionalValidators,
    Messages,
    layout,
    redirectOnSuccessAction
) {
    'use strict';

    return function (target) {
        return target.extend({
            /**
             * @return {Boolean}
             */
            selectPaymentMethodNew: function () {
                $(".payment-method").removeClass('_active');
                if(this.item.method == 'stripe_payments') {
                    $(".stripe-payments").addClass('_active');
                } else {
                    $("."+this.item.method).addClass('_active');
                }

                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            }
        });
    }
});
