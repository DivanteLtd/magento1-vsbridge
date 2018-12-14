<?php
define('MAX_PAGESIZE', 5000);
require_once(__DIR__.'/../helpers/JWT.php');

function _filterDTO($dtoToFilter, array $blackList = null) {
    foreach($dtoToFilter as $key => $val) {
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

function _object_to_array($object) {
    if (is_object($object)) {
     return array_map(__FUNCTION__, get_object_vars($object));
    } else if (is_array($object)) {
     return array_map(__FUNCTION__, $object);
    } else {
     return $object;
    }
   }

class Divante_VueStorefrontBridge_AbstractController extends Mage_Core_Controller_Front_Action
{
    public function init()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');        
        $this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHeader('Access-Control-Expose-Headers', 'Link');
    } 

    public function preDispatch() {
        if($this->getRequest()->getMethod() === 'OPTIONS'){
           $this->getResponse()->setBody(json_encode(true))->setHeader('Access-Control-Allow-Origin', '*')->setHeader('Access-Control-Allow-Headers', 'Content-Type')
           ->setHeader('Access-Control-Expose-Headers', 'Link')->sendResponse();
           die();
        }
    }

    public function optionsAction() {
        return $this->_result(204, true);
    }

    protected function _checkHttpMethod($methods) {
        if(!is_array($methods))
            $methods = array($methods);
        
        return in_array($this->getRequest()->getMethod(), $methods);
    }
    protected function _currentStore(){
        return Mage::app()->getStore(); // TODO: refactor to use GET parameters
    }
    protected function _getJsonBody() {
        return @json_decode($this->getRequest()->getRawBody());
    }

    protected function _authorizeAdminUser($request) {
        $apikey = $request->getParam('apikey');
        $secretKey = trim(Mage::getConfig()->getNode('default/auth/secret'));

        try {
            $tokenData = JWT::decode($apikey, $secretKey, 'HS256');
            if($tokenData->id > 0){
                return true;
            }  else {
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
     * Authorize the request against customers (not admin) db and return current customer
     * @param $request
     * @return object
     */
    protected function _currentCustomer($request) {
        $token = $request->getParam('token');
        $secretKey = trim(Mage::getConfig()->getNode('default/auth/secret'));

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

    protected function _currentQuote($request) {
        $cartId = $request->getParam('cartId');

        if(intval(($cartId)) > 0)
            return Mage::getModel('sales/quote')->load($cartId);
        else {
            if($cartId) {
                $secretKey = trim(Mage::getConfig()->getNode('default/auth/secret'));
                $tokenData = JWT::decode($cartId, $secretKey, 'HS256');
                return Mage::getModel('sales/quote')->load($tokenData->cartId);
            } else
                return null;
        }
    }

    protected function _checkQuotePerms($quoteObj, $customer) {
        $quoteCustomer = $quoteObj->getCustomer();
        return (($customer && $quoteCustomer && $quoteCustomer->getId() === $customer->getId()) || (!$quoteCustomer || !$quoteCustomer->getId()));
    }

    protected function _processParams($request) {
        $paramsDTO = array();
        $paramsDTO['page'] = max(abs(intval($request->getParam('page'))), 1);
        $paramsDTO['pageSize'] = min(abs(intval($request->getParam('pageSize'))), MAX_PAGESIZE);
        if($typeId = $request->getParam('type_id')) {
            $paramsDTO['type_id'] = $typeId;
        }
        return $paramsDTO;
    }

    protected function _filterDTO($dtoToFilter, array $blackList = null) {
        return _filterDTO($dtoToFilter, $blackList);
    }

    protected function _result($code, $result, $meta = null){
        $resultDTO = array(
            'code' => $code,
            'result' => $result
        );
        if($meta) {
            $resultDTO['meta'] = $meta;
        }
        $this->getResponse()->setBody(json_encode($resultDTO, JSON_NUMERIC_CHECK))->setHttpResponseCode($code)->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Expose-Headers', 'Link');
    }
}
?>