<?php
require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');
class Divante_VueStorefrontBridge_AuthController extends Divante_VueStorefrontBridge_AbstractController
{
    public function adminAction()
    {
        if($this->getRequest()->getMethod() !== 'POST'){
            return $this->_result(500, 'Only POST method allowed');
        } else {

            $request = @json_decode($this->getRequest()->getRawBody());

            if(!$request) {
                return $this->_result(500, 'No JSON object found in the request body');
            } else {
                if(!$request->username || !$request->password) {
                    return $this->_result(500, 'No username or password given!');
                } else {
                    $session = Mage::getSingleton('admin/session');
                    $secretKey = trim(Mage::getConfig()->getNode('default/auth/secret'));

                    $user = $session->login($request->username, $request->password);
                    if ($user->getId()) {
                        return $this->_result(200, JWT::encode(array('id' => $user->getId()),$secretKey));
                    } else {
                        return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
                    }


                }
            }

        }
    }
}
?>