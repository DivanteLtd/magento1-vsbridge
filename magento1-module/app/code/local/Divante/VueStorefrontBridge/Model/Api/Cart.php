<?php

/**
 * Class Divante_VueStorefrontBridge_Model_Api_Cart
 *
 * @package     Divante
 * @category    VueStoreFrontIndexer
 * @author      Agata Firlejczyk <afirlejczyk@divante.pl
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 * @license     See LICENSE_DIVANTE.txt for license details.
 */
class Divante_VueStorefrontBridge_Model_Api_Cart
{
    private $productList = [];

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Cart_Item
     */
    private $cartItem;

    /**
     * Divante_VueStorefrontBridge_Model_Api_Cart constructor.
     */
    public function __construct()
    {
        $this->cartItem = Mage::getSingleton('vsbridge/api_cart_item');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @throws Mage_Core_Exception
     */
    public function saveQuote(Mage_Sales_Model_Quote $quote)
    {
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals();
        $quote->save();
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $item
     *
     * @return array
     */
    public function getItemAsArray(Mage_Sales_Model_Quote_Item $cartItem)
    {
        if ($cartItem instanceof Mage_Sales_Model_Quote_Item) {
            $item = [
                'item_id' => (int)$cartItem->getId(),
                'sku' => $cartItem->getSku(),
                'name' => $cartItem->getName(),
                'price' => (float)$cartItem->getPrice(),
                'qty' => $cartItem->getQty(),
                'product_type' => $cartItem->getProductType(),
                'quote_id' => (int)$cartItem->getQuoteId(),
            ];

            $item['product_option']['extension_attributes'] = [
                'configurable_item_options' => $this->cartItem->getConfigurableOptions($cartItem),
            ];

            return $item;
        }

        return [];
    }

    /**
     * @param          $quoteObj
     * @param stdClass $cartItem
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function updateItem(Mage_Sales_Model_Quote $quoteObj, stdClass $cartItem)
    {
        $params = $this->prepareParams($cartItem);

        $item = $quoteObj->updateItem(
            $cartItem->item_id,
            new Varien_Object($params)
        );

        $this->saveQuote($quoteObj);

        return $this->getItemAsArray($item);
    }

    /**
     * @param stdClass $cartItem
     *
     * @return array
     */
    public function prepareParams(stdClass $cartItem)
    {
        $qty = 1;

        if (isset($cartItem->qty)) {
            $qty = $cartItem->qty;
        }

        $params = [
            'qty' => max(1, $qty),
        ];

        if (isset($cartItem->product_option) && isset($cartItem->product_option->extension_attributes)) {
            $productOption = (array)$cartItem->product_option->extension_attributes;

            if (!empty($productOption['configurable_item_options'])) {
                $options = $productOption['configurable_item_options'];

                foreach ($options as $option) {
                    $option = (array)$option;
                    $params['super_attribute'][$option['option_id']] = $option['option_value'];
                }
            }
        }

        if (isset($cartItem->item_id)) {
            $params['id'] = $cartItem->item_id;
        }

        return $params;
    }

    /**
     * @param string $productSku
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($productSku)
    {
        if (!isset($this->productList[$productSku])) {
            $productId = Mage::getModel('catalog/product')->getIdBySku($productSku);
            $product = Mage::getModel('catalog/product')->load($productId);
            $this->productList[$productSku] = $product;
        }

        return $this->productList[$productSku];
    }

    /**
     * @param Mage_Sales_Model_Quote $quoteObj
     * @param stdClass               $cartItem
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function addProductToCart(
        Mage_Sales_Model_Quote $quoteObj,
        stdClass $cartItem
    ) {
        if (isset($cartItem->product)) {
            $product = $cartItem->product;
        } else {
            $product = $this->getProduct($cartItem->sku);
        }

        $params = $this->prepareParams($cartItem);
        $item = $quoteObj->addProduct($product, new Varien_Object($params));

        if (is_string($item)) {
            Mage::throwException($item);
        }

        $this->saveQuote($quoteObj);

        if ($item->getParentItemId()) {
            $item = $item->getParentItem();
        }

        return $this->getItemAsArray($item);
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param                        $id
     *
     * @return Varien_Object
     */
    public function findItemByProductId(Mage_Sales_Model_Quote $quote, $id)
    {
        $items = $quote->getItemsCollection();
        $item = $items->getItemByColumnValue('product_id', $id);

        return $item;
    }
}
