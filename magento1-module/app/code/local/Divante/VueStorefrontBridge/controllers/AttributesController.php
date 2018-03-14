<?php
require_once('AbstractController.php');
class Divante_VueStorefrontBridge_AttributesController extends Divante_VueStorefrontBridge_AbstractController
{
    public function indexAction()
    {
        $params = $this->_processParams($this->getRequest());
        $this->getResponse()->setHttpResponseCode(200);   
        $this->getResponse()->setHeader('Content-Type', 'application/json');        
        
        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');

        $attrList = array();
        foreach ($productAttrs as $productAttr) { /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
            $attribute = Mage::getSingleton('eav/config')
            ->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'color');

            $options = array();
            if ($attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions(false);
            }

            $productAttrDTO = $productAttr->getData();
            $productAttrDTO['id'] = intval($productAttr->attribute_id);
            $productAttrDTO['options'] = $options;

            $productAttrDTO = $this->_filterDTO($productAttrDTO);

            $attrList[] = $productAttrDTO;
        }
        $this->getResponse()->setBody(json_encode($attrList, JSON_NUMERIC_CHECK ));
    }
}
?>