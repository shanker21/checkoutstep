define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote'
    ],
    function ($,$t,messageList,quote) {
        'use strict';

        return {

            /**
             * Validate checkout agreements
             *
             * @returns {Boolean}
             */
            validate: function () {
                var emailValidationResult = false;

                var noOfDays= $("#delivery_date").val();
                var items = quote.getItems();
                if (items[0]['product_type'] == "giftcard") {
                    $("#delivery-slot-message").hide();
                    return true;
                }
                if(noOfDays){
                    emailValidationResult = true;
                    $("#delivery-slot-message").hide();
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    messageList.addErrorMessage({ message: $t('Please select delivery slot') });
                }
                return emailValidationResult;
            }
        };
    }
);