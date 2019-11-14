<?php

/**
 * Divante VueStorefrontBridge StockMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Stock extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     */
    const MAPPER_IDENTIFIER = 'stock';

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastBool()
    {
        return [
            'stock_status_changed_auto',
            'stock_status_changed_automatically'
        ];
    }
}
