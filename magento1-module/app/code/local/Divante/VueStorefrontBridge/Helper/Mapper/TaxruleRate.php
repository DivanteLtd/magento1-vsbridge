<?php

/**
 * Divante VueStorefrontBridge TaxruleRateMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_TaxruleRate extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     */
    const MAPPER_IDENTIFIER = 'taxrule_rate';

    /**
     * @inheritdoc
     */
    protected function getBlacklist()
    {
        return [
            'tax_calculation_rate_id'
        ];
    }
}
