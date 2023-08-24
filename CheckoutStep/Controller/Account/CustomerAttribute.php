<?php
namespace MDC\CheckoutStep\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use MDC\CheckoutStep\Helper\Data as CheckoutStepHelper;
use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\AddressRepositoryInterface;

class CustomerAttribute extends Action
{
    protected $eavConfig;
    protected $checkoutStepHelper;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected $customer;

    public function __construct(
        Context $context,
        \Magento\Eav\Model\Config $eavConfig,
        CheckoutStepHelper $checkoutStepHelper,
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\Session $customer
    ) {
        $this->_eavConfig = $eavConfig;
        $this->checkoutStepHelper = $checkoutStepHelper;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->addressRepository = $addressRepository;
        $this->customer = $customer;
        parent::__construct($context);
    }


    public function execute()
    {

        $customerId = $this->customer->getCustomer()->getId(); //DYNAMIC_CUSTOMER_ID
        $customerAddress = $this->getCustomerAddresses($customerId);
        /*echo '<pre>';
        print_r($customerAddress);
        die();*/
        $resultAttribute=[];
        foreach($customerAddress as $address) {
            // var_dump($address);
            $checkNickname=$address->getCustomAttributes();
            $checkNicknameVal = '';
            foreach ($checkNickname as $checkNickname) {
                if($checkNickname->getAttributeCode()=='address_nickname') {
                    $checkNicknameVal= $checkNickname->getValue();
                }
            }
            if($checkNicknameVal != '') {
                $label = $checkNicknameVal;
            } else {
                $label = $address->getFirstname() . ' ' . $address->getLastname();
            }
            $value = $address->getId();
            $resultAttribute[]=['value' => $value , 'label' => $label];
        }

        echo '<pre>';
        print_r($resultAttribute);
        die();
    }


    public function getCustomerAddresses($customerId) {
        $addressesList = [];
        try {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'parent_id',$customerId)->create();
            $addressRepository = $this->addressRepository->getList($searchCriteria);
            foreach($addressRepository->getItems() as $address) {
                $addressesList[] = $address;
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $addressesList;
    }

}