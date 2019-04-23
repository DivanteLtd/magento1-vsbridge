<?php

/**
 * Divante VueStorefrontBridge StockMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Stock extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Get StockDto from StockItem
     *
     * @param Mage_CatalogInventory_Model_Stock_Item $stock
     *
     * @return array
     */
    protected function getDto($stock)
    {
        $stockDto = $stock->getData();

        $stockDto['stock_status_changed_auto'] = boolval($stockDto['stock_status_changed_auto']);
        $stockDto['stock_status_changed_automatically'] = boolval($stockDto['stock_status_changed_automatically']);
        $stockDto['notify_stock_qty'] = $stock->getNotifyStockQty();

        return $stockDto;
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
            'entity_id'
        ];
    }
}
