<?php

/**
 * Divante VueStorefrontBridge ProductAttributeMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_ProductAttribute extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     * @var string $_mapperIdentifier
     */
    protected $_mapperIdentifier = 'product_attribute';

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastInt()
    {
        return [
            'default_value',
            'position'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastBool()
    {
        return [
            'used_in_product_listing',
            'used_for_sort_by'
        ];
    }
}
