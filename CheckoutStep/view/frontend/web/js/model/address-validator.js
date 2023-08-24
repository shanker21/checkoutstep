define([
    'jquery',
    'underscore',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address'
], function(
    $,
    _,
    ko,
    customer,
    addressList,
    addressConverter,
    quote,
    createShippingAddress,
    selectShippingAddress,
    createBillingAddress,
    selectBillingAddress
) {
    'use strict';

    return {
        validateBillingInformation: function(isFormInline, source) {
            if ($('input[name="billing-address-same-as-shipping"]:checked').val() == 'sameasdeliveryaddress') {
                if (isFormInline) {
                    var shippingAddress = quote.shippingAddress();
                    var addressData = addressConverter.formAddressDataToQuoteAddress(
                        source.get('shippingAddress')
                    );

                    for (var field in addressData) {

                        if (addressData.hasOwnProperty(field) &&
                            shippingAddress.hasOwnProperty(field) &&
                            typeof addressData[field] != 'function' &&
                            _.isEqual(shippingAddress[field], addressData[field])
                        ) {
                            shippingAddress[field] = addressData[field];
                        } else if (typeof addressData[field] != 'function' &&
                            !_.isEqual(shippingAddress[field], addressData[field])) {
                            shippingAddress = addressData;
                            break;
                        }
                    }

                    if (customer.isLoggedIn()) {
                        shippingAddress.save_in_address_book = 1;
                    }
                    var newBillingAddress = createBillingAddress(shippingAddress);
                    newBillingAddress.regionId = shippingAddress.regionId;
                    newBillingAddress.regionCode = shippingAddress.regionCode;
                    newBillingAddress.customerId = shippingAddress.customerId;
                    selectBillingAddress(newBillingAddress);
                } else {
                    selectBillingAddress(quote.shippingAddress());
                }

                return true;
            }

            var selectedAddress = $('[name="billing_address_id"]').val();
            if(selectedAddress) {
                var res = addressList.some(function (addressFromList) {
                    if (selectedAddress == addressFromList.customerAddressId) {
                        selectBillingAddress(addressFromList);
                        return true;
                    }
                    return false;
                });

                return res;
            }

            source.set('params.invalid', false);
            source.trigger('billingAddress.data.validate');

            if (source.get('params.invalid')) {
                return false;
            }
            var addressData = source.get('billingAddress'),
                newBillingAddress;

            if ($('#billing-save-in-address-book').is(":checked")) {
                addressData.save_in_address_book = 1;
            }

            console.log("addressData => "+JSON.stringify(addressData));
            console.log("quote shippingAddress addressData => "+JSON.stringify(quote.shippingAddress()));
            console.log("quote billingAddress addressData => "+JSON.stringify(quote.billingAddress()));
            newBillingAddress = createBillingAddress(addressData);
            selectBillingAddress(newBillingAddress);

            return true;
        }
    }
});
