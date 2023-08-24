<?php
namespace MDC\CheckoutStep\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use MDC\CheckoutStep\Model\Config\Source\CustomerAttribute;

class CustomerAttributeProvider implements ConfigProviderInterface
{
    protected $customerAttribute;

    public function __construct(
        CustomerAttribute $customerAttribute,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->customerAttribute = $customerAttribute;
        $this->customerSession = $customerSession;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $customerTitleAttribute = $this->customerAttribute->toOptionArray();

        // print_r($customerTitleAttribute); exit;

        $config = [
            'customerTitle' => $customerTitleAttribute,
            'defaultPostcode' => $this->customerSession->getCustomer()->getPostcode()
        ];
        return $config;
    }
}
