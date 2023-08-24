define(
    [
        'jquery',
        'uiComponent',
        'ko',
        'underscore',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/action/check-email-availability',
        'Magento_Customer/js/action/login',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/step-navigator',
        'mage/url',
        'mage/validation',
    ],
    function ($, Component, ko,  _, customer, checkEmailAvailability, loginAction, quote, checkoutData, fullScreenLoader,stepNavigator, url) {
        'use strict';

        var validatedEmail;

        if(customer.isLoggedIn()) {
           window.location = url.build('checkout')+'#shipping';
        }

        if (!checkoutData.getValidatedEmailValue() &&
            window.checkoutConfig.validatedEmailValue
        ) {
            checkoutData.setInputFieldEmailValue(window.checkoutConfig.validatedEmailValue);
            checkoutData.setValidatedEmailValue(window.checkoutConfig.validatedEmailValue);
        }

        validatedEmail = checkoutData.getValidatedEmailValue();

        if (validatedEmail && !customer.isLoggedIn()) {
            quote.guestEmail = validatedEmail;
        }

        /**
         * check-login - is the name of the component's .html template
         */
        return Component.extend({
            defaults: {
                template: 'MDC_CheckoutStep/checkout-login',
                email: checkoutData.getInputFieldEmailValue(),
                emailFocused: false,
                isLoading: false,
                isPasswordVisible: false,
                isPasswordNotVisible: true,
                listens: {
                    email: 'emailHasChanged',
                    emailFocused: 'validateEmail'
                },
                ignoreTmpls: {
                    email: true
                }
            },

            checkDelay: 2000,
            checkRequest: null,
            isEmailCheckComplete: null,
            isCustomerLoggedIn: customer.isLoggedIn,
            forgotPasswordUrl: window.checkoutConfig.forgotPasswordUrl,
            emailCheckTimeout: 0,
            isVisible: ko.observable(true),
            isLogedIn: customer.isLoggedIn(),
            stepCode: 'isLogedCheck',
            stepTitle: 'User Details',
            quoteIsVirtual: quote.isVirtual(),

            /**
             *
             * @returns {*}
             */
            initialize: function () {
                this._super();
                stepNavigator.registerStep(
                    this.stepCode,
                    null,
                    this.stepTitle,
                    this.isVisible,

                    _.bind(this.navigate, this),

                    /**
                     * sort order value
                     * 'sort order value' < 10: step displays before shipping step;
                     * 10 < 'sort order value' < 20 : step displays between shipping and payment step
                     * 'sort order value' > 20 : step displays after payment step
                     */
                    9
                );

                return this;
            },

            /**
             * Initializes regular properties of instance.
             *
             * @returns {Object} Chainable.
             */
            initConfig: function () {
                this._super();

                this.isPasswordVisible = this.resolveInitialPasswordVisibility();
                if(this.resolveInitialPasswordVisibility()) {
                    this.isPasswordNotVisible = false;
                } else {
                    this.isPasswordNotVisible = true;
                }

                return this;
            },

            /**
             * Initializes observable properties of instance
             *
             * @returns {Object} Chainable.
             */
            initObservable: function () {
                this._super()
                    .observe(['email', 'emailFocused', 'isLoading', 'isPasswordVisible', 'isPasswordNotVisible']);

                return this;
            },/**
             * Callback on changing email property
             */
            emailHasChanged: function () {
                $('#first-continue').attr("disabled", "disabled");
                var self = this;
               
                clearTimeout(this.emailCheckTimeout);

                if (self.validateEmail()) {
                    quote.guestEmail = self.email();
                    checkoutData.setValidatedEmailValue(self.email());
                }
                this.emailCheckTimeout = setTimeout(function () {
                    if (self.validateEmail()) {
                        self.checkEmailAvailability();
                    } else {
                        self.isPasswordVisible(false);
                        self.isPasswordNotVisible(true);
                    }
                }, self.checkDelay);

                checkoutData.setInputFieldEmailValue(self.email());
            },

            /**
             * Check email existing.
             */
            checkEmailAvailability: function () {
                this.validateRequest();
                this.isEmailCheckComplete = $.Deferred();
                this.isLoading(true);
                this.checkRequest = checkEmailAvailability(this.isEmailCheckComplete, this.email());
                $('#first-continue').removeAttr('disabled');

                $.when(this.isEmailCheckComplete).done(function () {
                    this.isPasswordVisible(false);
                    this.isPasswordNotVisible(true);
                   $('#checkout-next').show();
                    checkoutData.setCheckedEmailValue('');
                }.bind(this)).fail(function () {
                    this.isPasswordVisible(true);
                    this.isPasswordNotVisible(false);
                    $('#checkout-next').hide();
                    checkoutData.setCheckedEmailValue(this.email());
                }.bind(this)).always(function () {
                    this.isLoading(false);
                }.bind(this));
            },

            /**
             * If request has been sent -> abort it.
             * ReadyStates for request aborting:
             * 1 - The request has been set up
             * 2 - The request has been sent
             * 3 - The request is in process
             */
            validateRequest: function () {
                if (this.checkRequest != null && $.inArray(this.checkRequest.readyState, [1, 2, 3])) {
                    this.checkRequest.abort();
                    this.checkRequest = null;
                }
            },

            /**
             * Local email validation.
             *
             * @param {Boolean} focused - input focus.
             * @returns {Boolean} - validation result.
             */
            validateEmail: function (focused) {
                var loginFormSelector = 'form[data-role=email-with-possible-login]',
                    usernameSelector = loginFormSelector + ' input[name=username]',
                    loginForm = $(loginFormSelector),
                    validator,
                    valid;

                loginForm.validation();

                if (focused === false && !!this.email()) {
                    valid = !!$(usernameSelector).valid();

                    if (valid) {
                        $(usernameSelector).removeAttr('aria-invalid aria-describedby');
                    }

                    return valid;
                }

                if (loginForm.is(':visible')) {
                    validator = loginForm.validate();

                    return validator.check(usernameSelector);
                }

                return true;
            },

            /**
             * Log in form submitting callback.
             *
             * @param {HTMLElement} loginForm - form element.
             */
            login: function (loginForm) {
                var loginData = {},
                    formDataArray = $(loginForm).serializeArray();

                formDataArray.forEach(function (entry) {
                    loginData[entry.name] = entry.value;
                });

                if (this.isPasswordVisible() && $(loginForm).validation() && $(loginForm).validation('isValid')) {
                    fullScreenLoader.startLoader();
                    loginAction(loginData, checkoutConfig.checkoutUrl).always(function () {
                        fullScreenLoader.stopLoader();
                       // window.location = url.build('checkout')+'#shipping';
                    });
                }
            },

            /**
             * Resolves an initial state of a login form.
             *
             * @returns {Boolean} - initial visibility state.
             */
            resolveInitialPasswordVisibility: function () {

                if (checkoutData.getInputFieldEmailValue() !== '' && checkoutData.getCheckedEmailValue() !== '') {
                    $('#checkout-next').hide();
                    return true;
                } else {
                    $('#checkout-next').show();
                }

                if (checkoutData.getInputFieldEmailValue() !== '') {
                    return checkoutData.getInputFieldEmailValue() === checkoutData.getCheckedEmailValue();
                }

                return false;
            },

            /**
             * The navigate() method is responsible for navigation between checkout step
             * during checkout. You can add custom logic, for example some conditions
             * for switching to your custom step
             */
            navigate: function () {

            },

            /**
             * @returns void
             */
            navigateToNextStep: function () {
                var loginFormSelector = 'form[data-role=email-with-possible-login]';
                var emailValidationResult = customer.isLoggedIn();
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (!emailValidationResult) {
                    $(loginFormSelector + ' input[name=username]').focus();

                    return false;
                } else {
                    stepNavigator.next();
                }
            },

            /**
             * @returns void
             */
            navigateToNext: function () {
                stepNavigator.next();
            },

            showHidePassword: function(){
                var m = $("#customer-password").attr('type');
                if(m === 'password')
                {
                    $("#customer-password").attr('type','text');
                    $("#showpassword").text('Hide password');
                }
                else{ $("#customer-password").attr('type','password');
                    $("#showpassword").text('Show password');
                }
               
            }

        });
    }
);