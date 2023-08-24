<?php

declare(strict_types=1);

namespace MDC\CheckoutStep\Block\Checkout;

use Amasty\JetTheme\Model\StoreThemeMapper;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Psr\Log\LoggerInterface;

class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @var StoreThemeMapper
     */
    private $storeThemeMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        StoreThemeMapper $storeThemeMapper,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->storeThemeMapper = $storeThemeMapper;
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout): array
    {
        try {
            if (!$this->storeThemeMapper->isCurrentThemeJetTheme()) {
                return $jsLayout;
            }

            if (isset($jsLayout["components"]["checkout"]["children"]["sidebar"]["children"]["shipping-information"])) {
                $shippingInformation = $jsLayout["components"]["checkout"]["children"]["sidebar"]["children"]
                ["shipping-information"];

                unset($jsLayout["components"]["checkout"]["children"]["sidebar"]["children"]
                    ["shipping-information"]);

            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $jsLayout;
    }

}
