<?php
require_once('AbstractController.php');

class Divante_VueStorefrontBridge_ProductsController extends Divante_VueStorefrontBridge_AbstractController
{
    public function indexAction()
    {
        $params = $this->_processParams($this->getRequest());
        $this->getResponse()->setHttpResponseCode(200);
        $this->getResponse()->setHeader('Content-Type', 'application/json');


        $result = array();
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSort('updated_at', 'DESC')
            ->addAttributeToSelect('*')
            ->setPage($params['page'], $params['pageSize']);

        if ($params['type_id']) {
            $productCollection->addFieldToFilter('type_id', $params['type_id']);
        }

        $productCollection->load();

        foreach ($productCollection as $product){
            $productDTO = $product->getData();
            $productDTO['id'] = intval($productDTO['entity_id']);
            unset($productDTO['entity_id']);

            if ($productDTO['type_id'] !== 'simple') {
                $configurable= Mage::getModel('catalog/product_type_configurable')->setProduct($product);
                $childProducts = $configurable->getUsedProductCollection()->addAttributeToSelect(array('price', 'sku', 'image', 'small_image', 'thumbnail', 'has_options', 'required_options', 'tax_class_id', 'url_key'))->addFilterByRequiredOptions();

                $productDTO['configurable_children'] = array();
                foreach($childProducts as $child) {
                    $childDTO = $child->getData();
                    $childDTO['id'] = intval($childDTO['entity_id']);
                    unset($childDTO['entity_id']);

                    $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                    $productDTO['configurable_options'] = [];
                    foreach ($productAttributeOptions as $productAttribute) {
                        $productDTO['configurable_options'][] = $productAttribute;
                    }

                    $productDTO['configurable_children'][] = $childDTO;
                }
            }

            $cats = $product->getCategoryIds();
            $productDTO['category'] = array();
            foreach ($cats as $category_id) {
                $cat = Mage::getModel('catalog/category')->load($category_id) ;
                $productDTO['category'][] = array(
                    "category_id" => $cat->getId(),
                    "name" => $cat->getName());
            }


            $result[] = $productDTO;
        }

        $this->getResponse()->setBody(json_encode($result, JSON_NUMERIC_CHECK ));
    }
}
?>