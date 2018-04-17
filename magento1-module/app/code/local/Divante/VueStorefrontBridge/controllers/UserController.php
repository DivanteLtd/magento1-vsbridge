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
        if(!$customer) {
            return $this->_result(500, 'No customer found with the specified token');
        } else { 
            try {
                if ($this->_checkHttpMethod(array('POST'))) { // modify user data
                    $request = _object_to_array($this->_getJsonBody());
                    if(!$request['customer']) {
                        return $this->_result(500, 'No customer data provided!');
                    }

                    //die(print_r($customer->getData(), true));
                    $updatedCustomer = $request['customer'];
                    $updatedCustomer['entity_id'] = $customer->getId();
                    
                    $customer->setData('firstname', $updatedCustomer['firstname'])
                            ->setData('lastname', $updatedCustomer['lastname'])
                            ->setData('email', $updatedCustomer['email'])
                            ->save();    

                    $updatedShippingId = 0;
                    $updatedBillingId = 0;
                    if ($updatedCustomer['addresses']) {
                        foreach($updatedCustomer['addresses'] as $updatedAdress) {
                            $updatedAdress['region'] = $updatedAdress['region']['region'];

                            if($updatedAdress['default_billing']) {
                                $bAddress = $customer->getDefaultBillingAddress();
                                
                                if(!$bAddress) 
                                    $bAddress = Mage::getModel('customer/address');
                                else
                                    $bAddress->delete();

                                $updatedAdress['parent_id'] = $customer->getId();


                                $bAddress->setData($updatedAdress)->setIsDefaultBilling(1)->save();
                                $updatedBillingId = $bAddress->getId();
                            }
                            if($updatedAdress['default_shipping']) {
                                $bAddress = $customer->getDefaultShippingAddress();
                              
                                if(!$bAddress) 
                                    $bAddress = Mage::getModel('customer/address');
                                else
                                    $bAddress->delete();

                                $updatedAdress['parent_id'] = $customer->getId();           
                                $bAddress->setData($updatedAdress)->setIsDefaultShipping(1)->save();
                                $updatedShippingId = $bAddress->getId();
                            }                        
                        }
                    }
                }
                $customer->load($customer->getId());
                
                $customerDTO = $customer->getData();
                $allAddress = $customer->getAddresses();
                $defaultBilling  = $customer->getDefaultBilling();
                $defaultShipping = $customer->getDefaultShipping();
                $customerDTO['addresses'] = array();

                foreach ($allAddress as $address) {
                    $addressDTO = $address->getData();
                    $addressDTO['id'] = $addressDTO['entity_id'];
                    $addressDTO['region'] = array('region' => $addressDTO['region']);
                    $addressDTO['street'] = explode("\n", $addressDTO['street']);
                    if($defaultBilling == $address->getId() || $address->getId() == $updatedBillingId) {
                        // TODO: Street + Region fields (region_code should be)

                        // its customer default billing address
                        $addressDTO['default_billing'] = true;
                        $customerDTO['default_billing'] = $address->getId();
                        $customerDTO['addresses'][] = $addressDTO;
                    } else if($defaultShipping == $address->getId()|| $address->getId() == $updatedShippingId) {
                        // its customer default shipping address
                        $addressDTO['default_shipping'] = true;
                        $customerDTO['default_shipping'] = $address->getId();
                        $customerDTO['addresses'][] = $addressDTO;
                    }
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