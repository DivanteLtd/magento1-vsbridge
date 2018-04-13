<?php
require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');
class Divante_VueStorefrontBridge_UserController extends Divante_VueStorefrontBridge_AbstractController
{
    public function loginAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
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

    public function createAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
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
}
?>