<?php
namespace MDC\CheckoutStep\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use MDC\CheckoutStep\Model\Config\Source\DeliverySlotDate;

class DeliverySlotDateProvider implements ConfigProviderInterface
{
    protected $deliverySlotDate;

    public function __construct(
        DeliverySlotDate $deliverySlotDate
    )
    {
        $this->deliverySlotDate = $deliverySlotDate;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $deliverySlotDate = $this->deliverySlotDate->toOptionArray();

        $config = [
            'deliverySlotDate' => $deliverySlotDate
        ];
        return $config;
    }
}
