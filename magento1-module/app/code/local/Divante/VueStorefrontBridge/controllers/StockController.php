<?php
require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');

/**
 * Class Divante_VueStorefrontBridge_StockController
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_StockController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * Retrieve stock data by product sku
     */
    public function checkAction()
    {
        $params = $this->getRequest()->getParams();

        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }

        $paramKey = @array_keys($params)[0];
        $sku = $params[$paramKey];

        if (!$sku) {
            return $this->_result(500, 'No SKU provided');
        }

        try {
            $product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
            $product = Mage::getModel('catalog/product')->load($product_id);
            $stock = $product->getStockItem();
            $stockDTO = $stock->getData();
            $stockDTO['is_in_stock'] = boolval($stockDTO['is_in_stock']);
            $stockDTO['notify_stock_qty'] = $stock->getNotifyStockQty();

            return $this->_result(200, $stockDTO);
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }
}
