<?php

/**
 * Class Divante_VueStorefrontBridge_Model_Api_Cart_Totals
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @author      Agata Firlejczyk <afirlejczyk@divante.pl
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_Model_Api_Cart_Totals
{

    /**
     * @param Mage_Sales_Model_Quote $quoteObj
     *
     * @return array
     */
    public function getTotalsAsArray(Mage_Sales_Model_Quote $quoteObj)
    {
        $shippingAddress = $quoteObj->getShippingAddress();
        $addressTotalData = $shippingAddress->getData();
        $quoteData = $quoteObj->getData();
        $totalsDTO = [
            'grand_total'                   => $quoteData['grand_total'],
            'base_grand_total'              => $quoteData['base_grand_total'],
            'base_subtotal'                 => $quoteData['base_subtotal'],
            'subtotal'                      => $quoteData['subtotal'],
            'discount_amount'               => $addressTotalData['discount_amount'],
            'base_discount_amount'          => $addressTotalData['base_discount_amount'],
            'subtotal_with_discount'        => $quoteData['subtotal_with_discount'],
            'shipping_amount'               => $addressTotalData['shipping_amount'],
            'base_shipping_amount'          => $addressTotalData['base_shipping_amount'],
            'shipping_discount_amount'      => $addressTotalData['shipping_discount_amount'],
            'base_shipping_discount_amount' => $addressTotalData['base_shipping_discount_amount'],
            'tax_amount'                    => $addressTotalData['tax_amount'],
            'base_tax_amount'               => $addressTotalData['base_tax_amount'],
            'shipping_tax_amount'           => $addressTotalData['shipping_tax_amount'],
            'base_shipping_tax_amount'      => $addressTotalData['base_shipping_tax_amount'],
            'subtotal_incl_tax'             => $addressTotalData['subtotal_incl_tax'],
            'shipping_incl_tax'             => $addressTotalData['shipping_incl_tax'],
            'base_shipping_incl_tax'        => $addressTotalData['base_shipping_incl_tax'],
            'base_currency_code'            => $quoteData['base_currency_code'],
            'quote_currency_code'           => $quoteData['quote_currency_code'],
            'items_qty'                     => $quoteData['items_qty'],
            'items'                         => [],
            'total_segments'                => [],
            'coupon_code'                   => $quoteData['coupon_code']
        ];

        foreach ($quoteObj->getAllVisibleItems() as $item) {
            $itemDto = $item->getData();
            $product = $item->getProduct();

            if ($product && 'configurable' === $product->getTypeId()) {
                $itemDto['parentSku'] = $product->getData('sku');
            }

            $itemDto['options'] = $this->getOptions($item);
            $totalsDTO['items'][] = $itemDto;
        }

        $totalsCollection = $quoteObj->getTotals();

        foreach ($totalsCollection as $code => $total) {
            $totalsDTO['total_segments'][] = $total->getData();
        }

        return $totalsDTO;
    }

    /**
     * @param Mage_Catalog_Model_Product_Configuration_Item_Interface $item
     *
     * @return string
     */
    public function getOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
    {
        $optionsData = [];
        /** @var Mage_Catalog_Helper_Product_Configuration $helper */
        $helper = Mage::helper('catalog/product_configuration');
        $options = $helper->getOptions($item);

        foreach ($options as $index => $optionValue) {
            $option = $helper->getFormattedOptionValue($optionValue);
            $optionsData[$index] = $option;
            $optionsData[$index]['label'] = $optionValue['label'];
        }

        return $this->serialize($optionsData);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function serialize(array $data)
    {
        return json_encode($data);
    }
}
