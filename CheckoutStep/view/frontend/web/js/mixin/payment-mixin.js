define([
    'ko',
    'jquery',
    'MDC_CheckoutStep/js/model/address-validator',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'mage/url',
    'mage/translate',
    'Magento_Ui/js/model/messageList',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list'
], function(
    ko,
    $,
    addressValidator,
    setShippingInformationAction,
    stepNavigator,
    checkoutData,
    quote,
    registry,
    url,
    $t,
    messageList,
    customer,
    addressList
) {
    'use strict';

    return function (target) {
        return target.extend({

            /**
             *
             * @returns {*}
             */
            initialize: function () {
                this.visible = ko.observable(false);
                this._super();

                return this;
            }

        });
    }
});
