<?php

/**
 * Class Divante_VueStorefrontBridge_Model_Api_Cart_Item
 *
 * @package     Divante
 * @category    VueStoreFrontIndexer
 * @author      Agata Firlejczyk <afirlejczyk@divante.pl
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 * @license     See LICENSE_DIVANTE.txt for license details.
 */

class Divante_VueStorefrontBridge_Model_Api_Cart_Item
{

    /**
     * @param Mage_Catalog_Model_Product_Configuration_Item_Interface $item
     *
     * @return array
     */
    public function getConfigurableOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
    {
        $configurableOptions = [];
        $product = $item->getProduct();
        $attributesOption = $product->getCustomOption('attributes');

        if ($attributesOption) {
            $selectedConfigurableOptions = unserialize($attributesOption->getValue());

            if (is_array($selectedConfigurableOptions)) {
                foreach ($selectedConfigurableOptions as $optionId => $optionValue) {
                    $configurableOptions[] = [
                        'option_id' => $optionId,
                        'option_value' => $optionValue,
                    ];
                }
            }
        }

        return $configurableOptions;
    }
}
