<?php
namespace MDC\CheckoutStep\Controller\DeliverySlot;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\Method\Logger;
use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;

class PostMessage extends Action
{
    protected $dataobject;
    protected $quoteRepository;
    protected $logger;
    protected $checkoutStepHelper;
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;


    public function __construct(
        Context $context,
        \Magento\Framework\DataObject $dataobject,
        PageFactory $resultPageFactory,
        Logger $logger,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \MDC\Zipcode\Helper\Data $zipcodeHelper,
        \Magedelight\Vendor\Model\VendorRepository $vendorRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        CheckoutStepHelper $checkoutStepHelper
    ) {
        $this->dataobject = $dataobject;
        $this->logger = $logger;
        $this->_resultPageFactory = $resultPageFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutStepHelper = $checkoutStepHelper;
        $this->zipcodeHelper = $zipcodeHelper;
        $this->_timezoneInterface = $timezoneInterface;
        $this->vendorRepository = $vendorRepository;
        parent::__construct($context);
    }


    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Oospostmessage.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Inside check next delivery dates');
        $this->layoutFactory = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $this->resultJsonFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultPage = $this->_resultPageFactory->create();
        $dataPostCode = $this->getRequest()->getPost();
        $response = $this->dataobject;
        if (!$this->getRequest()->isPost()) {
            $response->setError(true);
        }

        if (isset($dataPostCode['email'])) {
            $quoteId = (int) $this->checkoutSession->getQuote()->getId();

            if ($quoteId != '') {
                $quote = $this->quoteRepository->get($quoteId);
                $quote->setData('customer_email', $dataPostCode['email']);
                $this->quoteRepository->save($quote);
            }
        }

        if (isset($dataPostCode['check_post'])) {

            $notAvailableProducts = $this->checkoutStepHelper->getOosProductsCheckout($dataPostCode['post_code']);

            // if (empty($notAvailableId['ids'])) {
            //     $logger->info('inside empty not available ids condition');
            //     $notAvailableId = $this->checkoutStepHelper->getNotAvailableOosIds($dataPostCode['post_code']);
            // }

            $notAvailableIds = $notAvailableProducts[0] ?? [];
            $notAvailableProducts[1]['allRemove'] = 0;
            if (is_array($notAvailableIds) && count($notAvailableIds) > 0) {
                $logger->info('count of not available ids: ' . count($notAvailableIds));

                if ($quote->getItemsQty() < 1) {
                    $notAvailableProducts[1]['allRemove'] = 1;
                }

                $block = $resultPage->getLayout()
                    ->createBlock('MDC\CheckoutStep\Block\CheckUnavailProducts', 'unavail-products', ['data' => $notAvailableProducts[1]])
                    ->setTemplate('MDC_CheckoutStep::unavailProduct.phtml')
                    ->toHtml();

                $response->setHtml($block);
                $response->setPopup(true);
                $logger->info('Post code passed to find next delivery days ' . $dataPostCode['post_code']);

                $getNextDeliveryDateMessage = $this->checkoutStepHelper->getNextDeliveryDateMessage($dataPostCode['post_code']);
                $getNextDeliveryDate = $this->checkoutStepHelper->getNextDeliveryDate($dataPostCode['post_code']);

                if (isset($getNextDeliveryDateMessage)) {
                    $array_result = $getNextDeliveryDateMessage;
                    if (isset($array_result) && $array_result['status'] == 0) {
                        $response->setMessage($array_result['message']);
                        $response->setError(true);
                    } else {
                        $response->setMessage($array_result['message']);
                        $response->setDeliveryDate($getNextDeliveryDate);
                        $response->setError(false);
                    }
                } else {
                    $response->setError(true);
                }
                return $this->resultJsonFactory->setJsonData($response->toJson());
            }
        }

        $response->setPopup(false);
        $getNextDeliveryDateMessage = $this->checkoutStepHelper->getNextDeliveryDateMessage($dataPostCode['post_code']);
        $getNextDeliveryDate = $this->checkoutStepHelper->getNextDeliveryDate($dataPostCode['post_code']);

        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($dataPostCode['post_code']);

        $vendorData = $this->vendorRepository->getById($vendorId);
        $cutOffTime = $vendorData->getData('cutoff_time');
        $leadDay = $vendorData->getData('lead_day');
        $fullMonth = date('F', strtotime($cutOffTime));
        $fullDay = date('l', strtotime($cutOffTime));

        if (isset($getNextDeliveryDateMessage) && !empty($getNextDeliveryDate)) {
            $array_result = $getNextDeliveryDateMessage;
            if (isset($array_result) && $array_result['status'] == 0) {
                $response->setMessage($array_result['message']);
                $response->setError(true);
            } else {
                $response->setMessage($array_result['message']);
                $response->setDeliveryDate($getNextDeliveryDate);
                $response->setCutoffTime($cutOffTime);
                $response->setLeadDay($leadDay);
                $response->setFullMonth($fullMonth);
                $response->setFullDay($fullDay);
                $response->setPostCode($dataPostCode['post_code']);
                $response->setError(false);
            }
        } else {
            $response->setError(true);
        }

        return $this->resultJsonFactory->setJsonData($response->toJson());
    }

}