<?php

/**
 * Divante VueStorefrontBridge OrderMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Order extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * @inheritdoc
     */
    protected function getAttributesToCastInt()
    {
        return [
            'total_item_count'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastBool()
    {
        return [
            'customer_is_guest',
            'email_sent',
            'paypal_ipn_customer_notified'
        ];
    }
}
