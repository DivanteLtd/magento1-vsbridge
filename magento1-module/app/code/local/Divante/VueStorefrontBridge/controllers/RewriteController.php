<?php
require_once Mage::getModuleDir('controllers', 'Divante_VueStorefrontBridge') . DS . 'AbstractController.php';
class Divante_VueStorefrontBridge_RewriteController extends Divante_VueStorefrontBridge_AbstractController {

    public function targetAction()
    {

        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }
        $requestPath = $this->getRequest()->getParam('request_path');
        if ($requestPath) {

            $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
            $select = $reader->select()
                             ->from('core_url_rewrite', ['target_path'])->where('request_path = ?', $requestPath);
            $result = $reader->fetchOne($select);

            if ($result) {
                return $this->_result(200, $result);
            }
        }

        return $this->_result(500, 'Not possible to retrieve target location');
    }

}
