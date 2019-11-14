<?php

/**
 * Divante VueStorefrontBridge CategoryMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Category extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     */
    const MAPPER_IDENTIFIER = 'category';

    /**
     * @inheritdoc
     */
    protected function getBlacklist()
    {
        return [
            'entity_id',
            'path'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastInt()
    {
        return [
            'position',
            'level',
            'children_count'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastBool()
    {
        return [
            'include_in_menu'
        ];
    }
}
