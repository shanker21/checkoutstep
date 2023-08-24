<?php
namespace MDC\CheckoutStep\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use MDC\CheckoutStep\Model\Config\Source\DeliverySlotDate;
use Magento\Customer\Model\AttributeMetadataDataProvider as AttributeMetadataDataProviderAlias;
use Magento\Ui\Component\Form\AttributeMapper;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;

class LayoutProcessorPlugin
{
    /**
     * @var DeliverySlotDate
     */
    public $customerAttribute;

    /**
     * @var AttributeMetadataDataProviderAlias
     */
    public $attributeMetadataDataProvider;

    /**
     * @var AttributeMapper
     */
    public $attributeMapper;

    /**
     * @var AttributeMerger
     */
    public $merger;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var null
     */
    public $quote = null;

    /**
     * LayoutProcessorPlugin constructor.
     *
     * @param DeliverySlotDate $customerAttribute
     * @param AttributeMetadataDataProviderAlias $attributeMetadataDataProvider
     * @param AttributeMapper $attributeMapper
     * @param AttributeMerger $merger
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     */

    public function __construct(
        DeliverySlotDate $customerAttribute,
        AttributeMetadataDataProviderAlias $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMerger $merger,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession
    ) {
        $this->customerAttribute = $customerAttribute;
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function aroundProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        \Closure $proceed,
        array $jsLayout
    ) {
        $jsLayoutResult = $proceed($jsLayout);

        if($this->getQuote()->isVirtual()) {
            return $jsLayoutResult;
        }

        /*  echo '<pre>';
          print_r($jsLayoutResult);
          die();*/

        if(isset($jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset'])) {


            $elements = $this->getAddressAttributes();

            $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['billing-address'] = $this->getCustomBillingAddressComponent($elements);


        }

        // Payment Page remove billing address
        if (isset($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']
        )) {
            foreach ($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'] as $key => $payment) {

                if($key == 'before-place-order') {
                    foreach ($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']['before-place-order']['children'] as $key => $payment) {
                        if($key == 'agreements') {
                            continue;
                        }
                    }
                }
                unset($jsLayoutResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]);
            }
        }

        if($this->customerSession->isLoggedIn()) {

            // Address Nickname show after login

            /* $customAttributeCode = 'address_nickname';
            $customField = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress.custom_attributes',
                    'customEntry' => null,
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/input',
                    'tooltip' => [
                        'description' => 'For easy reference on your next order',
                    ],
                ],
                'dataScope' => 'shippingAddress.custom_attributes' . '.' . $customAttributeCode,
                'label' => 'Address nickname',
                'provider' => 'checkoutProvider',
                'sortOrder' => 0,
                'validation' => [
                    'required-entry' => false
                ],
                'options' => [],
                'filterBy' => null,
                'customEntry' => null,
                'visible' => true,
                'value' => '' // value field is used to set a default value of the attribute
            ];

            // customer login action
            $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $customField; */
        }


        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['street'] = [
            'component' => 'Magento_Ui/js/form/components/group',
            //'label' => __('Street Address'), I removed main label
            'required' => true, //turn false because I removed main label
            'dataScope' => 'shippingAddress.street',
            'provider' => 'checkoutProvider',
            'sortOrder' => 70,
            'type' => 'group',
            'additionalClasses' => 'street_custom_class checkout-no-display',
            'children' => [
                [
                    'label' => __('Address line 1'),
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'customScope' => 'shippingAddress',
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'MDC_CheckoutStep/form/element/text',
                        'dataScope' => 'test12'
                    ],
                    'dataScope' => '0',
                    'provider' => 'checkoutProvider',
                    'validation' => ['required-entry' => true, "min_text_len‌​gth" => 1, "max_text_length" => 255],
                ],
                [
                    'label' => __('Address line 2'),
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'customScope' => 'shippingAddress',
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'MDC_CheckoutStep/form/element/text',
                    ],
                    'dataScope' => '1',
                    'provider' => 'checkoutProvider',
                    'validation' => ['required-entry' => false, "min_text_len‌​gth" => 1, "max_text_length" => 255],
                ]
            ]
        ];

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['telephone']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['prefix']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['firstname']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['lastname']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['addressnickname']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['addressnickname']['config']['notice'] = 'For easy reference on your next order';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['city']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/text2';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['city']['config']['sortOrder'] = '90';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['postcode']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/postcode';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['telephone']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']
        ['title']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/select';

        // Change ids of billing address

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['prefix']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['addressnickname']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['addressnickname']['config']['notice'] = 'For easy reference on your next order';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['postcode']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_postcode';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['city']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_city';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['city']['config']['sortOrder'] = '90';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['region']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_city';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['street']['children'][0]['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['street']['children'][1]['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['street']['children'][0]['label'] = 'Address line 1';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['street']['children'][1]['label'] = 'Address line 2';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['lastname']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['firstname']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['telephone']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_text';

        $jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
        ['title']['config']['elementTmpl'] = 'MDC_CheckoutStep/form/element/billing_select';

        unset($jsLayoutResult['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['billing-address']['children']['form-fields']['children']
            ['street']['children'][2]);


        if($this->customerSession->isLoggedIn()) {
            unset($jsLayoutResult['components']['checkout']['children']['steps']['children']['checkout-login-step']);
        }
        // region remove
        /* unset($jsLayoutResult['components']['checkout']['children']['steps']['children']
        ['shipping-step']['children']['shippingAddress']['children']
        ['shipping-address-fieldset']['children']['region']); */
        // region remove id
        /* unset($jsLayoutResult['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children']['region_id']); */
        // country field visible false
        /*  $jsLayoutResult['components']['checkout']['children']['steps']['children']
          ['shipping-step']['children']['shippingAddress']['children']
          ['shipping-address-fieldset']['children']['country_id']['visible'] = true;*/
        // telephone tooltip hide
        unset($jsLayoutResult['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children']['telephone']['config']['tooltip']);

        /*  echo '<pre>';
     print_r($jsLayoutResult);
     die();*/

        return $jsLayoutResult;
    }

    /**
     * Get Quote
     *
     * @return \Magento\Quote\Model\Quote|null
     */
    public function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    /**
     * Get all visible address attribute
     *
     * @return array
     */
    private function getAddressAttributes()
    {
        /** @var \Magento\Eav\Api\Data\AttributeInterface[] $attributes */
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer_address',
            'customer_address_edit'
        );

        $elements = [];
        foreach ($attributes as $attribute) {

            $code = $attribute->getAttributeCode();
            if ($code == 'delivery_note') {
                continue;
            }
            //if ($code == 'country_id') {
            // continue;
            // }
            /* if ($attribute->getIsUserDefined()) {
                 continue;
             }*/
            $elements[$code] = $this->attributeMapper->map($attribute);
            if (isset($elements[$code]['label'])) {
                $label = $elements[$code]['label'];
                $elements[$code]['label'] = __($label);
            }
        }

        return $elements;
    }

    /**
     * Prepare billing address field for shipping step for physical product
     *
     * @param $elements
     * @return array
     */
    public function getCustomBillingAddressComponent($elements)
    {

        return [
            'component' => 'MDC_CheckoutStep/js/view/billing-address',
            'displayArea' => 'billing-address',
            'provider' => 'checkoutProvider',
            'deps' => ['checkoutProvider'],
            'dataScopePrefix' => 'billingAddress',
            'children' => [
                'form-fields' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'additional-fieldsets',
                    'children' => $this->appendPostCode($elements)
                ],
            ],
        ];
    }


    public function appendPostCode($elements) {

        $addPostcode['custom_postcode'] =  [
            'component' => 'MDC_CheckoutStep/js/view/dcustom-checkout-form',
            'displayArea' => 'additional-fieldsets',
            'sortOrder' => '40'
        ];
        $output = $this->merger->merge(
            $elements,
            'checkoutProvider',
            'billingAddress',
            [
                'country_id' => [
                    'sortOrder' => 115,
                ]
            ]
        );
        array_splice($output, 2, 0, $addPostcode);
        return $output;
    }

    public function getCustomDeliveryComponent(){
        return [
            'component' => 'MDC_CheckoutStep/js/view/delivery-slot',
            'displayArea' => 'billing-step',
            'provider' => 'checkoutProvider',
            'deps' => ['checkoutProvider'],
            'dataScopePrefix' => 'billingStep'
        ];
    }
}
