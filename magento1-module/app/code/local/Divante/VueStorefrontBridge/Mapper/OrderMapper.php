<?php
require_once('AbstractMapper.php');
require_once('OrderItemMapper.php');
require_once('PaymentMapper.php');

/**
 * Divante VueStorefrontBridge OrderMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class OrderMapper extends AbstractMapper
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

        $orderItemMapper = new OrderItemMapper();
        foreach($order->getAllVisibleItems() as $item) {
            $itemDto = $orderItemMapper->toDto($item);
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

        $paymentMapper = new PaymentMapper();
        $payment = $order->getPayment();
        $paymentDto = $paymentMapper->toDto($payment);
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
