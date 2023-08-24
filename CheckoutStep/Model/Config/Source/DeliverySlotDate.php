<?php
namespace MDC\CheckoutStep\Model\Config\Source;

use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;

class DeliverySlotDate implements \Magento\Framework\Option\ArrayInterface
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
        $getNextDeliveryDate = $this->checkoutStepHelper->getNextDeliveryDate();
        return $getNextDeliveryDate;
    }
}