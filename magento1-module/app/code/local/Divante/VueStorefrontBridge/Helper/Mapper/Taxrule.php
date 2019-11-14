<?php

/**
 * Divante VueStorefrontBridge TaxruleMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Taxrule extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * @inheritdoc
     */
    protected function getBlacklist()
    {
        return [
            'tax_calculation_rule_id',
            'tax_rates',
            'product_tax_classes',
            'customer_tax_classes'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesToCastInt()
    {
        return [
            'position',
            'priority'
        ];
    }
}
