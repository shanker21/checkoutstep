<?php

namespace MDC\CheckoutStep\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\PaymentMethodManagement as PaymentCol;
use Magento\Customer\Api\AddressRepositoryInterface;

class ActiveMethods extends Action
{
    /**
     * Order Payment
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection
     */
    protected $_orderPayment;

    /**
     * Payment Helper Data
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * Payment Model Config
     *
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;

    /**
     * ResultPage Factory
     *
     * @var ResultPageFactory
     */
    protected $resultPageFactory;

    /**
     * Cart Totals
     *
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * Checkout session
     *
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Cart Interface
     *
     * @var CartInterface
     */
    protected $cartInterface;

    /**
     * Init
     *
     * @param Context $context
     * @param PaymentCol $orderPayment
     * @param Data $paymentHelper
     * @param Config $paymentConfig
     * @param JsonFactory $resultJsonFactory
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param CheckoutSession $checkoutSession
     * @param CartInterface $cartInterface
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @param \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $shippingInformation
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param array $data
     */

    public function __construct(
        Context $context,
        PaymentCol $orderPayment,
        Data $paymentHelper,
        Config $paymentConfig,
        JsonFactory $resultJsonFactory,
        CartTotalRepositoryInterface $cartTotalRepository,
        CheckoutSession $checkoutSession,
        CartInterface $cartInterface,
        \Magento\Quote\Api\Data\AddressInterface $address,
        \Magento\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $shippingInformation,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \MDC\StripeIntegration\Block\Form $formBlock,
        Address $quoteAddress,
        AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MDC\CustomerRelation\Model\Customerchild $customerChildModel,
        array $data = []
    ) {
        $this->_orderPayment = $orderPayment;
        $this->_paymentHelper = $paymentHelper;
        $this->_paymentConfig = $paymentConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->checkoutSession = $checkoutSession;
        $this->cartInterface = $cartInterface;
        $this->address = $address;
        $this->shippingInformationManagement = $shippingInformationManagement;
        $this->shippingInformation = $shippingInformation;
        $this->regionFactory = $regionFactory;
        $this->quoteAddress = $quoteAddress;
        $this->addressRepository = $addressRepository;
        $this->formBlock = $formBlock;
        $this->resourceConnection = $resourceConnection;
        $this->customerChildModel = $customerChildModel;

        parent::__construct($context);
    }

    /**
     * Get payment methods that have been used for orders
     *
     * @return array
     */
    public function getUsedPaymentMethods()
    {
        $cartId = $this->checkoutSession->getQuote()->getId();
        $collection = $this->_orderPayment->getList($cartId);

        foreach ($collection as $col) {
            $paymentMethods[] = array('code' => $col->getCode(), 'title' => $col->getTitle());
        }
        return $paymentMethods;
    }

    /**
     * Return quote totals data
     * @return array
     */
    private function getTotalsData()
    {
        /** @var \Magento\Quote\Api\Data\TotalsInterface $totals */
        $totals = $this->cartTotalRepository->get($this->checkoutSession->getQuote()->getId());
        $items = [];
        /** @var  \Magento\Quote\Model\Cart\Totals\Item $item */
        foreach ($totals->getItems() as $item) {
            $items[] = $item->__toArray();
        }
        $totalSegmentsData = [];
        /** @var \Magento\Quote\Model\Cart\TotalSegment $totalSegment */
        foreach ($totals->getTotalSegments() as $totalSegment) {
            $totalSegmentArray = $totalSegment->toArray();
            if (is_object($totalSegment->getExtensionAttributes())) {
                $totalSegmentArray['extension_attributes'] = $totalSegment->getExtensionAttributes()->__toArray();
            }
            $totalSegmentsData[] = $totalSegmentArray;
        }

        $totals->setItems($items);
        $totals->setTotalSegments($totalSegmentsData);
        $totalsArray = $totals->toArray();
        if (is_object($totals->getExtensionAttributes())) {
            $totalsArray['extension_attributes'] = $totals->getExtensionAttributes()->__toArray();
        }
        return $totalsArray;
    }

    /**
     * Sets shipping address in checkout
     *
     * @return void
     */
    public function saveShippingInformation($postData)
    {
        if ($this->checkoutSession->getQuote()) {
            $cartId = $this->checkoutSession->getQuote()->getId();
            $shippingAddress = $this->getShippingAddressInformation($postData);
            $this->shippingInformationManagement->saveAddressInformation($cartId, $shippingAddress);
        }
    }

    /**
     * prepare shipping address from post params
     *
     * @return void
     */
    protected function getShippingAddressInformation($postData)
    {
        $shippingCarrierCode = $postData['shipping_carrier_code'];
        $shippingMethodCode = $postData['shipping_method_code'];
        $nickName = $postData['nickName'];

        $firstName = $postData['shipping_address']['firstname'];
        $lastName = $postData['shipping_address']['lastname'];
        $countryId = $postData['shipping_address']['countryId'];
        $pincode = $postData['shipping_address']['postcode'];
        if (! preg_match('/\s/',$pincode)) {
            $pincode = substr_replace($pincode, ' ' . substr($pincode, -3), -3);
        }
        $pincode = strtoupper($pincode);
        $region = $postData['shipping_address']['region'];
        $street = $postData['shipping_address']['street'];
        $city = $postData['shipping_address']['city'];
        $telephone = $postData['shipping_address']['telephone'];
        $regionId = $this->getRegionByName($region, $countryId);
        $address = $this->address
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setStreet($street)
            ->setCity($city)
            ->setCountryId($countryId)
            ->setRegionId($regionId)
            ->setRegion($region)
            ->setPostcode($pincode)
            ->setTelephone($telephone)
            ->setSameAsBilling(0);

        if (isset($postData['shipping_address']['customAttributes'])) {
            foreach ($postData['shipping_address']['customAttributes'] as $attr) {
                if ($attr['attribute_code'] == 'addressnickname') {
                    $addressNicknameVal = $attr['value'];
                }
                if ($attr['attribute_code'] == 'title') {
                    $addressTitleVal = $attr['value'];
                }
            }
        }
        if ($nickName != "")
        {
            $addressNicknameVal = $nickName;
        }

        if (isset($addressNicknameVal) && $addressNicknameVal != '') {
            $address->setCustomAttribute('addressnickname', $addressNicknameVal);
            $address->setData('addressnickname', $addressNicknameVal);
        }
        if (isset($addressTitleVal) && $addressTitleVal != '') {
            $address->setCustomAttribute('title', $addressTitleVal);
            $address->setData('title', $addressTitleVal);
        }
        if ((isset($postData['isNewAddress'])) && ($postData['isNewAddress'] == 1)) {
            $address->setSaveInAddressBook(1);
        }

        if (isset($postData['customerAddressId']) && $postData['customerAddressId'] != "") {

            $addressId = $postData['customerAddressId'];
            /** @var \Magento\Customer\Api\Data\AddressInterface $address */
            $addressData = $this->addressRepository->getById($addressId);
            if (($addressData->getFirstName() == "") || ($addressData->getLastName() == "")) {
                $this->updateChildCustomerName($firstName, $lastName, $pincode);
            }
            $addressData->setFirstname($firstName)
                ->setLastname($lastName)
                ->setStreet($street)
                ->setCity($city)
                ->setCountryId($countryId)
                ->setRegionId($regionId)
                ->setPostcode($pincode)
                ->setTelephone($telephone);

            if (isset($addressNicknameVal) && $addressNicknameVal != '') {
                $addressData->setCustomAttribute('addressnickname', $addressNicknameVal);
                $addressData->setData('addressnickname', $addressNicknameVal);
            }
            if (isset($addressTitleVal) && $addressTitleVal != '') {
                $addressData->setCustomAttribute('title', $addressTitleVal);
                $addressData->setData('title', $addressTitleVal);
            }
            $this->addressRepository->save($addressData);

        }
        $shippingAddress = $this->shippingInformation->setShippingAddress($address)
            ->setShippingCarrierCode($shippingCarrierCode)
            ->setShippingMethodCode($shippingMethodCode);

        return $shippingAddress;
    }

    /**
     * Gets Region id
     *
     * @param string $region
     * @param string $countryId
     * @return string
     */
    public function getRegionByName($region, $countryId)
    {
        return $this->regionFactory->create()->loadByName($region, $countryId)->getRegionId();
    }

    /**
     * Main function
     *
     * @return void|mixed
     */
    public function execute()
    {
        $postData = $this->getRequest()->getPost();
        // print_r($postData); die('helloooo');
        try {
            $postData = json_decode($postData['payload'], true);
            $postData = call_user_func_array('array_merge', $postData);

            $this->saveShippingInformation($postData);
            $email = $postData['email'] ?? null;
            $customerCards = $this->formBlock->getCustomerCards($email);
            $vendorAccountId = $this->checkoutSession->getData('stripe_account_id');
        } catch (\Exception $e) {
        }

        return $this->resultJsonFactory->create()->setData(
            json_encode(
                [
                    'payment_methods' => $this->getUsedPaymentMethods(),
                    'totals' => $this->getTotalsData(),
                    'savedCards' => !empty($customerCards) ? $customerCards : [],
                    'vendorAccountId' => isset($vendorAccountId) ? $vendorAccountId : ''
                ]
            )
        );
    }
    /**
     * Updating Empty Child Customer Firstname and Lastname
     *
     */
    public function updateChildCustomerName($firstName, $lastName, $postCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('customer_child');
        $select = $connection->select();
        $select->from($tableName, '*');
        $select->where('child_customer_postcode= ?', $postCode);
        $customerChildData = $connection->fetchAll($select);
        foreach ($customerChildData as $chidData) {
            $name = $chidData['name'];

            $child_data = $this->customerChildModel->load($chidData['child_id']);
            if (str_contains($name, $firstName)) {
                $child_data->setChildCustomerFirstname($firstName);
            }
            if (str_contains($name, $lastName)) {
                $child_data->setChildCustomerLastname($lastName);
            }
            $child_data->save();
        }
    }
}