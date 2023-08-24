<?php
namespace MDC\CheckoutStep\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class LoqateKeyProvider implements ConfigProviderInterface
{
    protected $helper;

    public function __construct(
        \MDC\CheckoutStep\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $loqatekey = $this->helper->getLoqateKey();

        $config = [
            'loqatekey' => $loqatekey
        ];
        return $config;
    }
}
