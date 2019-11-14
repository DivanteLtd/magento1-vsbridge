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
     */
    const MAPPER_IDENTIFIER = 'quote_item';

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
