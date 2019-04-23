<?php

/**
 * Divante VueStorefrontBridge PaymentMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Helper_Mapper_Payment extends Divante_VueStorefrontBridge_Helper_Mapper_Abstract
{
    /**
     * Get PaymentDto from Payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return array
     */
    protected function getDto($payment)
    {
        $paymentDto = $payment->getData();
        $paymentDto['method_title'] = $payment->getMethodInstance()->getTitle();

        return $paymentDto;
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
