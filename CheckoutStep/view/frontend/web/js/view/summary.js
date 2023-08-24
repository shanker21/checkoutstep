define([
    'Magento_Checkout/js/view/summary',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator'
], function (Component, quote, stepNavigator) {
    'use strict';

    return Component.extend({
        /**
         * @return {Boolean}
         */
        isVisible: function () {
            return stepNavigator.isProcessed('signinRegister');
        }
    });
});