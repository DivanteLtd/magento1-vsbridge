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
     * Get AddressDto from Address
     *
     * @param Mage_Customer_Model_Address $address
     * @return array
     */
    protected function getDto($address)
    {
        $addressDto = $address->getData();

        $addressDto['id'] = $addressDto['entity_id'];

        $region = null;
        if (isset($addressDto['region'])) {
            $region = $addressDto['region'];
        }

        $addressDto['region'] = ['region' => $region];

        $streetDto = explode("\n", $addressDto['street']);
        if (count($streetDto) < 2) {
            $streetDto[]='';
        }

        $addressDto['street'] = $streetDto;
        if (!$addressDto['firstname']) {
            $addressDto['firstname'] = $customerDto['firstname'];
        }

        if (!$addressDto['lastname']) {
            $addressDto['lastname'] = $customerDto['lastname'];
        }

        if (!$addressDto['city']) {
            $addressDto['city'] = '';
        }

        if (!$addressDto['country_id']) {
            $addressDto['country_id'] = 'US';
        }

        if (!$addressDto['postcode']) {
            $addressDto['postcode'] = '';
        }

        if (!$addressDto['telephone']) {
            $addressDto['telephone'] = '';
        }

        return $addressDto;
    }

    /**
     * @inheritdoc
     */
    protected function getBlacklist()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastInt()
    {
        return [
            'id',
            'entity_id',
            'entity_type_id',
            'attribute_set_id',
            'increment_id',
            'parent_id',
            'customer_id'
        ];
    }
}
