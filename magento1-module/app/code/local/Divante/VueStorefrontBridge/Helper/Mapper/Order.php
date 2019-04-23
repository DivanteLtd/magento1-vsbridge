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
     * Get OrderDto from Order
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getDto($order)
    {
        $orderDto = $order->getData();
        $orderDto['id'] = $orderDto['entity_id'];
        $orderDto['items'] = [];

        /** @var Mage_Catalog_Model_Resource_Product $resourceModel */
        $resourceModel = Mage::getResourceModel('catalog/product');

        foreach ($order->getAllVisibleItems() as $item) {
            $itemDto = Mage::helper('vsbridge_mapper/orderitem')->toDto($item);
            $itemDto['thumbnail'] = null;

            $image = $resourceModel->getAttributeRawValue(
                $item->getProductId(),
                'thumbnail',
                $order->getStoreId()
            );

            if ($image) {
                $itemDto['thumbnail'] = $image;
            }

            $orderDto['items'][] = $itemDto;
        }

        $paymentDto = Mage::helper('vsbridge_mapper/payment')->toDto($order->getPayment());
        $orderDto['payment'] = $paymentDto;

        return $orderDto;
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
