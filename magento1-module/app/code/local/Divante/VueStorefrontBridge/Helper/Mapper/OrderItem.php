<?php

/**
 * Divante VueStorefrontBridge OrderItemMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_OrderItem extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     * @var string $_mapperIdentifier
     */
    protected $_mapperIdentifier = 'order_item';

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastFloat()
    {
        return [
            'weight',
            'base_cost',
            'price',
            'base_price',
            'original_price',
            'base_original_price',
            'tax_percent',
            'tax_amount',
            'base_tax_amount',
            'tax_invoiced',
            'base_tax_invoiced',
            'discount_percent',
            'discount_amount',
            'base_discount_amount',
            'discount_invoiced',
            'base_discount_invoiced',
            'amount_refunded',
            'base_amount_refunded',
            'row_total',
            'base_row_total',
            'row_invoiced',
            'base_row_invoiced',
            'row_weight',
            'base_tax_before_discount',
            'tax_before_discount',
            'price_incl_tax',
            'base_price_incl_tax',
            'row_total_incl_tax',
            'base_row_total_incl_tax',
            'hidden_tax_amount',
            'base_hidden_tax_amount',
            'hidden_tax_invoiced',
            'base_hidden_tax_invoiced',
            'hidden_tax_refunded',
            'base_hidden_tax_refunded',
            'tax_canceled',
            'hidden_tax_canceled',
            'tax_refunded',
            'base_tax_refunded',
            'discount_refunded',
            'base_discount_refunded',
            'base_weee_tax_applied_amount',
            'base_weee_tax_applied_row_amnt',
            'base_weee_tax_applied_row_amount',
            'weee_tax_applied_amount',
            'weee_tax_applied_row_amount',
            'weee_tax_applied',
            'weee_tax_disposition',
            'weee_tax_row_disposition',
            'base_weee_tax_disposition',
            'base_weee_tax_row_disposition',
            'gw_base_price',
            'gw_price',
            'gw_base_tax_amount',
            'gw_tax_amount',
            'gw_base_price_invoiced',
            'gw_price_invoiced',
            'gw_base_tax_amount_invoiced',
            'gw_tax_amount_invoiced',
            'gw_base_price_refunded',
            'gw_price_refunded',
            'gw_base_tax_amount_refunded',
            'gw_tax_amount_refunded',
            'qty_returned'
        ];
    }
}
