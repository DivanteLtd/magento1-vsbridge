<?php
require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');
class Divante_VueStorefrontBridge_CartController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * Create shopping cart - https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartcreate
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_ExceptionC
     */
    public function createAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST' && $this->getRequest()->getMethod() !== 'OPTIONS') {
            return $this->_result(500, 'Only POST method allowed');
        } else {
            $store = $this->_currentStore();
            $customer = $this->_currentCustomer($this->getRequest());

            // quote assign to new customer
            $quoteObj = Mage::getModel('sales/quote');
            if ($customer)
                $quoteObj->assignCustomer($customer);

            $quoteObj->setStoreId($store->getId());
            $quoteObj->collectTotals();
            $quoteObj->setIsActive(true);
            $quoteObj->save();

            $secretKey = trim(Mage::getConfig()->getNode('default/auth/secret'));
            return $this->_result(200, $customer ? $quoteObj->getId() : JWT::encode(array('cartId' =>$quoteObj->getId()), $secretKey));
        }
    }

    public function pullAction()
    {
        if ($this->getRequest()->getMethod() !== 'GET') {
            return $this->_result(500, 'Only GET method allowed');
        } else {
            $customer = $this->_currentCustomer($this->getRequest());
            $quoteObj = $this->_currentQuote($this->getRequest());

            if(!$quoteObj) {
                return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
            } else {
                if(!$this->_checkQuotePerms($quoteObj, $customer)) {
                    return $this->_result(500, 'Mismatched quote owner for cartId = '.$this->getRequest()->getParam('cartId'));
                } else {
                    $items = array();
                    foreach ($quoteObj->getAllItems() as $item) {
                        $itemDto = $item->getData();
                        $items[] = array(
                            'item_id' => $itemDto['item_id'],
                            'sku' => $itemDto['sku'],
                            'name' => $itemDto['name'],
                            'price' => $itemDto['price'],
                            'qty' => $itemDto['qty'],
                            'product_type' => $itemDto['product_type'],
                            'quote_id' => $itemDto['quote_id']
                        );
                    }
                    return $this->_result(200, $items);

                }
            }
        }
    }


    /**
     * Apply Discount Code
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartapply-coupon
     * 
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_ExceptionC
     */
    public function applyCouponAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
            return $this->_result(500, 'Only POST method allowed');
        } else {
            $store = $this->_currentStore();
            $customer = $this->_currentCustomer($this->getRequest());
            $quoteObj = $this->_currentQuote($this->getRequest());

            if(!$quoteObj) {
                return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
            } else {
                if(!$this->_checkQuotePerms($quoteObj, $customer)) {
                    return $this->_result(500, 'Mismatched quote owner for cartId = '.$this->getRequest()->getParam('cartId'));
                } else {
                    $couponCode = $this->getRequest()->getParam('coupon');
                    
                    if(!$couponCode) {
                        return $this->_result(500, 'Coupon code is required');
                    }
                    
                    try {
                        $request = $this->_getJsonBody();
                        $quoteObj->setCouponCode($couponCode)->collectTotals()->save();

                        return $this->_result(200, true);
                    } catch (Exception $err) {
                        return $this->_result(500, false);
                    }

                }
            }
        }
    }  

   /**
     * Delete Discount Code
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartdelete-coupon
     * 
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_ExceptionC
     */
    public function deleteCouponAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
            return $this->_result(500, 'Only POST method allowed');
        } else {
            $store = $this->_currentStore();
            $customer = $this->_currentCustomer($this->getRequest());
            $quoteObj = $this->_currentQuote($this->getRequest());

            if(!$quoteObj) {
                return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
            } else {
                if(!$this->_checkQuotePerms($quoteObj, $customer)) {
                    return $this->_result(500, 'Mismatched quote owner for cartId = '.$this->getRequest()->getParam('cartId'));
                } else {
                    try {
                        $request = $this->_getJsonBody();
                        $quoteObj->setCouponCode('')->collectTotals()->save();

                        return $this->_result(200, true);
                    } catch (Exception $err) {
                        return $this->_result(500, false);
                    }

                }
            }
        }
    }      
    
   /**
     * Get Discount Code
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#get-vsbridgecartcoupon
     * 
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_ExceptionC
     */
    public function couponAction()
    {
        if ($this->getRequest()->getMethod() !== 'GET') {
            return $this->_result(500, 'Only GET method allowed');
        } else {
            $store = $this->_currentStore();
            $customer = $this->_currentCustomer($this->getRequest());
            $quoteObj = $this->_currentQuote($this->getRequest());

            if(!$quoteObj) {
                return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
            } else {
                if(!$this->_checkQuotePerms($quoteObj, $customer)) {
                    return $this->_result(500, 'Mismatched quote owner for cartId = '.$this->getRequest()->getParam('cartId'));
                } else {
                    try {
                        return $this->_result(200, $quoteObj->getCouponCode());
                    } catch (Exception $err) {
                        return $this->_result(500, false);
                    }

                }
            }
        }
    }    
    

    /**
     * Get Quote totals and Collect totals and set shipping information
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartcollect-totals
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#get-vsbridgecarttotals
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartshipping-information
     */
    public function totalsAction()
    {
        if (!in_array($this->getRequest()->getMethod(), array('GET', 'POST'))) {
            return $this->_result(500, 'Only GET or POST methods allowed');
        } else {
            $customer = $this->_currentCustomer($this->getRequest());
            $quoteObj = $this->_currentQuote($this->getRequest());

            if(!$quoteObj) {
                return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
            } else {
                if(!$this->_checkQuotePerms($quoteObj, $customer)) {
                    return $this->_result(500, 'Mismatched quote owner for cartId = '.$this->getRequest()->getParam('cartId'));
                } else {
                    $request = $this->_getJsonBody();

                    if ($request) {
                        $paymentMethodCode = $request->methods->paymentMethod->method;
                        $shippingMethodCode = $request->methods->shippingMethodCode;
                        $shippingMethodCarrier = $request->methods->shippingMethodCarrier;


                        $address = null;
                        if ($quoteObj->isVirtual()) {
                            $address = $quoteObj->getBillingAddress();
                        } else {
                            $address = $quoteObj->getShippingAddress();

                            $shippingAddress = $quoteObj->getShippingAddress();

                            if($request->addressInformation) {

                                $shippingMethodCarrier = $request->addressInformation->shipping_carrier_code;
                                $shippingMethodCode = $request->addressInformation->shipping_method_code;
                                        
                                $countryId = $request->addressInformation->shipping_address->country_id;
                                if($countryId) {
                                    $shippingAddress->setCountryId($countryId)->setCollectShippingrates(true)->save();
                                }
                            }
                            
                            $shippingAddress->setCollectShippingRates(true)
                                ->collectShippingRates()
                                ->setShippingMethod($shippingMethodCode);                            
                        }                        

                        if ($address) {
                            if($paymentMethodCode)
                                $address->setPaymentMethod($paymentMethodCode);
                        }
                    }
                    
                    $quoteData = $quoteObj->collectTotals()->save()->getData();
                    $totalsDTO = array(
                        'grand_total' => $quoteData['grand_total'],
                        'base_grand_total' => $quoteData['base_grand_total'],
                        'base_subtotal' => $quoteData['base_subtotal'],
                        'subtotal' => $quoteData['subtotal'],
                        'discount_amount' => $quoteData['discount_amount'],
                        'base_discount_amount' => $quoteData['base_discount_amount'],
                        'subtotal_with_discount' => $quoteData['subtotal_with_discount'],
                        'shipping_amount' => $quoteData['shipping_amount'],
                        'base_shipping_amount' => $quoteData['base_shipping_amount'],
                        'shipping_discount_amount' => $quoteData['shipping_discount_amount'],
                        'base_shipping_discount_amount' => $quoteData['base_shipping_discount_amount'],
                        'tax_amount' => $quoteData['tax_amount'],
                        'base_tax_amount' => $quoteData['base_tax_amount'],
                        'weee_tax_applied_amount' => $quoteData['weee_tax_applied_amount'],
                        'shipping_tax_amount' => $quoteData['shipping_tax_amount'],
                        'base_shipping_tax_amount' => $quoteData['base_shipping_tax_amount'],
                        'subtotal_incl_tax' => $quoteData['subtotal_incl_tax'],
                        'base_subtotal_incl_tax' => $quoteData['base_subtotal_incl_tax'],
                        'shipping_incl_tax' => $quoteData['shipping_incl_tax'],
                        'base_shipping_incl_tax' => $quoteData['base_shipping_incl_tax'],                        
                        'base_currency_code' => $quoteData['base_currency_code'],
                        'quote_currency_code' => $quoteData['quote_currency_code'],
                        'items_qty' => $quoteData['items_qty'],
                        'items' => array(),
                        'total_segments' => array()
                    );

                    foreach ($quoteObj->getAllItems() as $item) {
                        $itemDto = $item->getData();
                        $totalsDTO['items'][] = $itemDto;
                    }
                    $totalsCollection = $quoteObj->getTotals();
                    foreach($totalsCollection as $code => $total) {
                        $totalsDTO['total_segments'][] = $total->getData();
                    }
                    return $this->_result(200, $totalsDTO);

                }
            }
        }
    }    

    protected function _canUsePaymentMethod($method, $quote)
    {
        if (!($method->isGateway() || $method->canUseInternal())) {
            return false;
        }

        if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry())) {
            return false;
        }

        if (!$method->canUseForCurrency(Mage::app()->getStore($quote->getStoreId())->getBaseCurrencyCode())) {
            return false;
        }

        /**
         * Checking for min/max order total for assigned payment method
         */
        $total = $quote->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }

        return true;
    }    

    /**
     * Get active payment methods
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#get-vsbridgecartpayment-methods
     */
    public function paymentMethodsAction()
    {
        if ($this->getRequest()->getMethod() !== 'GET') {
            return $this->_result(500, 'Only GET method allowed');
        } else {
            $customer = $this->_currentCustomer($this->getRequest());
            $quoteObj = $this->_currentQuote($this->getRequest());

            if(!$quoteObj) {
                return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
            } else {
                if(!$this->_checkQuotePerms($quoteObj, $customer)) {
                    return $this->_result(500, 'Mismatched quote owner for cartId = '.$this->getRequest()->getParam('cartId'));
                } else {

                    $store = $quoteObj->getStoreId();
            
                    $total = $quoteObj->getBaseSubtotal();
            
                    $methodsResult = array();
                    $methods = Mage::helper('payment')->getStoreMethods($store, $quoteObj);
            
                    foreach ($methods as $method) {
                        /** @var $method Mage_Payment_Model_Method_Abstract */
                        if ($this->_canUsePaymentMethod($method, $quoteObj)) {
                            $isRecurring = $quoteObj->hasRecurringItems() && $method->canManageRecurringProfiles();
            
                            if ($total != 0 || $method->getCode() == 'free' || $isRecurring) {
                                $methodsResult[] = array(
                                    'code' => $method->getCode(),
                                    'title' => $method->getTitle()
                                );
                            }
                        }
                    }
                                
                    return $this->_result(200, $methodsResult);

                }
            }
        }
    }
    
    protected function _getAllShippingMethods()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
    
        $options = array();
    
        foreach($methods as $_ccode => $_carrier)
        {
            $_methodOptions = array();
            if($_methods = $_carrier->getAllowedMethods())
            {
                foreach($_methods as $_mcode => $_method)
                {
                    $_code = $_ccode . '_' . $_mcode;
                    $_methodOptions[] = array('value' => $_code, 'label' => $_method);
                }
    
                if(!$_title = Mage::getStoreConfig("carriers/$_ccode/title"))
                    $_title = $_ccode;
    
                $options[] = array('value' => $_methodOptions, 'label' => $_title);
            }
        }
    
        return $options;
    }
    /**
     * Get active shipping methods
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartshipping-methods
     */
    public function shippingMethodsAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
            return $this->_result(500, 'Only POST method allowed');
        } else {
            $customer = $this->_currentCustomer($this->getRequest());
            $quoteObj = $this->_currentQuote($this->getRequest());

            if(!$quoteObj) {
                return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
            } else {
                if(!$this->_checkQuotePerms($quoteObj, $customer)) {
                    return $this->_result(500, 'Mismatched quote owner for cartId = '.$this->getRequest()->getParam('cartId'));
                } else {
                    $request = $this->_getJsonBody();
                    $quoteShippingAddress = $quoteObj->getShippingAddress();

                    if($request->address) {
                        $countryId = $request->address->country_id;
                        if($countryId) {
                            $quoteShippingAddress->setCountryId($countryId)->setCollectShippingrates(true)->save();
                        }
                    }
                    
                    $store = $quoteObj->getStoreId();
                    if (is_null($quoteShippingAddress->getId())) {
                        $this->_result(500, 'Shipping address is not set');
                    }
            
                    try {
                        $groupedRates = $quoteShippingAddress->setCollectShippingRates(true)->collectShippingRates()->getGroupedAllShippingRates();
                        $ratesResult = array();
                        foreach ($groupedRates as $carrierCode => $rates ) {
                            $carrierName = $carrierCode;
                            if (!is_null(Mage::getStoreConfig('carriers/'.$carrierCode.'/title'))) {
                                $carrierName = Mage::getStoreConfig('carriers/'.$carrierCode.'/title');
                            }
            
                            foreach ($rates as $rate) {
                                $rateItem = $rate->getData();
                                $rateItem['carrier_title'] = $carrierName;
                                $rateItem['carrier_code'] = $carrierCode;
                                $rateItem['method_code'] = $rateItem['method'];
                                $rateItem['amount'] = $rateItem['price'];
                                
                                $ratesResult[] = $rateItem;
                                unset($rateItem);
                            }
                        }
                        return $this->_result(200, $ratesResult);
                    } catch (Mage_Core_Exception $e) {
                        return$this->_result(500, $e->getMessage());
                    }
                }
            }
        }
    }    

    public function updateAction()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
            return $this->_result(500, 'Only POST method allowed');
        } else {

            $request = $this->_getJsonBody();

            if (!$request) {
                return $this->_result(500, 'No JSON object found in the request body');
            } else {
                if ((!$request->cartItem)) {
                    return $this->_result(500, 'No cartItem data provided!');
                } else {

                    $cartItem = $request->cartItem;

                    $customer = $this->_currentCustomer($this->getRequest());
                    $quoteObj = $this->_currentQuote($this->getRequest());

                    if(!$quoteObj) {
                        return $this->_result(500, 'No quote found for cartId = '.$this->getRequest()->getParam('cartId'));
                    } else {
                        if (!$this->_checkQuotePerms($quoteObj, $customer)) {
                            return $this->_result(500, 'Mismatched quote owner for cartId = ' . $this->getRequest()->getParam('cartId'));
                        } else {

                           try {
                                if ($cartItem->item_id && $cartItem->qty) { // update action
                                    $item = $quoteObj->updateItem($cartItem->item_id, array('qty' => max(1, $cartItem->qty)));
                                    $quoteObj->collectTotals()->save();

                                    $itemDto = $item->getData();

                                    return $this->_result(200, array(
                                        'item_id' => $itemDto['item_id'],
                                        'sku' => $itemDto['sku'],
                                        'name' => $itemDto['name'],
                                        'price' => $itemDto['price'],
                                        'qty' => $itemDto['qty'],
                                        'product_type' => $itemDto['product_type'],
                                        'quote_id' => $itemDto['quote_id']
                                    ));

                                } else {
                                    $product_id = Mage::getModel('catalog/product')->getIdBySku($cartItem->sku);
                                    $product = Mage::getModel('catalog/product')->load($product_id);

                                    if (!$product) {
                                        return $this->_result(500, 'No product found with given SKU = ' . $cartItem->sku);
                                    } else { // stock quantity check required or not?
                                        $item = $quoteObj->addProduct($product, max(1, $cartItem->qty));
                                        $quoteObj->collectTotals()->save();

                                        $itemDto = $item->getData();
                                        return $this->_result(200, array(
                                            'item_id' => $itemDto['item_id'],
                                            'sku' => $itemDto['sku'],
                                            'name' => $itemDto['name'],
                                            'price' => $itemDto['price'],
                                            'qty' => $itemDto['qty'],
                                            'product_type' => $itemDto['product_type'],
                                            'quote_id' => $itemDto['quote_id']
                                        ));
                                    }
                                }
                           } catch (Exception $err) {
                                return $this->_result(500, $err->getMessage());
                            }

                        }
                    }

                }
            }

        }

    }

    public function deleteAction()
    {

        if ($this->getRequest()->getMethod() !== 'POST') {
            return $this->_result(500, 'Only POST method allowed');
        } else {

            $request = $this->_getJsonBody();

            if (!$request) {
                return $this->_result(500, 'No JSON object found in the request body');
            } else {
                if ((!$request->cartItem)) {
                    return $this->_result(500, 'No cartItem data provided!');
                } else {

                    $cartItem = $request->cartItem;

                    $customer = $this->_currentCustomer($this->getRequest());
                    $quoteObj = $this->_currentQuote($this->getRequest());

                    if (!$quoteObj) {
                        return $this->_result(500, 'No quote found for cartId = ' . $this->getRequest()->getParam('cartId'));
                    } else {
                        if (!$this->_checkQuotePerms($quoteObj, $customer)) {
                            return $this->_result(500, 'Mismatched quote owner for cartId = ' . $this->getRequest()->getParam('cartId'));
                        } else {

                            try {
                                if ($cartItem->item_id) { // update action
                                    $quoteObj->removeItem($cartItem->item_id);
                                    $quoteObj->collectTotals()->save();

                                    return $this->_result(200, true);

                                }
                            } catch (Exception $err) {
                                return $this->_result(500, $err->getMessage());
                            }

                        }
                    }

                }
            }
        }
    }
}
?>