<?php
namespace MDC\CheckoutStep\Controller\DeliverySlot;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\Method\Logger;
use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;
use Magento\Checkout\Model\Cart as Cart;

class RemoveProducts extends Action
{
    protected $dataobject;
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
        Cart $cart,
        CheckoutStepHelper $checkoutStepHelper
    ) {
        $this->dataobject = $dataobject;
        $this->logger = $logger;
        $this->_resultPageFactory = $resultPageFactory;
        $this->cart = $cart;
        $this->checkoutStepHelper = $checkoutStepHelper;
        parent::__construct($context);
    }


    public function execute()
    {
        $this->layoutFactory = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $this->resultJsonFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultPage = $this->_resultPageFactory->create();
        $dataPostCode = $this->getRequest()->getPost();
        $response = $this->dataobject;
        if (!$this->getRequest()->isPost()) {
            $response->setError(true);
        }

        $pid = explode(',', $dataPostCode['removeIds']);
        $arrPass = [];

        foreach ($pid as $prodId) {
            $arrPass[$prodId] = $prodId;
        }

        if(isset($dataPostCode['allRemove'])) {
            $quote = $this->cart->getQuote();
            $zipCode = $quote->getShippingAddress()->getPostCode();
            $response->setPostcode($zipCode);
        }

        $this->checkoutStepHelper->removeCartProductsByIds($arrPass);
        $response->setRemove(true);
        return $this->resultJsonFactory->setJsonData($response->toJson());

    }



}