define([
    'jquery',
    'moment'
], function ($, moment) {
    'use strict';

    return function (validator) {

        validator.addRule(
            'titlevalidation',
            function(value) {
                if(value) {
                    return true;
                }
            },
            $.mage.__("Please select a title")
        );

        validator.addRule(
            'firstnamevalidation',
            function(value) {
                if(value) {
                    return true;
                }
            },
            $.mage.__("Please enter first name")
        );

        validator.addRule(
            'lastnamevalidation',
            function(value) {
                if(value) {
                    return true;
                }
            },
            $.mage.__("Please enter last name")
        );

        validator.addRule(
            'streetvalidation',
            function(value) {
                if(value) {
                    return true;
                }
            },
            $.mage.__("Please enter at least one address number and/or name")
        );

        validator.addRule(
            'streetlengthvalidation',
            function(value) {
                if(value.length <= 50) {
                    return true;
                }
            },
            $.mage.__("Please enter upto 50 characters only")
        );

        validator.addRule(
            'cityvalidation',
            function(value) {
                if(value) {
                    return true;
                }
            },
            $.mage.__("Please enter city name")
        );

        validator.addRule(
            'postcodevalidation',
            function(value) {
                if(value) {
                    return true;
                }
            },
            $.mage.__("Please enter postcode")
        );

        validator.addRule(
            'postcodeformatvalidation',
            function(value) {
                var regexp = /^[A-Z]{1,2}[0-9RCHNQ][0-9A-Z]?\s?[0-9][ABD-HJLNP-UW-Z]{2}$|^[A-Z]{2}-?[0-9]{4}$/;
                var zipcode = value.toUpperCase();
                return regexp.test(zipcode);
            },
            $.mage.__("Enter the full postcode to proceed further.")
        );

        validator.addRule(
            'telephonevalidation',
            function(value) {
                if($.isNumeric(value)) {
                    return true;
                }
            },
            $.mage.__("Please enter a phone number.")
        );

        validator.addRule(
            'telephonelengthvalidation',
            function(value) {
                if(value.length >= 10) {
                    return true;
                }
            },
            $.mage.__("Phone number must be at least 10 digits.")
        );

        return validator;
    };
});
