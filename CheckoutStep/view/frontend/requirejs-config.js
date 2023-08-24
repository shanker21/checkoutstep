var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'MDC_CheckoutStep/js/mixin/shipping-mixin': true
            },
            'Magento_Checkout/js/view/payment': {
                'MDC_CheckoutStep/js/mixin/payment-mixin': true
            },
            'Magento_Checkout/js/view/payment/default': {
                'MDC_CheckoutStep/js/mixin/payment/default-mixin': true
            },
            'Amazon_Payment/js/view/shipping': {
                'MDC_CheckoutStep/js/mixin/shipping-amazon-mixin': true
            },
            'Magento_Ui/js/lib/validation/validator': {
                'MDC_CheckoutStep/js/validator-mixin': true
            },
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'MDC_CheckoutStep/js/model/checkout-data-resolver': true
            }
        }
    }
};
