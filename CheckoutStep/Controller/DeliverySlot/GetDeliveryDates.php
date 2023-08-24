<?php
namespace MDC\CheckoutStep\Controller\DeliverySlot;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Model\Method\Logger;
use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;

class GetDeliveryDates extends Action
{
    protected $dataobject;
    protected $logger;
    protected $checkoutStepHelper;


    public function __construct(
        Context $context,
        \Magento\Framework\DataObject $dataobject,
        Logger $logger,
        CheckoutStepHelper $checkoutStepHelper
    ) {
        $this->dataobject = $dataobject;
        $this->logger = $logger;
        $this->checkoutStepHelper = $checkoutStepHelper;
        parent::__construct($context);
    }


    public function execute()
    {

        $this->layoutFactory = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $this->resultJsonFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $response = $this->dataobject;

        if (!$this->getRequest()->isPost()) {
            $response->setError(true);
        }
        $dataPostCode = $this->getRequest()->getPost();
        $noOfDays=$dataPostCode['no_of_days'];

        $getNextDeliveryDate = $this->checkoutStepHelper->getNextDeliveryDateMonthwise($dataPostCode['post_code'],$noOfDays);

        if(isset($getNextDeliveryDate)) {
            $array_result = $getNextDeliveryDate;
            if(!isset($array_result) && count($array_result)==0){
                $response->setError(true);
            } else {
                $response->setError(false);
                $response->setDeliveryDate($getNextDeliveryDate);
            }
        } else {
            $response->setError(true);
        }

        return $this->resultJsonFactory->setJsonData($response->toJson());
    }



}