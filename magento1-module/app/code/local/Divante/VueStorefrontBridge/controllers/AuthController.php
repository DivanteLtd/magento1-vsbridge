<?php
require_once('AbstractController.php');
require_once(__DIR__ . '/../helpers/JWT.php');

/**
 * Divante VueStorefrontBridge AuthController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Piotr Karwatka <pkarwatka@divante.co>
 * @author      Björn Kraus PhoenixPM - BK
 * @author      Dariusz Oliwa <doliwa@divante.co>
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_AuthController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * admin action
     */
    public function adminAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
            return $this->_result(500, 'Only POST method allowed');
        }

        $request = $this->_getJsonBody();
        if (!$request) {
            return $this->_result(500, 'No JSON object found in the request body');
        }
        if (!$request->username || !$request->password) {
            return $this->_result(500, 'No username or password given!');
        }

        $session = Mage::getSingleton('admin/session');
        $secretKey = $this->getSecretKey();
        $user = $session->login($request->username, $request->password);

        if (empty($user->getId())) {
            return $this->_result(500, 'You did not sign in correctly or your account is temporarily disabled.');
        }
        return $this->_result(200, JWT::encode(['id' => $user->getId()], $secretKey));
    }
}
