<?php

/**
 * Divante VueStorefrontBridge AddressMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Address extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * @inheritdoc
     */
    protected function getAttributesToCastBool()
    {
        return [
            'default_billing',
            'default_shipping'
        ];
    }
}
