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
class Divante_VueStorefrontBridge_AbstractController extends Mage_Core_Controller_Front_Action
{
    public function init()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');        
    } 

    protected function _authorize($request) {
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

    protected function _result($code, $result){
        $this->getResponse()->setBody(json_encode(array(
            'code' => $code,
            'result' => $result
        ), JSON_NUMERIC_CHECK))->setHttpResponseCode($code)->setHeader('Content-Type', 'application/json');
    }
}
?>