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
        'mage/translate',
        'Magento_Checkout/js/action/select-shipping-address',
        'mage/url',
        'uiRegistry'
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
        $t,
        selectShippingAddress,
        url,
        registry
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'MDC_CheckoutStep/delivery-slot',
                deliveryslotOptions: window.checkoutConfig.deliverySlot,
                deliveryslotMessage: ko.observable(''),
                retrievedSlots: ko.observableArray([]),
                isDeliveryDate: ko.observable(''),
            },
            /**discount_code
             *
             * @returns {*}
             */
            initialize: function () {
                this._super();
                return this;
            },

            /**
             * @return {exports.initObservable}
             */
            initObservable: function () {
                this._super()
                    .observe({
                        isDeliveryDate: true
                    });

                return this;
            },

            myMessage: function () {
                var responseMessage = '';

                if (quote.shippingAddress()) {
                    var getPostCode = quote.shippingAddress().postcode;
                    let deliveryDate = deliveryPayload[0];
                    if (getPostCode !== '') {
                        $.ajax({
                            url: url.build('checkoutstep/deliveryslot/postMessage'),
                            data: ({ post_code: getPostCode, deliveryDate: deliveryDate }),
                            type: 'post',
                            dataType: 'json',
                            showLoader: true,
                        }).done(function (data) {
                            if (data) {
                                var deliverycomponent = registry.get('checkout.steps.billing-step.payment.beforeMethods.delivery-slot');
                                if (data.error) {
                                    $("#delivery_date_select").hide();

                                } else {
                                    $("#delivery_date_select").show();
                                }
                                $.cookie('zipcode_availability', data.post_code);
                                $.cookieStorage.set('zipcode_availability', data.post_code);
                                deliverycomponent.retrievedSlots(data.delivery_date);
                                deliverycomponent.deliveryslotMessage(data.message);
                                window.cutoffTime = data.cutoff_time;
                                window.leadDay = data.lead_day;
                                window.fullDay = data.full_day;
                                window.fullMonth = data.full_month;
                            }
                        });
                    }
                }
            },

            /**
             * @return {Boolean}
             */
            setDeliveryDate: function (data, event) {
                if (data.value !== '') {
                    console.log('calling setDeliveryDate method');
                    $('.error-select-delivery').css('display', 'none');
                    $('#place-order-trigger').removeAttr('disabled');
                    $('#place-order-trigger').removeProp('disabled');
                    $("#place-order-trigger").prop("disabled", false);
                    var deliveryPayload = data.value.split("|");
                    let deliveryDate = deliveryPayload[0];
                    let roundId = deliveryPayload[1];
                    let noOfSlots = deliveryPayload[2];
                    let avaibilityId = deliveryPayload[3];
                    $("#delivery_date").val(data.value);
                    $.ajax({
                        url: url.build('checkoutstep/deliveryslot/setDeliveryDates'),
                        data: ({ delivery_date: deliveryDate, round_id: roundId, no_of_slots: noOfSlots, avaibility_Id: avaibilityId, delivery_note: $("#delivery-notes").val() }),
                        type: 'post',
                        dataType: 'json',
                        showLoader: false,
                    }).done(function (data) {

                        //Calculate final delivery date
                        var cutoffTime = window.cutoffTime;
                        var leadDay = window.leadDay;
                        if (cutoffTime != null) {
                            var arrCutOffTime = cutoffTime.split(":");
                            var today = new Date();
                            today = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();

                            var todayTime = today.split(":");
                            if (arrCutOffTime[0] > todayTime[0] && arrCutOffTime[1] > todayTime[1] && arrCutOffTime[2] > todayTime[2]) {
                                leadDay = leadDay + 1;
                            }

                            var deliveryDateOnly = deliveryDate.split(" ")[0];
                            var finalDeliveryDate = deliveryDateOnly + " " + cutoffTime;
                        } else {
                            var deliveryDateOnly = deliveryDate.split(" ")[0];
                            var finalDeliveryDate = deliveryDateOnly;
                        }

                        if (leadDay == 0) {
                            var cutoff_datetime = new Date(finalDeliveryDate);
                        } else if (leadDay == 1) {
                            var cutoff_datetime = new Date(finalDeliveryDate);
                            cutoff_datetime.setDate(cutoff_datetime.getDate() - 1);
                        } else {
                            var cutoff_datetime = new Date(finalDeliveryDate);
                            if (leadDay) {
                                cutoff_datetime.setDate(cutoff_datetime.getDate() - leadDay);
                            }
                        }

                        let tmpDate = new Date(cutoff_datetime);

                        let tmpDateString = tmpDate.toString();
                        var GMT = tmpDateString.split('GMT');
                        var GMTtext = GMT[1].split('(');
                        var GMToperand = GMTtext[0].split(GMTtext[0].slice(0, 1));
                        var GMToperator = GMTtext[0].slice(0, 1);
                        var adjustbleHours = GMToperand[1].slice(0, 2);
                        var adjustbleMinutes = GMToperand[1].slice(2, 4);
                        let hour = tmpDate.getHours();
                        const weekday = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                        let fullDay = weekday[tmpDate.getDay()];
                        let fullMonth = tmpDate.toLocaleString('default', { month: 'long' });
                        // if (GMToperator == '+') {
                        //     hour = hour - parseInt(adjustbleHours);
                        // }
                        // if (GMToperator == '-') {
                        //     hour = hour + parseInt(adjustbleHours);
                        // }
                        let day = tmpDate.getDate();
                        let ampm = hour >= 12 ? "PM" : "AM";
                        // var fullMonth = data.full_month;
                        // var fullDay = data.full_day;
                        // var fullMonth = window.fullMonth;
                        // var fullDay = window.fullDay;
                        hour = (hour > 12 ? hour - 12 : hour);
                        hour = hour >= 10 ? hour : "0" + hour;
                        let minute = tmpDate.getMinutes();
                        minute = ('0' + minute).slice(-2);
                        // if (GMToperator == '+') {
                        //     minute = minute - parseInt(adjustbleMinutes);
                        // }
                        // if (GMToperator == '-') {
                        //     minute = minute + parseInt(adjustbleMinutes);
                        // }
                        // minute = (minute >= 10 ? minute : "0" + minute);
                        // minute = Math.abs(parseInt(minute));

                        if (day == 1 || day == 21 || day == 31)
                            day = day + "st";
                        else if (day == 2 || day == 22)
                            day = day + "nd";
                        else if (day == 3 || day == 23)
                            day = day + "rd";
                        else
                            day = day + "th";

                        var textMessage = 'Place your order before <span style="font-weight:600;">' + hour + ":" + minute + " " + ampm + '</span> on <span style="font-weight:600;">' + fullDay + ' ' + day + ' ' + fullMonth + '</span> to keep your delivery date booking.';
                        $('.message-select-delivery').html(textMessage);
                        $('.message-select-delivery').css('display', 'block');                        
                        //}
                    });
                }
            },

            deliverydatesRead: function () {
                var noOfDays = $("#delivery_date_select").val();
                var getPostCode = quote.shippingAddress().postcode;

                if (getPostCode !== '') {

                    $.ajax({
                        url: url.build('checkoutstep/deliveryslot/getDeliveryDates'),
                        data: ({ post_code: getPostCode, no_of_days: noOfDays }),
                        type: 'post',
                        dataType: 'json',
                        showLoader: true,
                    }).done(function (data) {
                        if (data) {
                            var deliverycomponent = registry.get('checkout.steps.billing-step.payment.beforeMethods.delivery-slot');
                            if (data.error) {
                                deliverycomponent.retrievedSlots(data.delivery_date);
                            } else {
                                deliverycomponent.retrievedSlots(data.delivery_date);
                            }
                        }
                    });

                }
            },
            /**
             * @return {Boolean}
            */
            canShowDeliveryDate: function (amount) {
                var items = quote.getItems();
                if (items[0]['product_type'] == "giftcard") {
                    $('.error-select-delivery').css('display', 'none');
                    $('#place-order-trigger').removeAttr('disabled');
                    return false;
                }
                else
                    return true;

            },

            /*   retrievedDeliverySlot: function () {
                  this.retrievedSlots(this.deliveryslotdateOptions);
               }
           */

        });
    }
);
