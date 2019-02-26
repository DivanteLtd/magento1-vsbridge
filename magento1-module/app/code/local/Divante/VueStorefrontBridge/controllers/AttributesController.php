<?php

require_once('AbstractController.php');

/**
 * Divante VueStorefrontBridge AttributesController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Piotr Karwatka <pkarwatka@divante.co>
 * @author      Dariusz Oliwa <doliwa@divante.co>
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_AttributesController extends Divante_VueStorefrontBridge_AbstractController
{
    const ES_DATA_TYPE_STRING  = 'text';
    const ES_DATA_TYPE_FLOAT   = 'float';
    const ES_DATA_TYPE_INT     = 'integer';
    const ES_DATA_TYPE_DATE    = 'date';
    const ES_DATA_TYPE_OBJECT  = 'object';
    const ES_DATA_TYPE_KEYWORD = 'keyword';
    const ES_DATA_TYPE_BOOLEAN = 'boolean';

    /**
     * elasticsearch <=> magento type mappings
     * @var array
     */
    protected $_mapAttributeType = array(
        'varchar'   => self::ES_DATA_TYPE_STRING,
        'text'      => self::ES_DATA_TYPE_STRING,
        'decimal'   => self::ES_DATA_TYPE_FLOAT,
        'int'       => self::ES_DATA_TYPE_INT,
        'smallint'  => self::ES_DATA_TYPE_INT,
        'timestamp' => self::ES_DATA_TYPE_DATE,
        'datetime'  => self::ES_DATA_TYPE_DATE,
        'static'    => self::ES_DATA_TYPE_STRING,
    );

    /**
     * index action
     */
    public function indexAction()
    {
        if ($this->_authorizeAdminUser($this->getRequest())) {
            $params       = $this->_processParams($this->getRequest());
            $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
            $attrList     = [];
            foreach ($productAttrs as $productAttr) {
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
                $attribute = Mage::getSingleton('eav/config')
                    ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $productAttr->getAttributeCode());
                $options   = [];
                if ($attribute->usesSource()) {
                    $options = $attribute->getSource()->getAllOptions(false);
                }

                $productAttrDTO = $productAttr->getData();

                if (in_array($productAttrDTO['source_model'], array('core/design_source_design'))) {
                    continue;
                } // exception - this attribute has string typed values; this is not acceptable by VS

                $productAttrDTO['id']            = intval($productAttr->getAttributeId());
                $productAttrDTO['options']       = $options;
                $productAttrDTO['default_value'] = (int)$productAttrDTO['default_value'];
                $productAttrDTO                  = $this->_filterDTO($productAttrDTO);
                $attrList[]                      = $productAttrDTO;
            }
            $this->_result(200, $attrList);
        }
    }

    /**
     * Prepare attribute data for mappings
     */
    public function productMappingAction()
    {
        if ($this->_authorizeAdminUser($this->getRequest())) {
            $result            = array();
            $attributeMapping  = array();
            $productAttributes = Mage::getResourceModel('catalog/product_attribute_collection');
            foreach ($productAttributes as $productAttribute) {
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttribute */
                $attribute = Mage::getSingleton('eav/config')
                    ->getAttribute(
                        Mage_Catalog_Model_Product::ENTITY,
                        $productAttribute->getAttributeCode()
                    );

                $backendType   = $attribute->getBackendType();
                $attributeCode = $productAttribute->getAttributeCode();
                if (strstr($attributeCode, 'is_') || strstr($attributeCode, 'has_')) {
                    $fieldType = self::ES_DATA_TYPE_BOOLEAN;
                } else {
                    $fieldType = $this->_getElasticsearchTypeByMagentoType($backendType);
                }

                $attributeMapping[$attributeCode]['type'] = $fieldType;
                if ($backendType == 'timestamp' || $backendType == 'datetime') {
                    $attributeMapping[$attributeCode]['format'] = "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis";
                }

                if (($attribute->getUsedForSortBy()
                        || $attribute->getIsFilterableInSearch()
                        || $attribute->getIsFilterable())
                    && $fieldType == self::ES_DATA_TYPE_STRING
                ) {
                    $attributeMapping[$attributeCode]['fielddata'] = true;
                }
            }
            $attributeMapping['final_price']['type']                 = self::ES_DATA_TYPE_FLOAT;
            $attributeMapping['configurable_children']['properties'] = $attributeMapping;

            unset($attributeMapping['created_at']);
            unset($attributeMapping['updated_at']);

            $result['properties'] = $attributeMapping;
            $this->_result(200, $result);
        }
    }

    /**
     * Elasticsearch <=> magento type mapping
     *
     * @param $magentoType
     *
     * @return mixed
     */
    protected function _getElasticsearchTypeByMagentoType($magentoType)
    {
        if (!isset($this->_mapAttributeType[$magentoType])) {
            return $magentoType;
        }

        return $this->_mapAttributeType[$magentoType];
    }
}
