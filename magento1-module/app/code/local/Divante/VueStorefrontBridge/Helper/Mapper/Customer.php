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
     * Get CustomerDto from Customer
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    protected function getDto($customer)
    {
        $customerDto = $customer->getData();

        $customerDto['disable_auto_group_change'] = boolval($customerDto['disable_auto_group_change']);
        $customerDto['id'] = $customerDto['entity_id'];

        return $customerDto;
    }

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
            'id',
            'entity_id',
            'attribute_set_id',
            'website_id',
            'group_id',
            'increment_id',
            'store_id',
        ];
    }
}
