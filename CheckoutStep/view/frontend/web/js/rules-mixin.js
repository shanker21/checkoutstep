define(['mage/translate', "jquery"], function($t, $) {
    'use strict';

    return function(rules) {
        rules['validate-country'] = {
            handler: function (value) {
                if (value === "FR") {
                    var zipValue = $('input[name="postcode"]').val();
                    if (zipValue) {
                        return !(zipValue.startsWith("97") || zipValue.startsWith("98"));
                    }
                }

                return true;
            },
            message: $t('You cannot choose France for DOM-TOM Zip Code')
        };
        return rules;
    };
});