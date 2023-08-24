<?php
namespace MDC\CheckoutStep\Controller\DeliverySlot;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\Method\Logger;
use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;
use Magento\Customer\Model\Session;

class tempOrderSync extends Action
{
    protected $dataobject;
    protected $logger;
    protected $checkoutStepHelper;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;


    public function __construct(
        Context $context,
        Session $customerSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        PageFactory $resultPageFactory
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->resourceConnection = $resourceConnection;
        $this->session = $customerSession;
        parent::__construct($context);
    }


    public function execute()
    {
        $dataPostCode = $this->getRequest()->getPost();
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('temp_sync_order');

        if($dataPostCode['needtopin'] == 1) {
            $message = __('Please register to pin orders to favorites');
        } else {
            $message = __('Please register to view orders');
        }

        $this->session->setUserExistErrorMessage($message);

        $sql = "Insert into " . $table . "(email,order_id,need_to_pin,nickname) values ('".$dataPostCode['email']."','".$dataPostCode['orderId']."','".$dataPostCode['needtopin']."','".$dataPostCode['nickname']."')";
        // Update avaibility table
        $connection->query($sql);
    }

}