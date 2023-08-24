<?php
namespace MDC\CheckoutStep\Plugin;

class AttributeMergerPlugin
{
    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $result)
    {
        // if (array_key_exists('firstname', $result)) {
        /*if($result['region_id']['dataScope']=='billingAddressfree.region_id') {
            $result['title']['additionalClasses'] = 'title_custom_class';
        }*/

        if (array_key_exists('addressnickname', $result)) {
            $result['addressnickname']['additionalClasses'] = 'nickname_custom_class';
        }

        if (array_key_exists('title', $result)) {
            $result['title']['additionalClasses'] = 'title_custom_class';
            $result['title']['validation']['titlevalidation'] = false;
            $result['title']['validation']['required-entry'] = false;
        }

        if (array_key_exists('region', $result)) {
            $result['region']['additionalClasses'] = 'region_custom_class checkout-no-display';
            $result['region']['validation']['regionvalidation'] = true;
            $result['region']['validation']['required-entry'] = false;
        }

        if (array_key_exists('region_id', $result)) {
             $result['region_id']['additionalClasses'] = 'region_id_custom_class';
             $result['region_id']['validation']['regionidvalidation'] = false;
             $result['region_id']['validation']['required-entry'] = false;
        }

        if (array_key_exists('company', $result)) {
            $result['company']['additionalClasses'] = 'company_custom_class checkout-no-display';
        }

        if (array_key_exists('prefix', $result)) {
            $result['prefix']['additionalClasses'] = 'prefix_custom_class';
        }

        if (array_key_exists('firstname', $result)) {
            $result['firstname']['additionalClasses'] = 'required firstname_custom_class';
            $result['firstname']['validation']['firstnamevalidation'] = true;
            $result['firstname']['validation']['required-entry'] = false;
        }

        if (array_key_exists('lastname', $result)) {
            $result['lastname']['additionalClasses'] = 'required lastname_custom_class';
            $result['lastname']['validation']['lastnamevalidation'] = true;
            $result['lastname']['validation']['required-entry'] = false;
        }

        if (array_key_exists('street', $result)) {
            $result['street']['children'][0]['additionalClasses'] = 'required street_custom_class checkout-no-display';
            $result['street']['children'][0]['validation']['streetvalidation'] = true;
            $result['street']['children'][0]['validation']['required-entry'] = false;
            $result['street']['children'][0]['validation']['streetlengthvalidation'] = true;


            $result['street']['children'][1]['additionalClasses'] = 'street_custom_class checkout-no-display';
            $result['street']['children'][1]['validation']['streetvalidation'] = false;
            $result['street']['children'][1]['validation']['required-entry'] = false;
            $result['street']['children'][1]['validation']['streetlengthvalidation'] = true;

        }

        if (array_key_exists('country_id', $result)) {
            $result['country_id']['additionalClasses'] = 'country_id_custom_class checkout-no-display';
            $result['country_id']['validation']['required-entry'] = false;
        }

        if (array_key_exists('city', $result)) {
            $result['city']['additionalClasses'] = 'required city_custom_class checkout-no-display';
            $result['city']['validation']['cityvalidation'] = true;
            $result['city']['validation']['required-entry'] = false;
        }

        if (array_key_exists('postcode', $result)) {
            $result['postcode']['additionalClasses'] = 'required postcode_custom_class checkout-no-display';
            $result['postcode']['validation']['postcodevalidation'] = true;
            $result['postcode']['validation']['postcodeformatvalidation'] = true;
            $result['postcode']['validation']['required-entry'] = false;
        }

        if (array_key_exists('telephone', $result)) {
            $result['telephone']['additionalClasses'] = 'required telephone_custom_class';
            $result['telephone']['validation']['telephonevalidation'] = true;
            $result['telephone']['validation']['telephonelengthvalidation'] = true;
            $result['telephone']['validation']['required-entry'] = false;
        }


        return $result;
    }
}