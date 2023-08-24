<?php
namespace MDC\CheckoutStep\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use MDC\CheckoutStep\Model\Config\Source\DeliverySlot;

class DeliverySlotProvider implements ConfigProviderInterface
{
    protected $deliverySlot;

    public function __construct(
        DeliverySlot $deliverySlot
    )
    {
        $this->deliverySlot = $deliverySlot;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $deliverySlot = $this->deliverySlot->toOptionArray();

        $config = [
            'deliverySlot' => $deliverySlot
        ];
        return $config;
    }
}
