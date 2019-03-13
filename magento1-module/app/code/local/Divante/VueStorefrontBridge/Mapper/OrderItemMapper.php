<?php
require_once('AbstractMapper.php');

/**
 * Divante VueStorefrontBridge OrderItemMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class OrderItemMapper extends AbstractMapper
{
    /**
     * Get OrderItemDto from OrderItem
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return array
     */
    protected function getDto($orderItem)
    {
        $orderItemDto = $orderItem->getData();
        $orderItemDto['id'] = $orderItemDto['item_id'];

        return $orderItemDto;
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
