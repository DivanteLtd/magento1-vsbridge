<?php

/**
 * Divante VueStorefrontBridge QuoteItemMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_QuoteItem extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     * @var string $_mapperIdentifier
     */
    protected $_mapperIdentifier = 'quote_item';

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastStr()
    {
        return [
            'quote_id'
        ];
    }
}
