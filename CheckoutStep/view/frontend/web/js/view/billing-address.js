/*jshint browser:true*/
/*global define*/
define(
    [
        'ko',
        'underscore',
        'jquery',
        'Magento_Ui/js/form/form',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (
        ko,
        _,
        $,
        Component,
        customer,
        addressList,
        quote,
        createBillingAddress,
        selectBillingAddress,
        checkoutData,
        checkoutDataResolver,
        customerData,
        setBillingAddressAction,
        globalMessageList,
        $t

    ) {
        'use strict';

        var newAddressOption = {
                /**
                 * Get new address label
                 * @returns {String}
                 */
                getAddressInline: function () {
                    return $t('New Address');
                },
                customerAddressId: null
            },
            countryData = customerData.get('directory-data'),
            addressOptions = addressList().filter(function (address) {
                return address.getType() == 'customer-address';
            });

        addressOptions.push(newAddressOption);

        return Component.extend({
            defaults: {
                template: 'MDC_CheckoutStep/billing-address'
            },
            currentBillingAddress: quote.billingAddress,
            addressOptions: addressOptions,
            customerHasAddresses: addressOptions.length > 1,
            /**
             * Init component
             */
            initialize: function () {
                this._super();
                this.isAddressSameAsShipping = ko.observable('sameasdeliveryaddress');
                return this;
            },

            /**
             * @return {exports.initObservable}
             */
            initObservable: function () {
                this._super()
                    .observe({
                        selectedAddress: null,
                        isAddressFormVisible: false,
                        isAddressSameAsShipping: true,
                        saveInAddressBook: 1,
                        isAddressFormListVisible:false
                    });


                return this;
            },

            canUseShippingAddress: ko.computed(function () {
                return !quote.isVirtual() && quote.shippingAddress() && quote.shippingAddress().canUseForBilling();
            }),

            /**
             * @param {Object} address
             * @return {*}
             */
            addressOptionsText: function (address) {
                if(address.firstname) {
                    var addLine = address.firstname+' '+address.lastname;
                } else {
                    var addLine = 'New Address';
                }

                return addLine;
            },

            /**
             * @return {Boolean}
             */
            useShippingAddress1: function () {
                if (this.isAddressSameAsShipping()) {
                    this.isAddressFormVisible(false);
                    this.isAddressFormListVisible(false);
                } else {
                    if(addressOptions.length == 1) {
                        this.isAddressFormVisible(true);
                    } else {
                        this.isAddressFormListVisible(true);
                    }
                }
                return true;
            },
            /**
             * @return {Boolean}
             */
            useShippingAddress: function () {

                if (this.isAddressSameAsShipping()!='differentbillingaddress') {
                    this.isAddressFormVisible(false);
                    this.isAddressFormListVisible(false);
                }

                if (this.isAddressSameAsShipping()=='differentbillingaddress') {
                    if(addressOptions.length == 1) {
                        this.isAddressFormVisible(true);
                    } else {
                        this.isAddressFormListVisible(true);
                    }
                }
                return true;
            },
            /**
             * @param {Object} address
             */
            onAddressChange: function (address) {
                if(address) {
                    this.isAddressFormVisible(false);
                } else {
                    this.isAddressFormVisible(true);
                }
            },

            /**
             * @param {int} countryId
             * @return {*}
             */
            getCountryName: function (countryId) {
                return countryData()[countryId] != undefined ? countryData()[countryId].name : '';
            },

            /**
             * Get code
             * @param {Object} parent
             * @returns {String}
             */
            getCode: function (parent) {
                return _.isFunction(parent.getCode) ? parent.getCode() : 'shared';
            },

            /**
             * @return {Boolean}
             */
            radioSelectedSet: function () {
                $('input:radio[name="billing-address-same-as-shipping"]').filter('[value="sameasdeliveryaddress"]').attr('checked', true);
            }


        });
    }
);
