<?php
require_once('AbstractController.php');
class Divante_VueStorefrontBridge_AttributesController extends Divante_VueStorefrontBridge_AbstractController
{
    public function indexAction()
    {
        if($this->_authorize($this->getRequest())) {

            $params = $this->_processParams($this->getRequest());
            $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');

            $attrList = array();
            foreach ($productAttrs as $productAttr) {
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
                $attribute = Mage::getSingleton('eav/config')
                    ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $productAttr->getAttributeCode());

                $options = array();
                if ($attribute->usesSource()) {
                    $options = $attribute->getSource()->getAllOptions(false);
                }

                $productAttrDTO = $productAttr->getData();

                if (in_array($productAttrDTO['source_model'], array('core/design_source_design'))) continue; // exception - this attribute has string typed values; this is not acceptable by VS

                $productAttrDTO['id'] = intval($productAttr->attribute_id);
                $productAttrDTO['options'] = $options;

                $productAttrDTO = $this->_filterDTO($productAttrDTO);

                $attrList[] = $productAttrDTO;
            }
            $this->_result(200, $attrList);
        }
    }
}
?>