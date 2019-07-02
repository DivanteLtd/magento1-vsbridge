<?php
require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');

class Divante_VueStorefrontBridge_UserController extends Divante_VueStorefrontBridge_AbstractController
{
    /**
     * Login the customer and return API access token
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgeuserlogin
     */
    public function loginAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        } else {
            try {
                $request = $this->_getJsonBody();

                if (!$request) {
                    return $this->_result(500, 'No JSON object found in the request body');
                } else {
                    if (!$request->username || !$request->password) {
                        return $this->_result(500, 'No username or password given!');
                    } else {
                        $session = Mage::getSingleton( 'customer/session' );
                        $secretKey = $this->getSecretKey();

                        if($session->login($request->username, $request->password)) {
                            $user = $session->getCustomer();
                            if ($user->getId()) {
                                $refreshToken = JWT::encode($request, $secretKey, 'HS256');
                                return $this->_result(200, JWT::encode(array('id' => $user->getId()), $secretKey), array('refreshToken' => $refreshToken));
                            } else {
                                return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
                            }
                        } else {
                            return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
                        }


                    }
                }
            } catch (Exception $err) {
                return $this->_result(500, $err->getMessage());
            }

        }
    }

    /**
     * Send password reset link
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgeuserresetpassword
     */
    public function resetPasswordAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        $request = $this->_getJsonBody();

        if (!$request || !$request->email) {
            return $this->_result(500, 'No e-mail provided');
        }

        if ($flowPassword = Mage::getModel('customer/flowpassword')) {
            if (!$flowPassword->checkCustomerForgotPasswordFlowEmail($request->email)) {
                return $this->_result(500, $this->__('You have exceeded requests to times per 24 hours from 1 e-mail.'));
            }

            if (!$flowPassword->checkCustomerForgotPasswordFlowIp()) {
                return $this->_result(500, $this->__('You have exceeded requests to times per hour from 1 IP.'));
            }
        }

        /** @var  $helper */
        $helper = Mage::helper('vsbridge');

        try {
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($request->email);

            if ($customer->getId()) {
                $customer->sendPasswordResetConfirmationEmail();

                return $this->_result(
                    200,
                    $helper->__(
                        'If there is an account associated with %s you will receive an email with a link to reset your password.',
                        $helper->escapeHtml($request->email)
                    )
                );
            }

            return $this->_result(500, 'Wrong e-mail provided');
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    /**
     * Change user password
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgeuserchangepassword
     */
    public function changePasswordAction() {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        } else {
            $request = $this->_getJsonBody();
            if(!$request || !$request->currentPassword || !$request->newPassword) {
                return $this->_result(500, 'No current and new passwords provided!');
            } else {
                $customer = $this->_currentCustomer($this->getRequest());
                if(!$customer) {
                    return $this->_result(500, 'No customer found with the specified token');
                } else {
                    try {
                        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->authenticate($customer->getEmail(), $request->currentPassword);
                        $customer->setPassword($request->newPassword);
                        $customer->save();
                        return $this->_result(200, 'New password set for the customer');
                    } catch (Exception $err) {
                        return $this->_result(500, 'Current password does not match the customer');
                    }
                }
            }
        }
    }



    /**
     * Refresh the user token
     */
    public function refreshAction() {
        try {
            if (!$this->_checkHttpMethod('POST')) {
                return $this->_result(500, 'Only POST method allowed');
            } else {
                $request = $this->_getJsonBody();
                if(!$request || !$request->refreshToken) {
                    return $this->_result(500, 'No request token provided');
                } else  {
                    $secretKey = $this->getSecretKey();
                    $loginRequest = JWT::decode($request->refreshToken, $secretKey, 'HS256');
                    if(!$loginRequest || !$loginRequest->username || !$loginRequest->password) {
                        return $this->_result(500, 'Invalid token or no username password pair');
                    } else {
                        $session = Mage::getSingleton( 'customer/session' );

                        if($session->login($loginRequest->username, $loginRequest->password)) {
                            $user = $session->getCustomer();
                            if ($user->getId()) {
                                $refreshToken = JWT::encode($loginRequest, $secretKey);
                                return $this->_result(200, JWT::encode(array('id' => $user->getId()), $secretKey), array('refreshToken' => $refreshToken));
                            } else {
                                return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
                            }
                        } else {
                            return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
                        }
                    }
                }
            }
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    public function orderHistoryAction()
    {
        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }

        $customer = $this->_currentCustomer($this->getRequest());

        if ($customer) {
            $request = $this->getRequest();
            $page = max(abs(intval($request->getParam('page', 1))), 1);
            $pageSize = min(abs(intval($request->getParam('pageSize', 50))), 50);

            /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
            $orderCollection = Mage::getResourceModel('sales/order_collection');
            $orderCollection
                ->addFieldToSelect('*')
                ->setPageSize($pageSize)->setCurPage($page)
                ->addFieldToFilter('customer_id', $customer->getId())
                ->addFieldToFilter(
                    'state',
                    ['in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()]
                )
                ->setOrder('created_at', 'desc');

            $ordersDTO = [];
            /** @var Mage_Catalog_Model_Resource_Product $resourceModel */
            $resourceModel = Mage::getResourceModel('catalog/product');

            /** @var Mage_Sales_Model_Order $order */
            foreach ($orderCollection as $order) {
                $orderDTO = $order->getData();
                $orderDTO['id'] = $orderDTO['entity_id'];
                $orderDTO['items'] = [];

                foreach($order->getAllVisibleItems() as $item) {
                    $itemDTO = $item->getData();
                    $itemDTO['id'] = $itemDTO['item_id'];
                    $itemDTO['thumbnail'] = null;

                    $image = $resourceModel->getAttributeRawValue(
                        $item->getProductId(),
                        'thumbnail',
                        $order->getStoreId()
                    );

                    if ($image) {
                        $itemDTO['thumbnail'] = $image;
                    }

                    $orderDTO['items'][] = $itemDTO;
                }

                $orderDTO['discount_tax_compensation_amount'] = $orderDTO['hidden_tax_amount'];

                $payment = $order->getPayment();
                $orderDTO['payment'] = $payment->toArray();
                $orderDTO['payment']['additional_information'][0] = $payment->getMethodInstance()->getTitle();

                //TODO explode street by linebreak with mapper when mapper merged
                $shippingAddress = $order->getShippingAddress()->getData();
                $shippingAddress['street'] = explode("\n", $shippingAddress['street']);
                $orderDTO['extension_attributes']['shipping_assignments'][0]['shipping']['address'] = $shippingAddress;

                //TODO explode street by linebreak with mapper when mapper merged
                $billingAddress = $order->getBillingAddress()->getData();
                $billingAddress['street'] = explode("\n", $billingAddress['street']);
                $orderDTO['billing_address'] = $billingAddress; //TODO explode street by linebreak when mapper merged

                $ordersDTO[] = $orderDTO;
            }

            return $this->_result(200, array('items' => $ordersDTO));
        }

        return $this->_result(500, 'User is not authroized to access self');
    }

    /**
     * Register the customer
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgeusercreate
     */
    public function createAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        $request = $this->_getJsonBody();

        if (!$request) {
            return $this->_result(500, 'No JSON object found in the request body');
        }

        if ((!$request->customer || !$request->customer->email) || !$request->password) {
            return $this->_result(500, 'No customer data or password provided!');
        }

        $websiteId = Mage::app()->getWebsite()->getId();
        $store = Mage::app()->getStore();

        $customer = Mage::getModel("customer/customer");
        $customer   ->setWebsiteId($websiteId)
            ->setStore($store)
            ->setFirstname($request->customer->firstname)
            ->setLastname($request->customer->lastname)
            ->setEmail($request->customer->email)
            ->setPassword($request->password);

        try{
            $customer->save();
            $filteredCustomerData = $this->_filterDTO($customer->getData(), array('password', 'password_hash', 'password_confirmation', 'confirmation', 'entity_type_id'));

            return $this->_result(200, $filteredCustomerData); // TODO: add support for 'Refresh-token'
        } catch (Exception $e) {
            return $this->_result(500, $e->getMessage());
        }
    }

    /**
     * Get all information for the current user or update some data of the user
     */
    public function meAction(){
        $customer = $this->_currentCustomer($this->getRequest());

        if(!$customer) {
            return $this->_result(500, 'User is not authroized to access self');
        } else {
            $addressHelper = Mage::helper('vsbridge/address');

            $updatedShippingId = 0;
            $updatedBillingId = 0;

            try {
                if ($this->_checkHttpMethod(array('POST'))) { // modify user data
                    $request = _object_to_array($this->_getJsonBody());

                    if(!$request['customer']) {
                        return $this->_result(500, 'No customer data provided!');
                    }

                    $updatedCustomer = $request['customer'];
                    $updatedCustomer['entity_id'] = $customer->getId();

                    $customer->setData('firstname', $updatedCustomer['firstname'])
                             ->setData('lastname', $updatedCustomer['lastname'])
                             ->setData('email', $updatedCustomer['email']);

                    if (isset($updatedCustomer['dob'])) {
                        $customer->setData('dob', $updatedCustomer['dob']);
                    }

                    $customer->save();

                    if ($updatedCustomer['addresses']) {
                        foreach($updatedCustomer['addresses'] as $updatedAdress) {
                            $updatedAdress['region'] = $updatedAdress['region']['region'];
                            $handled = false;

                            if($updatedAdress['default_shipping'] && $updatedAdress['default_billing']) {
                                $sAddressId = $customer->getDefaultShipping();
                                $sAddress = Mage::getModel('customer/address');

                                if($sAddressId) {
                                    if (isset($updatedAdress['entity_id'])) {
                                        $sAddressId = $updatedAdress['entity_id'];
                                    }

                                    $sAddress->load($sAddressId);
                                }

                                $updatedAdress['parent_id'] = $customer->getId();
                                $updatedAdress['street'] = $addressHelper->concatStreetData($updatedAdress['street']);

                                $sAddress->setData($updatedAdress)
                                         ->setIsDefaultShipping(1)
                                         ->setIsDefaultBilling(1)
                                         ->save();
                                $updatedShippingId = $sAddress->getId();
                                $handled = true;
                            } elseif($updatedAdress['default_shipping']) {
                                $sAddressId = $customer->getDefaultShipping();
                                $sAddress = Mage::getModel('customer/address');

                                if($sAddressId) {
                                    if (isset($updatedAdress['entity_id'])) {
                                        $sAddressId = $updatedAdress['entity_id'];
                                    }

                                    $sAddress->load($sAddressId);
                                }

                                $updatedAdress['parent_id'] = $customer->getId();
                                $updatedAdress['street'] = $addressHelper->concatStreetData($updatedAdress['street']);

                                $sAddress->setData($updatedAdress)
                                         ->setIsDefaultShipping(1)
                                         ->setIsDefaultBilling(0)
                                         ->save();
                                $updatedShippingId = $sAddress->getId();
                                $handled = true;
                            } elseif($updatedAdress['default_billing']) {
                                $bAddressId = $customer->getDefaultBilling();
                                $bAddress = Mage::getModel('customer/address');

                                if($bAddressId){
                                    if (isset($updatedAdress['entity_id'])) {
                                        $bAddressId = $updatedAdress['entity_id'];
                                    }

                                    $bAddress->load($bAddressId);
                                }

                                $updatedAdress['parent_id'] = $customer->getId();
                                $updatedAdress['street'] = $addressHelper->concatStreetData($updatedAdress['street']);


                                $bAddress->setData($updatedAdress)
                                         ->setIsDefaultShipping(0)
                                         ->setIsDefaultBilling(1)
                                         ->save();
                                $updatedBillingId = $bAddress->getId();
                                $handled = true;
                            }

                            if($updatedAdress['delete'] === true) {
                                try {
                                    $sAddress = Mage::getModel('customer/address');

                                    $sAddress->load($updatedAdress['entity_id']);

                                    $sAddress->setData($updatedAdress)->delete();
                                } catch (Throwable $exception) {
                                    Mage::log(
                                        'Cloud not load address:' . $updatedAdress['entity_id'] . " to delete it",
                                        Zend_Log::ERR,
                                        'vsbridge.log'
                                    );
                                }
                            } elseif (!$handled) {
                                $sAddress = Mage::getModel('customer/address');

                                try {
                                    if (isset($updatedAdress['entity_id']) && !empty($updatedAdress['entity_id'])) {
                                        $sAddress->load($updatedAdress['entity_id']);
                                    }
                                } catch (Exception $exception) {
                                    Mage::log(
                                        'Cloud not load address:' . $updatedAdress['entity_id'] . " for updating it.",
                                        Zend_Log::ERR,
                                        'vsbridge.log'
                                    );
                                }

                                $updatedAdress['parent_id'] = $customer->getId();
                                $updatedAdress['street'] = $addressHelper->concatStreetData($updatedAdress['street']);

                                $sAddress->setData($updatedAdress)
                                         ->setIsDefaultBilling(0)
                                         ->setIsDefaultShipping(0)
                                         ->save();
                            }
                        }
                        $customer = $this->_currentCustomer($this->getRequest());
                    }
                }

                $customer->load($customer->getId());
                $customerDTO = $customer->getData();
                $subscription = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
                $customerDTO['is_subscribed'] = $subscription->isSubscribed();

                $allAddress = $customer->getAddresses();
                $defaultBilling  = $customer->getDefaultBilling();
                $defaultShipping = $customer->getDefaultShipping();
                $customerDTO['addresses'] = array();

                foreach ($allAddress as $address) {
                    $addressDTO = $address->getData();
                    $addressDTO['id'] = $addressDTO['entity_id'];
                    $region = null;

                    if (isset($addressDTO['region'])) {
                        $region = $addressDTO['region'];
                    }

                    $addressDTO['region'] = ['region' => $region];
                    $addressDTO['street'] = $addressHelper->splitStreetData($addressDTO['street']);

                    if(!$addressDTO['firstname'])
                        $addressDTO['firstname'] = $customerDTO['firstname'];

                    if(!$addressDTO['lastname'])
                        $addressDTO['lastname'] = $customerDTO['lastname'];

                    if(!$addressDTO['city'])
                        $addressDTO['city'] = '';

                    if(!$addressDTO['country_id'])
                        $addressDTO['country_id'] = 'US';

                    if(!$addressDTO['postcode'])
                        $addressDTO['postcode'] = '';

                    if(!$addressDTO['telephone'])
                        $addressDTO['telephone'] = '';

                    if($defaultBilling == $address->getId() || $address->getId() == $updatedBillingId) {
                        // TODO: Street + Region fields (region_code should be)

                        // its customer default billing address
                        $addressDTO['default_billing'] = true;
                        $customerDTO['default_billing'] = $address->getId();
                    }

                    if ($defaultShipping == $address->getId()|| $address->getId() == $updatedShippingId) {
                        // its customer default shipping address
                        $addressDTO['default_shipping'] = true;
                        $customerDTO['default_shipping'] = $address->getId();
                    }

                    $customerDTO['addresses'][] = $addressDTO;
                    $customerDTO['id'] = $customerDTO['entity_id'];
                }

                $filteredCustomerData = $this->_filterDTO($customerDTO, array('password', 'password_hash', 'password_confirmation', 'confirmation', 'entity_type_id'));

                return $this->_result(200, $filteredCustomerData);
            } catch (Exception $err) {
                return $this->_result(500, $err->getMessage());
            }
        }
    }
}
?>
