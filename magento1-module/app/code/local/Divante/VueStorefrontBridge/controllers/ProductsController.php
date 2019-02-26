<?php
require_once('AbstractController.php');

/**
 * Divante VueStorefrontBridge ProductsController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Piotr Karwatka <pkarwatka@divante.co>
 * @author      Dariusz Oliwa <doliwa@divante.co>
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_ProductsController extends Divante_VueStorefrontBridge_AbstractController
{
    public function indexAction()
    {
        if ($this->_authorizeAdminUser($this->getRequest())) {
            $params = $this->_processParams($this->getRequest());
            $confChildBlacklist = [
                'entity_id',
                'id',
                'type_id',
                'updated_at',
                'created_at',
                'stock_item',
                'short_description',
                'page_layout',
                'news_from_date',
                'news_to_date',
                'meta_description',
                'meta_keyword',
                'meta_title',
                'description',
                'attribute_set_id',
                'entity_type_id',
                'has_options',
                'required_options',
            ];

            $result = [];
            $productCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSort('updated_at', 'DESC')
                ->addAttributeToSelect('*')
                ->setPage($params['page'], $params['pageSize']);

            if (isset($params['type_id']) && $params['type_id']) {
                $productCollection->addFieldToFilter('type_id', $params['type_id']);
            }

            foreach ($productCollection as $product) {
                $productDTO = $product->getData();
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $productDTO['id'] = intval($productDTO['entity_id']);
                unset($productDTO['entity_id']);
                unset($productDTO['stock_item']);

                $productDTO['stock'] = $stock->getData();
                if (isset($productDTO['stock']['is_in_stock']) && $productDTO['stock']['is_in_stock'] == 1) {
                    $productDTO['stock']['is_in_stock'] = true;
                }
                $productDTO['media_gallery'] = $product->getMediaGalleryImages();
                if ($productDTO['type_id'] !== 'simple') {
                    $configurable = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
                    $childProducts = $configurable->getUsedProductCollection()->addAttributeToSelect('*')
                        ->addFilterByRequiredOptions();

                    $productDTO['configurable_children'] = [];
                    foreach ($childProducts as $child) {
                        $childDTO = $child->getData();
                        $childDTO['id'] = intval($childDTO['entity_id']);
                        $productAttributeOptions = $product->getTypeInstance(true)
                            ->getConfigurableAttributesAsArray(
                                $product
                            );
                        $productDTO['configurable_options'] = [];

                        foreach ($productAttributeOptions as $productAttribute) {
                            if (!isset($productDTO[$productAttribute['attribute_code'] . '_options'])) {
                                $productDTO[$productAttribute['attribute_code'] . '_options'] = [];
                            }

                            $productDTO['configurable_options'][] = $productAttribute;
                            $availableOptions = [];

                            foreach ($productAttribute['values'] as $aOp) {
                                $availableOptions[] = $aOp['value_index'];
                            }

                            $productDTO[$productAttribute['attribute_code'] . '_options'] = $availableOptions;
                        }

                        $childDTO = $this->_filterDTO($childDTO, $confChildBlacklist);
                        $productDTO['configurable_children'][] = $childDTO;
                    }
                }

                $cats = $product->getCategoryIds();
                $productDTO['category'] = [];
                $productDTO['category_ids'] = [];
                foreach ($cats as $category_id) {
                    $cat = Mage::getModel('catalog/category')->load($category_id);
                    $productDTO['category'][] = [
                        'category_id' => $cat->getId(),
                        'name' => $cat->getName()
                    ];
                    $productDTO['category_ids'][] = $category_id;
                }

                $productDTO = $this->_filterDTO($productDTO);
                $result[] = $productDTO;
            }

            $this->_result(200, $result);
        }
    }
}
