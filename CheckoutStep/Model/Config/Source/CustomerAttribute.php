<?php
namespace MDC\CheckoutStep\Model\Config\Source;

use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;

class CustomerAttribute implements \Magento\Framework\Option\ArrayInterface
{

    protected $checkoutStepHelper;

    public function __construct(
        CheckoutStepHelper $checkoutStepHelper
    ) {
        $this->checkoutStepHelper = $checkoutStepHelper;
    }


    /**
     * Retrieve Custom Option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $getCustomerShippingAddress=$this->checkoutStepHelper->readCustomerShippingAddress();
        if(isset($getCustomerShippingAddress)) {
            $resultAttribute=[];
            foreach($getCustomerShippingAddress as $address) {

                $checkNickname = $address->getCustomAttributes();
                // print_r($checkNickname); exit;
                $checkNicknameVal = '';
                foreach ($checkNickname as $checkNickname) {
                    if($checkNickname->getAttributeCode()=='addressnickname') {
                        $checkNicknameVal = $checkNickname->getValue();
                    }
                }
                $label = "";
                $value = $address->getId();
                $addressLine = $address->getStreet();
                if($checkNicknameVal != '') {
                    $label = $checkNicknameVal;
                } else if (($address->getFirstname() != "") || ($address->getLastname() != "")) {
                    $label = $address->getFirstname() . ' ' . $address->getLastname();
                } else if ($address->getPostcode() != '') {
                    $label =  $address->getPostcode();
                }
                else if ($addressLine[0] != '') {
                    $label =  $addressLine[0];
                }
                if ($label!='' && $value!='') {
                    $resultAttribute[] = ['value' => $value,
                        'label' => $label,
                        'nickname' => $checkNicknameVal,
                        'firstname' => $address->getFirstname(),
                        'lastname' => $address->getLastname(),
                        'postcode' => $address->getPostcode(),
                        'company' => $address->getCompany(),
                        'city' => $address->getCity(),
                        'addressline1' => $addressLine[0],
                        'addressline2' => count($addressLine) > 1 ? $addressLine[1] : '',
                        'phonenumber' => $address->getTelephone()
                    ];
                }
            }

            return $resultAttribute;
        }
    }
}
