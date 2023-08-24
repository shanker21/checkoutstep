<?php
namespace MDC\CheckoutStep\Controller\Account;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use MDC\CheckoutStep\Helper\Data as Checkouthelper;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePost extends \Magento\Customer\Controller\Account\CreatePost
{

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlFactory $urlFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        CustomerExtractor $customerExtractor,
        DataObjectHelper $dataObjectHelper,
        AccountRedirect $accountRedirect,
        CustomerRepository $customerRepository,
        Validator $formKeyValidator = null,
        Checkouthelper $socialhelper
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $scopeConfig,
            $storeManager,
            $accountManagement,
            $addressHelper,
            $urlFactory,
            $formFactory,
            $subscriberFactory,
            $regionDataFactory,
            $addressDataFactory,
            $customerDataFactory,
            $customerUrl,
            $registration,
            $escaper,
            $customerExtractor,
            $dataObjectHelper,
            $accountRedirect,
            $customerRepository,
            $formKeyValidator
        );
        $this->_helper = $socialhelper;
    }
    /**
     * Create customer account action.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $this->layoutFactory = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $this->resultJsonFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $response = $this->_helper->getResponseObject();

        if (!$this->getRequest()->isPost()) {
            $response->setError(true);
        }
        $this->session->regenerateId();
        try {
            $address = $this->extractAddress();
            $addresses = $address === null ? [] : [$address];

            $customer = $this->customerExtractor->extract('customer_account_create', $this->_request);
            $customer->setAddresses($addresses);

            $is_create_account = $this->getRequest()->getParam('is_create_new_account');

            $postcode = $this->getRequest()->getParam('postcode');

            $checkPostcode = $this->_helper->ukPostcodeCheck($postcode);

            if($checkPostcode) {
                if ($is_create_account == 1) {
                    $password = $this->getRequest()->getParam('password');
                    $confirmation = $this->getRequest()->getParam('password_confirmation');
                    $redirectUrl = $this->_helper->getRedirection();

                    $this->checkPasswordConfirmation($password, $confirmation);

                    $customer = $this->accountManagement
                        ->createAccount($customer, $password, $redirectUrl);

                    if ($this->getRequest()->getParam('is_subscribed', false)) {
                        $this->subscriberFactory->create()->subscribeCustomerById($customer->getId());
                    }

                    $this->_eventManager->dispatch(
                        'customer_register_success',
                        ['account_controller' => $this, 'customer' => $customer]
                    );

                    $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
                    if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                        $email = $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());

                        $this->messageManager->addSuccess(__(
                            'You must confirm your account.
                    Please check your email for the confirmation link or <a href="%1">click here</a> for a new link.',
                            $email
                        ));

                        $response->setError(false);
                    } else {
                        $this->session->setCustomerDataAsLoggedIn($customer);
                        $response->setError(false);
                        $response->setUrl($this->_helper->getBaseUrl() . 'checkout#shipping');

                        return $this->resultJsonFactory->setJsonData($response->toJson());
                    }
                } else {
                    $response->setError(false);
                    $response->setNext($this->_helper->getBaseUrl() . 'checkout#shipping');

                    return $this->resultJsonFactory->setJsonData($response->toJson());
                }
            } else {
                $response->setMessage(__('Please enter a valid postcode.'));
                $response->setError(true);
            }
        } catch (StateException $e) {
            $response->setMessage($e->getMessage());
            $response->setError(true);
        } catch (InputException $e) {
            $response->setMessage($e->getMessage());
            $response->setError(true);
        } catch (\Exception $e) {
            $response->setMessage(__('We couldn\'t save the customer.'));
            $response->setError(true);
        }

        $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        if ($response->getError() == true) {
            return $this->resultJsonFactory->setJsonData($response->toJson());
        } else {
            $response->setError(false);
            $response->setUrl($this->_helper->getRedirection());

            return $this->resultJsonFactory->create()->setJsonData($response->toJson());
        }
    }
}
