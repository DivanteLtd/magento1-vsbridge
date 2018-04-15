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

            $request = $this->_getJsonBody();

            if (!$request) {
                return $this->_result(500, 'No JSON object found in the request body');
            } else {
                if (!$request->username || !$request->password) {
                    return $this->_result(500, 'No username or password given!');
                } else {
                    $session = Mage::getSingleton( 'customer/session' );
                    $secretKey = trim(Mage::getConfig()->getNode('default/auth/secret'));

                    if($session->login($request->username, $request->password)) {
                        $user = $session->getCustomer();
                        if ($user->getId()) {
                            return $this->_result(200, JWT::encode(array('id' => $user->getId()), $secretKey));
                        } else {
                            return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
                        }
                    } else {
                        return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
                    }


                }
            }

        }
    }

    /**
     * Register the customer
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgeusercreate
     */
    public function createAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        } else {

            $request = $this->_getJsonBody();

            if (!$request) {
                return $this->_result(500, 'No JSON object found in the request body');
            } else {
                if ((!$request->customer || !$request->customer->email) || !$request->password) {
                    return $this->_result(500, 'No customer data or password provided!');
                } else {


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
                        return $this->_result(200, $filteredCustomerData);
                    }
                    catch (Exception $e) {
                        return $this->_result(500, $e->getMessage());
                    }

                }
            }

        }
    }


    public function meAction(){
        $customer = $this->_currentCustomer($this->getRequest());
        if(!customer) {
            return $this->_result(500, 'No customer found with the specified token');
        } else { 
            if ($this->_checkHttpMethod(array('POST'))) { // modify user data
            }
            $customerDTO = $customer->getData();
            $allAddress = $customer->getAddresses();
            $defaultBilling  = $customer->getDefaultBilling();
            $defaultShipping = $customer->getDefaultShipping();
                            
            foreach ($allAddress as $address) {
                $addressDTO = $address->getData();
                if($defaultBilling == $address->getId()) {
                    // its customer default billing address
                    $addressDTO['default_billing'] = true;
                } else if($defaultShipping == $address->getId()) {
                    // its customer default shipping address
                    $addressDTO['default_shipping'] = true;
                }
                $customerDTO['id'] = $customerDTO['entity_id'];
                $customerDTO['addresses'][] = $addressDTO;
            }
            
            $filteredCustomerData = $this->_filterDTO($customerDTO, array('password', 'password_hash', 'password_confirmation', 'confirmation', 'entity_type_id'));
            return $this->_result(200, $filteredCustomerData);
        
        }
    }
}
?>