<?php
define('MAX_PAGESIZE', 5000);
class Divante_VueStorefrontBridge_AbstractController extends Mage_Core_Controller_Front_Action
{
    public function init()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');        
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
}
?>