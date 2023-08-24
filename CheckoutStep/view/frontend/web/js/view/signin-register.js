define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'underscore',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'uiRegistry',
        'mage/translate',
        'Magento_Customer/js/action/login',
        'Magento_Checkout/js/model/authentication-messages',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url'
    ],
    function (
        $,
        ko,
        Component,
        _,
        stepNavigator,
        customer,
        quote,
        modal,
        checkoutDataResolver,
        checkoutData,
        registry,
        $t,
        loginAction,
        messageContainer,
        fullScreenLoader,
        url
    ) {
        'use strict';
        /**
         * check-login - is the name of the component's .html template
         */

        var checkoutConfig = window.checkoutConfig;

        return Component.extend({
            defaults: {
                template: 'MDC_CheckoutStep/signin-register',
                customerTitleConfig: window.checkoutConfig.customerTitle,
                tracks: {
                    email: true,
                    title: true,
                    firstname:true,
                    lastname:true,
                    telephone:true,
                    postcode:true
                },
                statefull: {
                    email: true,
                    title: true,
                    firstname:true,
                    lastname:true,
                    telephone:true,
                    postcode:true
                }
            },
            isCustomerLoggedIn: customer.isLoggedIn,
            //add here your logic to display step,
            isVisible: ko.observable(true),
            errorValidationMessage: ko.observable(false),
            //step code will be used as step content id in the component template
            stepCode: 'signinRegister',
            //step title value
            stepTitle: 'Sign In / Register',
            forgotPasswordUrl: checkoutConfig.forgotPasswordUrl,
            autocomplete: checkoutConfig.autocomplete,
            isRegisterPasswordVisible: ko.observable(false),
            isRegister: ko.observable(false),
            /**
             *
             * @returns {*}
             */
            initialize: function () {
                this._super();
                // register your step
                stepNavigator.registerStep(
                    this.stepCode,
                    //step alias
                    null,
                    this.stepTitle,
                    //observable property with logic when display step or hide step
                    this.isVisible,

                    null,

                    /**
                     * sort order value
                     * 'sort order value' < 10: step displays before shipping step;
                     * 10 < 'sort order value' < 20 : step displays between shipping and payment step
                     * 'sort order value' > 20 : step displays after payment step
                     */
                    5
                );

                this.isRegister.subscribe(function (newValue) {
                    if(newValue){
                        this.isRegisterPasswordVisible(true);
                        this.addPasswordValidation();
                    }else{
                        this.isRegisterPasswordVisible(false);
                        this.removePasswordValidation();
                    }
                }.bind(this));

                return this;
            },

            /**
             * The navigate() method is responsible for navigation between checkout step
             * during checkout. You can add custom logic, for example some conditions
             * for switching to your custom step
             */
            navigate: function () {
                var self = this;
                self.isVisible(true);

            },

            /**
             * @param {HTMLElement} loginForm
             * @returns void
             */
            navigateToNextStep: function () {
                    stepNavigator.next();
            },

            /**
             * Provide login action.
             *
             * @param {HTMLElement} loginForm
             */
            login: function (loginForm) {
                var loginData = {},
                    formDataArray = $(loginForm).serializeArray();

                formDataArray.forEach(function (entry) {
                    loginData[entry.name] = entry.value;
                });

                if ($(loginForm).validation() &&
                    $(loginForm).validation('isValid')
                ) {
                    fullScreenLoader.startLoader();
                    var redirectUrl=url.build('checkout#shipping');
                    loginAction(loginData, redirectUrl, undefined, messageContainer).always(function () {
                        fullScreenLoader.stopLoader();
                    });
                }
            },

            /**
             * Provide register action.
             *
             * @param {HTMLElement} registerForm
             */
            register: function (registerForm) {
                if ($(registerForm).validation() &&
                    $(registerForm).validation('isValid')
                ) {

                    $.ajax({
                        url:  url.build('checkoutstep/account/createPost'),
                        data: $('#create-account').serialize(),
                        type: 'post',
                        dataType: 'json',
                        showLoader: true,
                    }).done(function (data) {
                        if (data) {
                            if(data.error) {
                                var messageBox = $('#error-message');
                                messageBox.html(data.message);
                                messageBox.show();
                                setTimeout(function () {
                                    messageBox.hide('blind', {}, 500)
                                }, 5000);
                            } else {
                                if (data.url) {
                                    window.location.href = data.url;
                                }
                                if (data.next) {
                                    stepNavigator.next();
                                }
                            }
                        }
                    });

                }
            },

            showpwd: function () {
                var loginPassword = $("#login-password").attr('type');
                if(loginPassword === 'password')
                {
                    $("#login-password").attr('type','text');
                    $("#lshowpwd").text($t('Hide password'));
                }
                else{ $("#login-password").attr('type','password');
                    $("#lshowpwd").text($t('Show password'));
                }
            },


            openEmailModel: function () {
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: 'Why are we asking for this?',
                    buttons: [{
                        text: $.mage.__('Okay'),
                        class: '',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                };

                var popup = modal(options, $("#showEmailModal"));
                $("#showEmailModal").modal("openModal");

            },

            openPhoneModel: function () {
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: 'Why are we asking for this?',
                    buttons: [{
                        text: $.mage.__('Okay'),
                        class: '',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                };

                var popup = modal(options, $("#showPhoneModal"));
                $("#showPhoneModal").modal("openModal");
            },

            addPasswordValidation: function () {
                $("#password").attr('data-validate','{required:true, \'validate-customer-password\':true}');
                $("#password").attr('data-msg-required',$t('Password can\'t be empty'));
                $("#password-confirmation").attr('data-validate','{required:true, equalTo:\'#password\'}');
                $("#password-confirmation").attr('data-msg-required',$t('Password confirmation can\'t be empty'));
                $("#password-confirmation").attr('data-msg-equalto',$t('Password confirmation does not match'));
            },

            removePasswordValidation: function () {
                $("#password").removeAttr('data-validate');
                $("#password").removeAttr('data-msg-required');
                $("#password-confirmation").removeAttr('data-validate');
                $("#password-confirmation").removeAttr('data-msg-required');
                $("#password-confirmation").removeAttr('data-msg-equalto');
            }

        });
    }
);