<?php
require_once('AbstractController.php');

class Divante_VueStorefrontBridge_ProductsController extends Divante_VueStorefrontBridge_AbstractController
{
    public function indexAction()
    {
        if($this->_authorize($this->getRequest())) {

            $params = $this->_processParams($this->getRequest());
            $confChildBlacklist = array('entity_id', 'id', 'type_id', 'updated_at', 'created_at', 'stock_item', 'short_description', 'page_layout', 'news_from_date', 'news_to_date', 'meta_description', 'meta_keyword', 'meta_title', 'description', 'attribute_set_id', 'entity_type_id', 'has_options', 'required_options');

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

            foreach ($productCollection as $product) {
                $productDTO = $product->getData();
                $productDTO['id'] = intval($productDTO['entity_id']);
                unset($productDTO['entity_id']);

                if ($productDTO['type_id'] !== 'simple') {
                    $configurable = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
                    $childProducts = $configurable->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();

                    $productDTO['configurable_children'] = array();
                    foreach ($childProducts as $child) {
                        $childDTO = $child->getData();
                        $childDTO['id'] = intval($childDTO['entity_id']);

                        $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                        $productDTO['configurable_options'] = [];
                        foreach ($productAttributeOptions as $productAttribute) {
                            if (!$productDTO[$productAttribute['attribute_code'] . '_options'])
                                $productDTO[$productAttribute['attribute_code'] . '_options'] = array();

                            $productDTO['configurable_options'][] = $productAttribute;
                            $availableOptions = array();
                            foreach ($productAttribute['values'] as $aOp)
                                $availableOptions[] = $aOp['value_index'];

                            $productDTO[$productAttribute['attribute_code'] . '_options'] = $availableOptions;
                        }

                        $childDTO = $this->_filterDTO($childDTO, $confChildBlacklist);
                        $productDTO['configurable_children'][] = $childDTO;
                    }
                }

                $cats = $product->getCategoryIds();
                $productDTO['category'] = array();
                foreach ($cats as $category_id) {
                    $cat = Mage::getModel('catalog/category')->load($category_id);
                    $productDTO['category'][] = array(
                        "category_id" => $cat->getId(),
                        "name" => $cat->getName());
                }

                $productDTO = $this->_filterDTO($productDTO);
                $result[] = $productDTO;
            }

            $this->_result(200, $result);
        }
    }
}
?>