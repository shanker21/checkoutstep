<?php
namespace MDC\CheckoutStep\Helper;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\Context as HelperContext;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\App;
use Magento\Eav\Model\Config as EnvConfig;
use Magento\Customer\Model\Session as Session;
use Magento\Checkout\Model\Cart as Cart;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use MDC\Holidays\Helper\Data as HolidayHelper;

class Data extends \Magento\Framework\Url\Helper\Data
{
    protected $coreRegistry;
    protected $layout;
    protected $storeManager;
    protected $scopeConfig;
    protected $redirect;
    protected $eavConfig;
    protected $customer;
    protected $cart;
    protected $zipcodeHelper;
    protected $availableDeliveryHelper;
    protected $searchCriteriaBuilder;
    protected $addressRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    public function __construct(
        HelperContext $context,
        StoreManagerInterface $storeManager,
        Registry $coreRegistry,
        LayoutInterface $layout,
        App\Response\RedirectInterface $redirect,
        \Magento\Framework\DataObject $dataobject,
        EnvConfig $eavConfig,
        Session $customer,
        Cart $cart,
        \MDC\Zipcode\Helper\Data $zipcodeHelper,
        \MDC\AvailableDeliveryDays\Helper\Data $availableDeliveryHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magedelight\Catalog\Model\Product $vendorProduct,
        \MDC\Sales\Helper\Data $mdcSalesHelper,
        \Magento\Checkout\Model\CartFactory $cartFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magedelight\Catalog\Model\ResourceModel\ProductWebsite\CollectionFactory $ProductWebsiteCollectionFactory,
        \Magedelight\Catalog\Model\ResourceModel\Product\CollectionFactory $vendorProductCollectionFactory,
        StockRegistryInterface $stockRegistry,
        HolidayHelper $holidayHelper
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_layout = $layout;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $context->getScopeConfig();
        $this->redirect = $redirect;
        $this->logger = $context->getLogger();
        $this->dataobject = $dataobject;
        $this->eavConfig = $eavConfig;
        $this->customer = $customer;
        $this->cart = $cart;
        $this->zipcodeHelper = $zipcodeHelper;
        $this->availableDeliveryHelper = $availableDeliveryHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->vendorProduct = $vendorProduct;
        $this->mdcSalesHelper = $mdcSalesHelper;
        $this->cartFactory = $cartFactory;
        $this->addressRepository = $addressRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->resource = $resource;
        $this->customerRepository = $customerRepository;
        $this->ProductWebsiteCollectionFactory = $ProductWebsiteCollectionFactory;
        $this->stockRegistry = $stockRegistry;
        $this->_productloader = $vendorProductCollectionFactory;
        $this->holidayHelper = $holidayHelper;

        parent::__construct($context);
    }


    public function getRedirection()
    {
        return $this->getBaseUrl() . 'checkout/#shipping';
    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    public function getResponseObject()
    {
        return $this->dataobject;
    }


    public function readEavAttribute($attributeName, $attributeCode)
    {
        $attribute = $this->eavConfig->getAttribute($attributeName, $attributeCode);
        $options = $attribute->getSource()->getAllOptions();
        $attributeResult = [];
        foreach ($options as $option) {
            if ($option['value'] > 0) {
                $attributeResult[] = $option;
            }
        }
        return $attributeResult;
    }


    public function readCustomerShippingAddress()
    {
        $customerId = $this->customer->getCustomer()->getId();
        $isShowAddress = false;
        $billingAddressId = "";
        $shippingAddressId = "";
        $addressesList = [];
        try {
            $shippingAddressId = 0;
            if ($customerId) {
                $customer = $this->customerRepository->getById($customerId);
                $shippingAddressId = $customer->getDefaultShipping();
                $billingAddressId =  $customer->getDefaultBilling();
            }
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'parent_id',
                $customerId
            )->create();
            $addressRepository = $this->addressRepository->getList($searchCriteria);
            foreach ($addressRepository->getItems() as $address) {
                $addressType = $this->getBillingAddressId($address->getId());
                if ($shippingAddressId != $billingAddressId) {
                    $isShowAddress = true;
                }else if($address->getId() != $shippingAddressId) {
                    $isShowAddress = true;
                }
                if (($addressType != 'billing') && ($isShowAddress)) {
                    $addressesList[] = $address;
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $addressesList;
    }

    public function getBillingAddressId($addressId)
    {
        $customerId = $this->customer->getCustomer()->getId();
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $select->from($connection->getTableName('quote_address'), 'address_type');
        $select->where('customer_address_id = ' . $addressId . ' AND' . ' customer_id = ' . $customerId);
        $type = $connection->fetchOne($select);
        return $type;
    }

    public function getNextDeliveryDateMonthwise($zipCode, $monthNo, $noOfDays = 5)
    {
        // $quote = $this->cart->getQuote();
        // $zipCode = $quote->getShippingAddress()->getPostCode();
        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($zipCode);
        //$vendorId = 101;
        $nextDeliveryDates = [];
        $selectedMonth = $noOfDays = '';

        try {
            if ($monthNo >= 0) {
                //$noOfDays=date("t", strtotime('+'.$monthNo.' month'));
                $noOfDays = 100;
                $selectedMonth = date("n Y", strtotime('first day of +' . $monthNo . ' month'));
            } else {
                $noOfDays = 5;
                $selectedMonth = date("n Y", strtotime('+0 month'));
            }

            $nextDeliveryDates = $this->availableDeliveryHelper->getNextDeliveryDays($zipCode, $vendorId, $noOfDays, 0, true);
            //Add holidays condition
            $holidayDates = $this->availableDeliveryHelper->getListOfHolidays($vendorId) ?? [];
            $recurringHolidays = $this->holidayHelper->getRecurringVendorHolidaysById($vendorId) ?? [];

            if (isset($nextDeliveryDates)) {
                $resultAttribute = [];
                foreach ($nextDeliveryDates as $key => $value) {
                    if ($monthNo >= 0) {
                        if ($selectedMonth == date("n Y", strtotime($value['date']))) {
                            $timestamp = strtotime($value['date']);
                            $day = date('w', $timestamp);
                            if (!((in_array($value['date'], $holidayDates)) || (in_array($day, $recurringHolidays)))) {
                                $dateFormat = date("D, jS F", strtotime($value['date']));
                                $dateFormat .= $value['parts_of_the_day'] != '' ? ', ' . $value['parts_of_the_day'] : '';
                                $resultAttribute[] = ['value' => $value['date'] . "|" . $value['round_id'] . "|" . $value['available_slots'] . "|" . $key, 'label' => $dateFormat];
                            }
                        }
                    } else {
                        $timestamp = strtotime($value['date']);
                        $day = date('w', $timestamp);
                        if (!((in_array($value['date'], $holidayDates)) || (in_array($day, $recurringHolidays)))) {
                            $dateFormat = date("D, jS F", strtotime($value['date']));
                            $dateFormat .= $value['parts_of_the_day'] != '' ? ', ' . $value['parts_of_the_day'] : '';
                            $resultAttribute[] = ['value' => $value['date'] . "|" . $value['round_id'] . "|" . $value['available_slots'] . "|" . $key, 'label' => $dateFormat];
                        }
                    }
                }

                return $resultAttribute;
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getNextDeliveryDate($zipCode, $noOfDays = 5)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Oospostmessage.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($zipCode);
        $logger->info('Vendor ID found:' . $vendorId);
        $nextDeliveryDate = '';
        try {

            $nextDeliveryDates = $this->availableDeliveryHelper->getNextDeliveryDays($zipCode, $vendorId, $noOfDays, 0, true);
            $logger->info('Next delivery dates found:' . json_encode($nextDeliveryDates));

             //Add holidays condition
             $holidayDates = $this->availableDeliveryHelper->getListOfHolidays($vendorId) ?? [];
             $recurringHolidays = $this->holidayHelper->getRecurringVendorHolidaysById($vendorId) ?? [];
             $logger->info('Holidays');
             $logger->info(print_r($recurringHolidays, true));
            if (!empty($nextDeliveryDates)) {
                $resultAttribute = [];
                foreach ($nextDeliveryDates as $key => $value) {
                    $timestamp = strtotime($value['date']);
                    $day = date('w', $timestamp);
                    if (!((in_array($value['date'], $holidayDates)) || (in_array($day, $recurringHolidays)))) {
                        $dateFormat = date("D, jS F", strtotime($value['date']));
                        $dateFormat .= $value['parts_of_the_day'] != '' ? ', ' . $value['parts_of_the_day'] : '';
                        $resultAttribute[] = ['value' => $value['date'] . "|" . $value['round_id'] . "|" . $value['available_slots'] . "|" . $key, 'label' => $dateFormat];
                    }
                }
                return $resultAttribute;
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getNextDeliveryDateBk($noOfDays = 5)
    {
        $quote = $this->cart->getQuote();
        $zipCode = $quote->getShippingAddress()->getPostCode();
        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($zipCode);
        //$vendorId = 101;
        $nextDeliveryDates = [];
        $nextDeliveryDate = '';
        try {
            $nextDeliveryDates = $this->availableDeliveryHelper->getNextDeliveryDays($zipCode, $vendorId, $noOfDays);
            $nextDeliveryDate = $nextDeliveryDates['next_delivery_dates'];

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $nextDeliveryDate;

    }

    public function getNextDeliveryDateMessage($zipCode, $noOfDays = 2)
    {
        // $quote = $this->cart->getQuote();
        // $zipCode = $quote->getShippingAddress()->getPostCode();
        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($zipCode);
        $postcodeMessage = [];
        $nextDeliveryDateNames = [];
        try {
            $nextDeliveryDates = $this->availableDeliveryHelper->getNextDeliveryDays($zipCode, $vendorId, 5, 0, true);
            foreach ($nextDeliveryDates as $key => $deliveryDate) {
                $nextDeliveryDateNames[date('N', strtotime($deliveryDate['date']))] = date('l', strtotime($deliveryDate['date'])) . 's';
            }

            ksort($nextDeliveryDateNames, 1);

            $nextDeliveryDate = implode(", ", $nextDeliveryDateNames);
            if ($nextDeliveryDate != '' && $zipCode != '') {
                if (! preg_match('/\s/',$zipCode)) {
                    $zipCode = substr_replace($zipCode, ' ' . substr($zipCode, -3), -3);
                }
                $zipCode = strtoupper($zipCode);
                $postcodeMessage = [
                    'status' => '1',
                    'message' => "Your local driver in <b>" . $zipCode . "</b> delivers on <b>" . $nextDeliveryDate . ".</b> Select a delivery date below that works for you."
                ];
            } else {
                $postcodeMessage = [
                    'status' => '0',
                    'message' => "Delivery dates not found."
                ];
            }
        } catch (\Exception $e) {
            $postcodeMessage = [
                'status' => '0',
                'message' => $e->getMessage()
            ];
        }
        return $postcodeMessage;

    }


    public function ukPostcodeCheck(&$toCheck)
    {
        // Permitted letters depend upon their position in the postcode.
        $alpha1 = "[abcdefghijklmnoprstuwyz]"; // Character 1
        $alpha2 = "[abcdefghklmnopqrstuvwxy]"; // Character 2
        $alpha3 = "[abcdefghjkstuw]"; // Character 3
        $alpha4 = "[abehmnprvwxy]"; // Character 4
        $alpha5 = "[abdefghjlnpqrstuwxyz]"; // Character 5

        // Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA with a space
        // Or AN, ANN, AAN, AANN with no whitespace
        $pcexp[0] = '^(' . $alpha1 . '{1}' . $alpha2 . '{0,1}[0-9]{1,2})([[:space:]]{0,})([0-9]{1}' . $alpha5 . '{2})?$';

        // Expression for postcodes: ANA NAA
        // Or ANA with no whitespace
        $pcexp[1] = '^(' . $alpha1 . '{1}[0-9]{1}' . $alpha3 . '{1})([[:space:]]{0,})([0-9]{1}' . $alpha5 . '{2})?$';

        // Expression for postcodes: AANA NAA
        // Or AANA With no whitespace
        $pcexp[2] = '^(' . $alpha1 . '{1}' . $alpha2 . '[0-9]{1}' . $alpha4 . ')([[:space:]]{0,})([0-9]{1}' . $alpha5 . '{2})?$';

        // Exception for the special postcode GIR 0AA
        // Or just GIR
        $pcexp[3] = '^(gir)([[:space:]]{0,})?(0aa)?$';

        // Standard BFPO numbers
        $pcexp[4] = '^(bfpo)([[:space:]]{0,})([0-9]{1,4})$';

        // c/o BFPO numbers
        $pcexp[5] = '^(bfpo)([[:space:]]{0,})(c\/o([[:space:]]{0,})[0-9]{1,3})$';

        // Overseas Territories
        $pcexp[6] = '^([a-z]{4})([[:space:]]{0,})(1zz)$';

        // Anquilla
        $pcexp[7] = '^(ai\-2640)$';

        // Load up the string to check, converting into lowercase
        $postcode = strtolower($toCheck);

        // Assume we are not going to find a valid postcode
        $valid = false;

        // Check the string against the six types of postcodes
        foreach ($pcexp as $regexp) {
            if (preg_match('/' . $regexp . '/i', $postcode, $matches)) {

                // Load new postcode back into the form element
                $postcode = strtoupper($matches[1]);
                if (isset($matches[3])) {
                    $postcode .= ' ' . strtoupper($matches[3]);
                }

                // Take account of the special BFPO c/o format
                $postcode = preg_replace('/C\/O/', 'c/o ', $postcode);

                // Remember that we have found that the code is valid and break from loop
                $valid = true;
                break;
            }
        }

        // Return with the reformatted valid postcode in uppercase if the postcode was
        // valid
        if ($valid) {
            $toCheck = $postcode;
            return true;
        } else {
            return false;
        }
    }

    public function getOosProductsCheckout($zipCode)
    {
        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($zipCode);
        $quote = $this->cart->getQuote();
        $items = $quote->getAllVisibleItems();

        $oosIds = [];
        $oosItemsData = [];
        foreach ($items as $item) {
            $productId = $item->getProductId();
            $isInStock = $this->getProductVendorStockStatus($productId, $vendorId);
            if (!$isInStock) {
                $oosIds[] = $productId;
                $oosItemsData['ids'][$productId]['name'] = $item->getName();
                $oosItemsData['ids'][$productId]['qty'] = $item->getQty();
                $oosItemsData['ids'][$productId]['price'] = $item->getRowTotal();
                $oosItemsData['ids'][$productId]['image'] = $this->mdcSalesHelper->getProductOdImageUrl($productId);
            }
        }

        return [$oosIds, $oosItemsData];
    }

    public function getProductVendorStockStatus($productId, $vendorId)
    {
        $vendorProductId = $this->_productloader->create()->addFieldToFilter('marketplace_product_id', $productId)
            ->addFieldToFilter('vendor_id', $vendorId)->getFirstItem()->getData('vendor_product_id');

        $vendorProductLoaded = $this->vendorProduct->load($vendorProductId);
        $isInStock = $vendorProductLoaded->getData('stock_status');
        return $isInStock;
    }

    public function getNotAvailableIds($zipCode)
    {
        $arrReturnData = [];
        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($zipCode);
        $vendorId2 = $this->zipcodeHelper->getZipcodeVendorId();
        $arrReturnData['ids'] = [];
        $arrReturnData['all'] = false;

        if ($vendorId == $vendorId2) {
            return $arrReturnData;
        }

        $quote = $this->cart->getQuote();
        $items = $quote->getAllVisibleItems();

        $arrCurrentProductIds = $arrZipProductIds = [];

        $vendorProducts = $this->vendorProduct->getVendorProductsById($vendorId);
        /* fix applied while updating vendor status from pending to active */
        foreach ($vendorProducts as $key => $vendorProductItem) {
            $arrZipProductIds[$vendorProductItem->getMarketplaceProductId()] = $vendorProductItem->getMarketplaceProductId();
        }

        foreach ($items as $item) {
            if (!isset($arrZipProductIds[$item->getProductId()])) {
                $arrReturnData['ids'][$item->getProductId()]['name'] = $item->getName();
                $arrReturnData['ids'][$item->getProductId()]['qty'] = $item->getQty();
                $arrReturnData['ids'][$item->getProductId()]['price'] = $item->getRowTotal();
                $arrReturnData['ids'][$item->getProductId()]['image'] = $this->mdcSalesHelper->getProductOdImageUrl($item->getProductId());
            }
            $arrCurrentProductIds[] = $item->getProductId();
        }

        if (count($arrCurrentProductIds) == count($arrReturnData['ids'])) {
            $arrReturnData['all'] = true;
        }

        return $arrReturnData;
    }

    public function getNotAvailableOosIds($zipCode)
    {
        $arrReturnData = [];
        $vendorId = $this->zipcodeHelper->getVendorIdByZipcode($zipCode);
        $vendorId2 = $this->zipcodeHelper->getZipcodeVendorId();
        $arrReturnData['ids'] = [];
        $arrReturnData['all'] = false;
        $quote = $this->cart->getQuote();
        $items = $quote->getAllVisibleItems();

        $arrCurrentProductIds = $arrZipProductIds = [];

        $vendorProducts = $this->vendorProduct->getVendorProductsById($vendorId2);
        /* fix applied while updating vendor status from pending to active */
        foreach ($vendorProducts as $key => $vendorProductItem) {
            $arrZipProductIds[$vendorProductItem->getMarketplaceProductId()] = $vendorProductItem->getMarketplaceProductId();
        }

        foreach ($items as $item) {
            // $stockItem = $this->stockRegistry->getStockItem($item->getProductId());
            // $isInStock = $stockItem->getIsInStock();

            $productId = $item->getProductId();
            $vendorProductId = $this->_productloader->create()->addFieldToFilter('marketplace_product_id', $productId)
                ->addFieldToFilter('vendor_id', $vendorId)->getFirstItem()->getData('vendor_product_id');

            $vendorProductLoaded = $this->vendorProduct->load($vendorProductId);
            $isInStock = $vendorProductLoaded->getData('stock_status');

            if ($isInStock == 0) {
                $arrReturnData['ids'][$item->getProductId()]['name'] = $item->getName();
                $arrReturnData['ids'][$item->getProductId()]['qty'] = $item->getQty();
                $arrReturnData['ids'][$item->getProductId()]['price'] = $item->getRowTotal();
                $arrReturnData['ids'][$item->getProductId()]['image'] = $this->mdcSalesHelper->getProductOdImageUrl($item->getProductId());
            }
            $arrCurrentProductIds[] = $item->getProductId();
        }

        if (count($arrCurrentProductIds) == count($arrReturnData['ids'])) {
            $arrReturnData['all'] = true;
        }

        return $arrReturnData;
    }

    public function removeCartProductsByIds($pids)
    {

        $items = $this->cartFactory->create()->getItems();
        foreach ($items as $item) {
            if (isset($pids[$item->getProductId()])) {
                $item->delete();
            }
        }
        $this->_checkoutSession->getQuote()->collectTotals()->save();
    }

    /**
     * Get loqate key from configuration
     * @return string
     */
    public function getLoqateKey()
    {
        $configValue = $this->scopeConfig->getValue("MDC_loqate/loqate_main/loqate_key");
        return $configValue;
    }
}