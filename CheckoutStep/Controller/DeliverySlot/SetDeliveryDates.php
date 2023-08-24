<?php
namespace MDC\CheckoutStep\Controller\DeliverySlot;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Model\Method\Logger;
use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;
use Magento\Checkout\Model\Cart as Cart;

class SetDeliveryDates extends Action
{
    protected $dataobject;
    protected $logger;
    protected $checkoutStepHelper;
    protected $cart;
    protected $quoteRepository;
    protected $zipcodeHelper;
    protected $deliveryFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\DataObject $dataobject,
        Logger $logger,
        CheckoutStepHelper $checkoutStepHelper,
        Cart $cart,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \MDC\Zipcode\Helper\Data $zipcodeHelper,
        \MDC\AvailableDeliveryDays\Model\DeliveryFactory $deliveryFactory,
        \Magedelight\Vendor\Model\VendorRepository $vendorRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
    )
    {
        $this->dataobject = $dataobject;
        $this->logger = $logger;
        $this->checkoutStepHelper = $checkoutStepHelper;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->zipcodeHelper = $zipcodeHelper;
        $this->deliveryFactory = $deliveryFactory;
        $this->vendorRepository = $vendorRepository;
        $this->_timezoneInterface = $timezoneInterface;
        parent::__construct($context);
    }


    public function execute()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/quote_rounds_data.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('--------------ROUNDS DATA SAVED IN QUOTE------------------');

        $this->layoutFactory = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $this->resultJsonFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $response = $this->dataobject;

        if (!$this->getRequest()->isPost()) {
            $response->setError(true);
        }
        $dataPostCode = $this->getRequest()->getPost();
        $deliveryDate = $dataPostCode['delivery_date'];
        $noOfSlots = $dataPostCode['no_of_slots'];
        $roundId = $dataPostCode['round_id'];
        $deliveryId = $dataPostCode['avaibility_Id'];
        $deliveryNote = $dataPostCode['delivery_note'];
        $quoteId = (int) $this->checkoutSession->getQuote()->getId();

        if ($quoteId != '' && $deliveryDate != '' && $noOfSlots != '' && $roundId != '') {

            $vendorId = $this->zipcodeHelper->getZipcodeVendorId();

            $vendorData = $this->vendorRepository->getById($vendorId);
            $cutOffTime = $vendorData->getData('cutoff_time');
            $leadDay = $vendorData->getData('lead_day');
            $today = $this->_timezoneInterface->date()->format('h:i:s');
            $todayTime = explode(":", $today);
            if ($cutOffTime != null) {
                $arrCutOffTime = explode(":", $cutOffTime);
                $today = $this->_timezoneInterface->date()->format('h:i:s');
                $todayTime = explode(":", $today);
                if ($arrCutOffTime[0] > $todayTime[0] && $arrCutOffTime[1] > $todayTime[1] && $arrCutOffTime[2] > $todayTime[2]) {
                    $leadDay = $leadDay + 1;
                }
                $deliveryDateOnly = explode(" ", $deliveryDate)[0];
                $finalDeliveryDate = $deliveryDateOnly . " " . $cutOffTime;
            } else {
                $deliveryDateOnly = explode(" ", $deliveryDate)[0];
                $finalDeliveryDate = $deliveryDateOnly . " 00:00:00";
            }
            if ($leadDay == 0) {
                $cutoff_datetime = date('Y-m-d H:i:s T', strtotime($finalDeliveryDate));
            } else if ($leadDay == 1) {
                $cutoff_datetime = date('Y-m-d H:i:s T', strtotime($finalDeliveryDate . '-1 day'));
            } else {
                $cutoff_datetime = date('Y-m-d H:i:s T', strtotime($finalDeliveryDate . ' -' . $leadDay . ' days'));
            }
            $fullMonth = date('F', strtotime($cutoff_datetime));
            $fullDay = date('l', strtotime($cutoff_datetime));
            try {
                $quote = $this->quoteRepository->get($quoteId);
                $quote->setData('delivery_date', $deliveryDate);
                $quote->setData('available_slots', $noOfSlots);
                $quote->setData('round_id', $roundId);
                // $quote->setData('vendor_id', $vendorId);
                $quote->setData('delivery_available_id', $deliveryId);
                $quote->setData('special_instructions', $deliveryNote);
                $this->quoteRepository->save($quote);
                $logger->info(json_encode($quote->getData()));
            }
            catch (\Exception $e)
            {
                $logger->info('Error : '.$e->getMessage());
            }

            $response->setError(false);
            $response->setDeliveryAvailableId($deliveryId);
            $response->setCutoffDatetime($cutoff_datetime);
            $response->setFullMonth($fullMonth);
            $response->setFullDay($fullDay);

        }
        return $this->resultJsonFactory->setJsonData($response->toJson());
    }

}