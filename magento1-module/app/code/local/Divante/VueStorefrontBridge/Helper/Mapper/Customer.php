<?php

/**
 * Divante VueStorefrontBridge CustomerMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Customer extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     */
    const MAPPER_IDENTIFIER = 'customer';

    /**
     * @inheritdoc
     */
    protected function getBlacklist()
    {
        return [
            'password',
            'password_hash',
            'password_confirmation',
            'password_created_at',
            'confirmation',
            'entity_type_id'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastInt()
    {
        return [
            'default_billing',
            'default_shipping'
        ];
    }
}
