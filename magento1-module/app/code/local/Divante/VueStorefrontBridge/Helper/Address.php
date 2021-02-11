<?php

class Divante_VueStorefrontBridge_Helper_Address extends Mage_Core_Helper_Abstract
{
    /**
     * @param array $streetData
     *
     * @return string
     */
    public function concatStreetData(array $streetData): string
    {
        $concatStreetData = '';

        if (isset($streetData[0], $streetData[1])) {
            $concatStreetData = $streetData[0] . ' ' . $streetData[1];
        }

        return $concatStreetData;
    }

    /**
     * @param string $streetData
     *
     * @return array
     */
    public function splitStreetData(string $streetData): array
    {
        $streetData = preg_split('/(?=\d)/', $streetData, 2);

        $splitStreetData = [];
        foreach ($streetData as $value) {
            $splitStreetData[] = trim($value);
        }

        return $splitStreetData;
    }

    /**
     * @param string $streetData
     *
     * @return array
     */
    public function getProcessedAddressData(array $address): array
    {
        $address['street']  = $this->splitStreetData($address['street']);
        $address['country'] = Mage::getModel('directory/country')->load($address['country_id'])->getName();

        return $address;
    }
}