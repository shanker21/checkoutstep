<?php
namespace MDC\CheckoutStep\Controller\DeliverySlot;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Model\OrderFactory;
use MDC\Requestbrochure\Model\CustomerchildFactory;
use MDC\Requestbrochure\Model\CustomeraddressFactory;
use MDC\Requestbrochure\Model\ParentcustomerFactory;
use Magento\Framework\App\ResourceConnection;

class orderConfirmation extends Action
{
    protected $dataobject;
    protected $logger;
    protected $checkoutStepHelper;
    protected $customerChildFactory;

    /**
     * @var Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;


    public function __construct(
        Context $context,
        EventManagerInterface $eventManager,
        OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerchildFactory $customerchild,
        CustomeraddressFactory $customeraddress,
        ParentcustomerFactory $parentcustomer,
        ResourceConnection $resourceConnection,
        \Magento\Eav\Model\Config $eavConfig,
        \MDC\CustomerRelation\Model\Customerchild $customerChildModel
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->eventManager = $eventManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_customerchild = $customerchild;
        $this->_customeraddress = $customeraddress;
        $this->_parentcustomer = $parentcustomer;
        $this->customerChildModel = $customerChildModel;
        $this->_resourceConnection = $resourceConnection;
        $this->_eavConfig = $eavConfig;
        parent::__construct($context);
    }


    public function execute()
    {

        $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/marketin_response_order.log');
		$logger = new \Laminas\Log\Logger();
		$logger->addWriter($writer);
        
        $dataPostCode = $this->getRequest()->getPost();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $dataPostCode['orderId'], 'eq')->create();
        $order = $this->orderRepository->getList($searchCriteria)->getFirstItem();
        $childId = $order->getChildId();
        $orderId = $order->getId();
        $logger->info("Start Order : ".$orderId); 
        $vId = $order->getVendorId();
        if(empty($childId)){
            $logger->info("Child not exist. Check in DB"); 
            $shippingAddress = $order->getShippingAddress();
            $firstname = $shippingAddress->getFirstname();
            $firstname = substr($firstname, 0, 1);
            $lastname = $shippingAddress->getLastname();
            $postcode = $shippingAddress->getPostcode(); //str_replace(" ","",$dataset['for-myself-post-code']);

            $child_data = $this->_customerchild->create();
            $childcollection = $child_data->getCollection();
            $childcollection->addFieldToFilter('child_customer_firstname',array("like"=>$firstname."%"));
            $childcollection->addFieldToFilter('child_customer_lastname',array("eq"=>$lastname));
            $childcollection->addFieldToFilter('child_customer_postcode',array("eq"=>$postcode));
            $childcollection = $childcollection->getLastItem();
            $childId = $childcollection->getChildId();

            if(empty($childId)){
                $logger->info("Child not exist. Create new"); 
                $title = $order->getShippingAddress()->getTitle();
                $attribute = $this->_eavConfig->getAttribute('customer_address', 'title');
                $customeraddress = $attribute->getSource()->getAllOptions();
                $titlename = '';
                foreach($customeraddress as $key => $value) {
                    if($value['value'] == $title) {
                        $titlename = $value['label'];
                    }
                }
                if ($titlename != '') {
                    if ((strtolower($titlename) == strtolower('Please select')) || (strtolower($titlename) == strtolower('Title'))) {
                        $titlename = '' ;
                    } 
                }

                if(empty($vId)){
                    $vId = $this->getVendorCode($shippingAddress->getPostcode());
                }

                $child_data = $this->_customerchild->create();
                $childdata['child_customer_firstname'] = $shippingAddress->getFirstname();
                $childdata['child_customer_lastname'] = $shippingAddress->getLastname();
                $childdata['child_customer_postcode'] = $shippingAddress->getPostcode();
                $childdata['email'] = $shippingAddress->getEmail();
                if($childdata['email'] == '')
                {
                    $childdata['customer_type'] = 0;
                }else{
                    $childdata['customer_type'] = 1;
                    $Parent = $this->_parentcustomer->create();
                    $Parentcustomer['email'] = $shippingAddress->getEmail();
                    $Parent->setData($Parentcustomer);
                    $Parent->save();
                }
                $childdata['type'] = 1;
                $childdata['title'] = $titlename;
                $childdata['origin_campaign_code'] = $dataPostCode['hearAbout'];
                $childdata['franchise_code'] = $vId;
                $logger->info(print_r($childdata, true));
                $child_data->setData($childdata);
                $child_data->save();
                $childcollection = $child_data->getCollection()->getLastItem();
                $childId = $childcollection->getChildId();
                
                $data1['address_line1'] = isset($shippingAddress->getStreet()[0]) ? $shippingAddress->getStreet()[0] : "";
                $data1['address_line2'] = isset($shippingAddress->getStreet()[1]) ? $shippingAddress->getStreet()[1] : "";
                $data1['postal_code'] = $shippingAddress->getPostcode();
                $data1['region'] = $shippingAddress->getRegion();
                $data1['country'] = $shippingAddress->getCountryId();
                $data1['address_name'] = $shippingAddress->getBuildingName();
                $data1['child_id'] = $childId;
                $logger->info(print_r($data1, true));
                $customeraddress = $this->_customeraddress->create();
                $customeraddress->setData($data1);
                $customeraddress->save();

                $addressdata = $customeraddress->getCollection()->getLastItem();
                $addressid = $addressdata->getAddressId();
                /*update address id in customer_child table start*/
                $customerChildCollection = $this->customerChildModel->load($childId);
                if($customerChildCollection){
                    $type = 2;
                    $customerChildCollection->setData('address_id',$addressid);
                    $customerChildCollection->setData('type',$type);
                    $customerChildCollection->save();
                }
            } else {
                $logger->info("Child exist in DB."); 
                $customerChildCollection = $this->customerChildModel->load($childId);
                if($customerChildCollection){
                    $childDataMarkResp = $customerChildCollection->getData('origin_campaign_code');
                    if(empty($childDataMarkResp)){
                        $logger->info("Update marketing response"); 
                        $customerChildCollection->setData("origin_campaign_code", $dataPostCode['hearAbout']);
                        $customerChildCollection->setData("update_dynamics_sync", 0);
                        $customerChildCollection->save();
                    }
                }
            }
        } else{
            $logger->info("Child exist in Order"); 
            $customerChildCollection = $this->customerChildModel->load($childId);
            if($customerChildCollection){
                $childDataMarkResp = $customerChildCollection->getData('origin_campaign_code');
                if(empty($childDataMarkResp)){
                    $logger->info("Update marketing response1"); 
                    $customerChildCollection->setData("origin_campaign_code", $dataPostCode['hearAbout']);
                    $customerChildCollection->setData("update_dynamics_sync", 0);
                    $customerChildCollection->save();
                }
            }
        }
        
        $order->setMarketingResponse($dataPostCode['hearAbout']);
        $this->orderRepository->save($order);

        $dataPostCode = [
            'order' => $this->orderFactory->create()->load($dataPostCode['orderId'])->getData(),
            'postData' => $dataPostCode
        ];
        $this->eventManager->dispatch(
            'order_confirmation_save_all_after',
            ['myEventData' => $dataPostCode]);
    }

    public function getVendorCode($postcode){
        $postcode = strtoupper(str_replace(" ","",$postcode));
        $twoletters = substr($postcode,0,2);
        $threeletters = substr($postcode,0,3);
        $fourletters = substr($postcode,0,4);

        // Query to get first 2 letter match result
        $connection = $this->_resourceConnection->getConnection();
        $table = $connection->getTableName('md_vendor_zipcode');
        $sql = $connection->select()
            ->from($table,
                [
                    "zipcode", "vendor_id"
                ]);
        $sql->where("zipcode like '".$twoletters."%' ");

        $result = $connection->fetchAll($sql); 
        $postcodevalues=[];
        $postcodevalues_4digit = [];
        $postcode_final = [];
        if(count($result) > 1){
            
            // Foreach to get 3 digit all and 4 digit exact result
            foreach ($result as $values){
                // if (str_contains($values['zipcode'], $fourletters)) {
                if ($values['zipcode'] == $fourletters) {
                    $postcodevalues_4digit = $values;
                    break;
                }
                if ($values['zipcode'] == $threeletters) {
                    $postcodevalues[] = $values;
                }
            }
        }else{ // Display 2 digit result
            if(!empty($result))
                $postcode_final = $result[0];
        }
        // If 3 digit have multiple result then display 4 digit result
        if(!empty($postcodevalues_4digit)){
            $postcode_final = $postcodevalues_4digit;
        }else{ // Display 3 digit result
            if(!empty($postcodevalues))
                $postcode_final = $postcodevalues[0];
        }
        if(!empty($postcode_final)){
            return $postcode_final['vendor_id'];
        }else{
            return "";
        }
    }
}