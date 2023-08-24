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
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/form-address-state',
    'Magento_Ui/js/modal/modal'
], function (
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
    addressList,
    formState,
    modal
) {
    'use strict';

    return function (target) {
        return target.extend({
            defaults: {
                template: 'MDC_CheckoutStep/address',
                retrievedUsers1: ko.observable([]),
                isCustomerLoggedIn: customer.isLoggedIn,
                isFormInline: addressList().length === 0
            },
            /**
             *
             * @returns {*}
             */
            initialize: function () {
                this.visible = ko.observable(false);
                this._super();
                $(document).on("keyup", '#shipping_postcode', function() {
                    var dInput = this.value;
                    if(dInput) {
                        var regexp = /^[A-Z]{1,2}[0-9RCHNQ][0-9A-Z]?\s?[0-9][ABD-HJLNP-UW-Z]{2}$|^[A-Z]{2}-?[0-9]{4}$/;
                        var zipcode = dInput.toUpperCase();
                        if(regexp.test(zipcode)) 
                        {
                            $('.form-shipping-address .postcode_custom_class .field-error').hide();
                        }
                        else{
                            $('.form-shipping-address .postcode_custom_class .field-error').show();
                        }
                    }
                });
                $(document).on("keyup", '#billing_postcode', function() {
                    var dInput = this.value;
                    if(dInput) {
                        var regexp = /^[A-Z]{1,2}[0-9RCHNQ][0-9A-Z]?\s?[0-9][ABD-HJLNP-UW-Z]{2}$|^[A-Z]{2}-?[0-9]{4}$/;
                        var zipcode = dInput.toUpperCase();
                        if(regexp.test(zipcode)) 
                        {
                            $('.billing-address-container .postcode_custom_class .field-error').hide();
                        }
                        else{
                            $('.billing-address-container .postcode_custom_class .field-error').show();
                        }
                    }
                });
                return this;
            },

            /**
             * Show address form popup
             */
            showShippingForm: function () {
                $('.select-dropdown-div').hide();
                $('.shipping-address-items').hide();
                $('#opc-new-shipping-address').show();
                $('.new-address-popup').hide();
                $('#checkEditForm').val('yes');
                $('#newadd').val(1);
            },
            /**
             * @inheritDoc
             */
            setShippingInformation: function () {
                $('#shipping-new-address-form input[name="firstname"]').trigger("change");
                $('#shipping-new-address-form input[name="lastname"]').trigger("change");
                $('#shipping-new-address-form input[name="company"]').trigger("change");
                $('#shipping-new-address-form input[name="street[0]"]').trigger("change");
                $('#shipping-new-address-form input[name="street[1]"]').trigger("change");
                $('#shipping-new-address-form input[name="city"]').trigger("change");
                $('#shipping-new-address-form input[name="postcode"]').trigger("change");
                $('#shipping-new-address-form input[name="telephone"]').trigger("change");
                $('#shipping-new-address-form input[name="Postcode"]').trigger("change");

                if ($('#checkEditForm').val() != '') {
                    if (!this.saveNewAddress()) {
                        $(".nickname_custom_class").attr('style', 'display: block !important');
                        $(".street_custom_class").attr('style', 'display: block !important');
                        $(".region_custom_class").attr('style', 'display: block !important')
                        $(".city_custom_class").attr('style', 'display: block !important')
                        $(".postcode_custom_class").attr('style', 'display: block !important')
                        $(".telephone_custom_class").attr('style', 'display: block !important')
                        $(".field[name='shippingAddress.region']").attr('style', 'display: block !important')
                        $(".field[name='billingAddress.region']").attr('style', 'display: block !important')
                        return false;
                    }
                }
                if (
                    this.validateShippingInformation()
                    &&
                    addressValidator.validateBillingInformation(this.isFormInline, this.source)
                    &&
                    this.deliverynoteValidation()
                ) {
                    registry.async('checkoutProvider')(function (checkoutProvider) {
                        var shippingAddressData = checkoutData.getShippingAddressFromData();

                        if (shippingAddressData) {
                            checkoutProvider.set(
                                'shippingAddress',
                                $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                            );
                        }
                    });

                    setShippingInformationAction().done(
                        function () {
                            // alert($('input[name="postcode"]').val());
                            if ($('input[name="postcode"]').val()) {
                                window.checkoutConfig.customerTitle.selPostcode = $('input[name="postcode"]').val();
                            }
                            console.log('window.Postcode=');
                            console.log(window.checkoutConfig.customerTitle.selPostcode);
                            var getPostCode = window.checkoutConfig.customerTitle.selPostcode; //quote.shippingAddress().postcode;
                            var email = $(".validator-email #customer-email").val();

                            if (email == undefined) {
                                var email = $(".email #customer-email").val();
                            }

                            $.ajax({
                                url: url.build('checkoutstep/deliveryslot/postMessage'),
                                data: ({ post_code: getPostCode, check_post: false, email: email }),
                                type: 'post',
                                dataType: 'json',
                                showLoader: true,
                            }).done(function (data) {

                                var deliverycomponent = registry.get('checkout.steps.billing-step.payment.beforeMethods.delivery-slot');

                                if (data) {
                                    window.cutoffTime = data.cutoff_time;
                                    window.leadDay = data.lead_day;
                                    window.fullDay = data.full_day;
                                    window.fullMonth = data.full_month;
                                    if (data.popup) {
                                        var CreateOrderPopup = {
                                            type: 'popup',
                                            responsive: true,
                                            innerScroll: true,
                                            parentModalClass: '_has-modal _csa-modal',
                                            modalClass: 'csa-modal-box no-products-popup',
                                            title: 'Products Unavailable',
                                            buttons: []
                                        };

                                        $("#delivery_date_select").val(-1);
                                        $("#delivery_date_select").show();
                                        deliverycomponent.retrievedSlots(data.delivery_date);
                                        deliverycomponent.deliveryslotMessage(data.message);

                                        $('#not-available-popup-modal').html(data.html);
                                        var popup = modal(CreateOrderPopup, $('#not-available-popup-modal'));
                                        $('#not-available-popup-modal').modal("openModal");
                                    } else {
                                        if (data.error) {
                                            $("#delivery_date_select").hide();
                                            deliverycomponent.retrievedSlots(data.delivery_date);
                                            deliverycomponent.deliveryslotMessage(data.message);
                                            window.scrollTo({ top: 0, behavior: 'smooth' });
                                            messageList.addErrorMessage({ message: $t('Sorry, Invalid Postal Code is for delivery slots.') });
                                        } else {
                                            $("#delivery_date_select").val(-1);
                                            $("#delivery_date_select").show();
                                            deliverycomponent.retrievedSlots(data.delivery_date);
                                            deliverycomponent.deliveryslotMessage(data.message);
                                            $.cookie('zipcode_availability', data.post_code);
                                            $.cookieStorage.set('zipcode_availability', data.post_code);
                                            deliverycomponent.retrievedSlots(data.delivery_date);
                                            deliverycomponent.deliveryslotMessage(data.message);
                                            window.cutoffTime = data.cutoff_time;
                                            window.leadDay = data.lead_day;
                                            window.fullDay = data.full_day;
                                            window.fullMonth = data.full_month;
                                            stepNavigator.next();
                                        }
                                    }
                                }
                            });
                        }
                    );
                } else {
                    $(".nickname_custom_class").attr('style', 'display: block !important');
                    $(".street_custom_class").attr('style', 'display: block !important');
                    $(".region_custom_class").attr('style', 'display: block !important')
                    $(".city_custom_class").attr('style', 'display: block !important')
                    $(".postcode_custom_class").attr('style', 'display: block !important')
                    $(".telephone_custom_class").attr('style', 'display: block !important')
                    $(".field[name='shippingAddress.region']").attr('style', 'display: block !important')
                    $(".field[name='billingAddress.region']").attr('style', 'display: block !important')
                    return false;
                }
            },
            populatedeliveryNote: function () {
                if (window.checkoutConfig.quoteData.special_instructions) {
                    var value = window.checkoutConfig.quoteData.special_instructions;
                    $("#delivery-notes").val(value);
                } else {
                    var address = window.checkoutConfig.customerTitle;
                    if (address.length != 0) {
                        var addressId = address[0]['value'];
                        $.ajax({
                            url: url.build('checkoutstep/deliveryslot/setDeliveryNote'),
                            data: ({ addressId: addressId }),
                            type: 'post',
                            dataType: 'json',
                            showLoader: false,
                        }).done(
                            function (response) {
                                var result = response['delivery_note']
                                $("#delivery-notes").val(result);

                            }
                        ).fail(
                            function (response) {
                                errorProcessor.process(response);

                            }
                        );
                    }

                }
            },
            deliverynoteValidation: function () {        
                console.log('deliverynotvalidation');
                var note = $("#delivery-notes").val();
                var length = note.length;
                console.log(length);
                console.log(note);
                if (note.length <= 220) {
                    return true;
                }
                else {
                    $('#deliverynote-error').show();
                    setTimeout(function () { $("#deliverynote-error").hide(); }, 5000);
                    return false;
                }
            }
        });
    }
});
