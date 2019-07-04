<?php

require_once(__DIR__ . '/../helpers/JWT.php');

function _object_to_array($object) {
    if (is_object($object)) {
        return array_map(__FUNCTION__, get_object_vars($object));
    } else if (is_array($object)) {
        return array_map(__FUNCTION__, $object);
    } else {
        return $object;
    }
}

/**
 * Divante VueStorefrontBridge AbstractController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Piotr Karwatka <pkarwatka@divante.co>
 * @author      Björn Kraus PhoenixPM - BK
 * @author      Dariusz Oliwa <doliwa@divante.co>
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_AbstractController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Divante_VueStorefrontBridge_Model_Config
     */
    private $configSettings;

    /**
     * Divante_VueStorefrontBridge_AbstractController constructor.
     *
     * @param Zend_Controller_Request_Abstract  $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array                             $invokeArgs
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = []
    ) {
        parent::__construct($request, $response, $invokeArgs);

        $this->configSettings = Mage::getSingleton('vsbridge/config');
    }

    /**
     * Sets response header content type to json
     */
    public function init()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHeader('Access-Control-Expose-Headers', 'Link');
    }

    /**
     * @inheritdoc
     */
    public function preDispatch() {
        if($this->getRequest()->getMethod() === 'OPTIONS'){
            $this->getResponse()->setBody(json_encode(true))->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type')
                ->setHeader('Access-Control-Expose-Headers', 'Link')->sendResponse();
            die();
        }

        Mage::app()->getTranslator()->init('frontend');
    }

    public function optionsAction()
    {
        return $this->_result(204, true);
    }

    /**
     * @param $methods
     *
     * @return bool
     */
    protected function _checkHttpMethod($methods)
    {
        if(!is_array($methods))
            $methods = array($methods);

        return in_array($this->getRequest()->getMethod(), $methods);
    }

    /**
     * @return mixed
     */
    protected function _currentStore()
    {
        return Mage::app()->getStore(); // TODO: refactor to use GET parameters
    }

    /**
     * @return mixed
     */
    protected function _getJsonBody()
    {
        return @json_decode($this->getRequest()->getRawBody());
    }

    /**
     * Checks authorization token
     *
     * @param Mage_Core_Controller_Request_Http $request
     *
     * @return bool
     */
    protected function _authorizeAdminUser(Mage_Core_Controller_Request_Http $request)
    {
        $apikey    = $request->getParam('apikey');
        $secretKey = $this->getSecretKey();
        try {
            $tokenData = JWT::decode($apikey, $secretKey, 'HS256');
            if ($tokenData->id > 0) {
                return true;
            } else {
                $this->_result(401, 'Unauthorized request');

                return false;
            }
        } catch (Exception $err) {
            $this->_result(500, $err->getMessage());

            return false;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->configSettings->getSecretKey();
    }

    /**
     * Authorize the request against customers (not admin) db and return current customer
     * @param $request
     * @return object
     */
    protected function _currentCustomer($request) {
        $token = $request->getParam('token');
        $secretKey = $this->getSecretKey();

        try {
            $tokenData = JWT::decode($token, $secretKey, 'HS256');
            if($tokenData->id > 0){
                $customer = Mage::getModel('customer/customer')->load($tokenData->id);

                if ($customer->getId()) {
                    return $customer;
                }
            }  else {
                return null;
            }
        } catch (Exception $err) {
            return null;
        }

        return null;
    }

    /**
     * @param $request
     *
     * @return Mage_Sales_Model_Quote|null
     * @throws Exception
     */
    protected function _currentQuote($request)
    {
        $cartId = $request->getParam('cartId');

        if(intval(($cartId)) > 0)
            return Mage::getModel('sales/quote')->load($cartId);
        else {
            if($cartId) {
                $secretKey = $this->configSettings->getSecretKey();
                $tokenData = JWT::decode($cartId, $secretKey, 'HS256');
                return Mage::getModel('sales/quote')->load($tokenData->cartId);
            } else
                return null;
        }
    }

    /**
     * @param $quoteObj
     * @param $customer
     *
     * @return bool
     */
    protected function _checkQuotePerms($quoteObj, $customer)
    {
        $quoteCustomer = $quoteObj->getCustomer();
        return (($customer && $quoteCustomer && $quoteCustomer->getId() === $customer->getId()) || (!$quoteCustomer || !$quoteCustomer->getId()));
    }

    /**
     * Processes parameters
     *
     * @param Mage_Core_Controller_Request_Http $request
     *
     * @return array
     */
    protected function _processParams(Mage_Core_Controller_Request_Http $request)
    {
        $paramsDTO             = [];
        $paramsDTO['page']     = max(abs(intval($request->getParam('page'))), 1);
        $paramsDTO['pageSize'] = min(
            abs(intval($request->getParam('pageSize'))),
            $this->configSettings->getMaxPageSize()
        );
        if ($typeId = $request->getParam('type_id')) {
            $paramsDTO['type_id'] = $typeId;
        }

        return $paramsDTO;
    }
    /**
     * Filters parameters map removing blacklisted
     *
     * @param array      $dtoToFilter
     * @param array|null $blackList
     *
     * @return mixed
     */
    protected function _filterDTO(array $dtoToFilter, array $blackList = null)
    {
        foreach ($dtoToFilter as $key => $val) {
            if ($blackList && in_array($key, $blackList)) {
                unset ($dtoToFilter[$key]);
            } else {
                if (strstr($key, 'is_') || strstr($key, 'has_')) {
                    $dtoToFilter[$key] = boolval($val);
                }
            }
        }
        return $dtoToFilter;
    }
    /**
     * Sends back code and result of performed operation
     *
     * @param $code
     * @param $result
     */
    protected function _result($code, $result)
    {
        $this->getResponse()->setBody(
            json_encode(
                [
                    'code'   => $code,
                    'result' => $result,
                ],
                JSON_NUMERIC_CHECK
            )
        )
            ->setHttpResponseCode($code)
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Cache-Control', 'no-cache');
    }

    /**
     * Check if the version of Magento currently being rune is Enterprise Edition
     *
     * @return bool
     */
    protected function _isMageEnterprise()
    {
        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise')
            && Mage::getConfig()->getModuleConfig('Enterprise_Cms');
    }
}
