define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'MDC_CheckoutStep/js/model/validate'
    ],
    function (Component, additionalValidators, gmailValidation) {
        'use strict';
        additionalValidators.registerValidator(gmailValidation);
        return Component.extend({});
    }
);