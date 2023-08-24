<?php


namespace MDC\CheckoutStep\Block;


use Magedelight\Backend\Block\Template;

class CheckUnavailProducts extends Template
{

    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getUnavailableProducts() {
        $data = $this->getData();

        return $data;
    }
}