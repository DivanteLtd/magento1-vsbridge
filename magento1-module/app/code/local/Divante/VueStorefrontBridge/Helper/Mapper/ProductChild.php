<?php

/**
 * Divante VueStorefrontBridge ProductChildMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_ProductChild extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * @inheritdoc
     */
    protected function getBlacklist()
    {
        return [
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
            'required_options'
        ];
    }
}
