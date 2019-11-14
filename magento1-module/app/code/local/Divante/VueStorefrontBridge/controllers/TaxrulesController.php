<?php
require_once('AbstractController.php');

/**
 * Divante VueStorefrontBridge TaxrulesController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Piotr Karwatka <pkarwatka@divante.co>
 * @author      Dariusz Oliwa <doliwa@divante.co>
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_TaxrulesController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * index action
     */
    public function indexAction()
    {
        if ($this->_authorizeAdminUser($this->getRequest())) {
            $this->getResponse()->setHttpResponseCode(300);
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $rate       = Mage::getModel('tax/calculation_rate');
            $collection = Mage::getModel('tax/calculation_rule')->getCollection();
            if ($collection->getSize()) {
                $collection->addCustomerTaxClassesToResult()
                    ->addProductTaxClassesToResult()
                    ->addRatesToResult();
            }
            $taxRules = array();
            if ($collection->getSize()) {
                foreach ($collection as $rule) {
                    $taxRuleDTO = $rule->getData();

                    $taxRuleDTO['id'] = $taxRuleDTO['tax_calculation_rule_id'];
                    $taxRuleDTO['tax_rates_ids'] = $taxRuleDTO['tax_rates'];
                    $taxRuleDTO['product_tax_class_ids'] = $taxRuleDTO['product_tax_classes'];
                    $taxRuleDTO['customer_tax_class_ids'] = $taxRuleDTO['customer_tax_classes'];
                    $taxRuleDTO['rates'] = [];

                    foreach ($taxRuleDTO['tax_rates_ids'] as $rateId) {
                        $rate->load($rateId);
                        $rateDTO       = $rate->getData();
                        $rateDTO['id'] = $rateDTO['tax_calculation_rate_id'];
                        $rateDTO = Mage::helper('vsbridge_mapper/taxruleRate')->filterDto($rateDTO);

                        $taxRuleDTO['rates'][] = $rateDTO;
                    }

                    $taxRuleDTO = Mage::helper('vsbridge_mapper/taxrule')->filterDto($taxRuleDTO);
                    $taxRules[] = $taxRuleDTO;
                }
            }
            $this->_result(200, $taxRules);
        }
    }
}
