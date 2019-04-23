<?php

require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');

/**
 * Class Divante_VueStorefrontBridge_CartController
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_CartController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Cart
     */
    private $cartModel;

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Cart_Totals
     */
    private $totalsModel;

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Request
     */
    private $requestModel;

    /**
     * Divante_VueStorefrontBridge_WishlistController constructor.
     *
     * @param Zend_Controller_Request_Abstract  $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array                             $invokeArgs
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = []
    ) {
        parent::__construct($request, $response, $invokeArgs);
        $this->cartModel = Mage::getSingleton('vsbridge/api_cart');
        $this->totalsModel = Mage::getSingleton('vsbridge/api_cart_totals');
        $this->requestModel = Mage::getSingleton('vsbridge/api_request');
    }

    /**
     * Create shopping cart -
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartcreate
     */
    public function createAction()
    {
        try {
            if ($this->getRequest()->getMethod() !== 'POST' && $this->getRequest()->getMethod() !== 'OPTIONS') {
                return $this->_result(500, 'Only POST method allowed');
            }

            $store = $this->_currentStore();
            $customer = $this->requestModel->currentCustomer($this->getRequest());
            $quoteObj = null;

            if ($customer instanceof Mage_Customer_Model_Customer) {
                $quoteObj = Mage::getModel('sales/quote')->loadByCustomer($customer);
            }

            if (($quoteObj === null) || !$quoteObj->getId()) {
                // quote assign to new customer
                $quoteObj = Mage::getModel('sales/quote');

                if ($customer instanceof Mage_Customer_Model_Customer) {
                    $quoteObj->assignCustomer($customer);
                }

                $quoteObj->setStoreId($store->getId()); // TODO: return existing user cart id if exists
                $quoteObj->collectTotals();
                $quoteObj->setIsActive(true);
                $quoteObj->getBillingAddress();
                $quoteObj->getShippingAddress();
                $quoteObj->save();
            }

            $secretKey = $this->getSecretKey();

            return $this->_result(
                200,
                $customer ? $quoteObj->getId() : JWT::encode(['cartId' => $quoteObj->getId()], $secretKey)
            );
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    /**
     * Pull the server cart for synchronization
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#get-vsbridgecartpull
     */
    public function pullAction()
    {
        try {
            if (!$this->_checkHttpMethod('GET')) {
                return $this->_result(500, 'Only GET method allowed');
            }

            if (!$this->requestModel->validateQuote($this->getRequest())) {
                return $this->_result(500, $this->requestModel->getErrorMessage());
            }

            /** @var Mage_Sales_Model_Quote $quoteObj */
            $quoteObj = $this->requestModel->currentQuote($this->getRequest());
            $items = [];

            foreach ($quoteObj->getAllVisibleItems() as $item) {
                $items[] = $this->cartModel->getItemAsArray($item);
            }

            return $this->_result(200, $items);
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    /**
     * Apply Discount Code
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartapply-coupon
     */
    public function applyCouponAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        if (!$this->requestModel->validateQuote($this->getRequest())) {
            return $this->_result(500, $this->requestModel->getErrorMessage());
        }

        $quoteObj = $this->requestModel->currentQuote($this->getRequest());
        $couponCode = $this->getRequest()->getParam('coupon');

        if (!$couponCode) {
            return $this->_result(500, 'Coupon code is required');
        }

        try {
            $quoteObj->setCouponCode($couponCode);
            $quoteObj->collectTotals()->save();

            if ($quoteObj->getCouponCode()) {
                return $this->_result(200, true);
            }

            return $this->_result(500, false);
        } catch (Exception $err) {
            return $this->_result(500, false);
        }
    }

    /**
     * Delete Discount Code
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#post-vsbridgecartdelete-coupon
     */
    public function deleteCouponAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        if (!$this->requestModel->validateQuote($this->getRequest())) {
            return $this->_result(500, $this->requestModel->getErrorMessage());
        }

        try {
            $quoteObj = $this->requestModel->currentQuote($this->getRequest());
            $quoteObj->setCouponCode('')->collectTotals()->save();

            return $this->_result(200, true);
        } catch (Exception $err) {
            return $this->_result(500, false);
        }
    }

    /**
     * Get Discount Code
     * https://github.com/DivanteLtd/magento1-vsbridge/blob/master/doc/VueStorefrontBridge%20API%20specs.md#get-vsbridgecartcoupon
     */
    public function couponAction()
    {
        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }

        if (!$this->requestModel->validateQuote($this->getRequest())) {
            return $this->_result(500, $this->requestModel->getErrorMessage());
        }

        try {
            $quoteObj = $this->requestModel->currentQuote($this->getRequest());

            return $this->_result(200, $quoteObj->getCouponCode());
        } catch (Exception $err) {
            return $this->_result(500, false);
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
        try {
            if (!$this->_checkHttpMethod(array('GET', 'POST'))) {
                return $this->_result(500, 'Only GET or POST methods allowed');
            }

            if (!$this->requestModel->validateQuote($this->getRequest())) {
                return $this->_result(500, $this->requestModel->getErrorMessage());
            }

            $quoteObj = $this->requestModel->currentQuote($this->getRequest());
            $request = $this->_getJsonBody();

            if ($request && isset($request->methods)) {
                $paymentMethodCode = $request->methods->paymentMethod->method;
                $shippingMethodCode = $request->methods->shippingMethodCode;

                $address = null;

                if ($quoteObj->isVirtual()) {
                    $address = $quoteObj->getBillingAddress();
                } else {
                    $address = $quoteObj->getShippingAddress();
                    $shippingAddress = $quoteObj->getShippingAddress();

                    if ($request->addressInformation) {
                        $shippingMethodCode = $request->addressInformation->shipping_method_code;
                        $countryId = $request->addressInformation->shipping_address->country_id;

                        if ($countryId) {
                            $shippingAddress->setCountryId($countryId)->setCollectShippingrates(true)->save();
                        }
                    }

                    $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod($shippingMethodCode);
                }

                if ($address && $paymentMethodCode) {
                    $address->setPaymentMethod($paymentMethodCode);
                }
            } else {
                $quoteObj->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
            }

            $this->cartModel->saveQuote($quoteObj);
            $totalsDTO = $this->totalsModel->getTotalsAsArray($quoteObj);

            return $this->_result(200, $totalsDTO);
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    /**
     * @param $method
     * @param $quote
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
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
        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }

        try {
            if (!$this->requestModel->validateQuote($this->getRequest())) {
                return $this->_result(500, $this->requestModel->getErrorMessage());
            }

            $quoteObj = $this->requestModel->currentQuote($this->getRequest());
            $store = $quoteObj->getStoreId();
            $total = $quoteObj->getBaseSubtotal();
            $methodsResult = [];
            $methods = Mage::helper('payment')->getStoreMethods($store, $quoteObj);

            foreach ($methods as $method) {
                /** @var $method Mage_Payment_Model_Method_Abstract */
                if ($this->_canUsePaymentMethod($method, $quoteObj)) {
                    $isRecurring = $quoteObj->hasRecurringItems() && $method->canManageRecurringProfiles();

                    if ($total != 0 || $method->getCode() == 'free' || $isRecurring) {
                        $methodsResult[] = [
                            'code' => $method->getCode(),
                            'title' => $method->getTitle(),
                        ];
                    }
                }
            }

            return $this->_result(200, $methodsResult);
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    /**
     * @return array
     */
    protected function _getAllShippingMethods()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        $options = [];

        foreach ($methods as $ccode => $_carrier) {
            $methodOptions = [];
            $allowedMethods = $_carrier->getAllowedMethods();

            if ($allowedMethods) {
                foreach ($allowedMethods as $allowMcode => $allowMethod) {
                    $code = $ccode . '_' . $allowMcode;
                    $methodOptions[] = ['value' => $code, 'label' => $allowMethod];
                }

                $title = Mage::getStoreConfig("carriers/$ccode/title");

                if (!$title) {
                    $title = $ccode;
                }

                $options[] = ['value' => $methodOptions, 'label' => $title];
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
        try {
            if (!$this->_checkHttpMethod('POST')) {
                return $this->_result(500, 'Only POST method allowed');
            }

            if (!$this->requestModel->validateQuote($this->getRequest())) {
                return $this->_result(500, $this->requestModel->getErrorMessage());
            }

            $quoteObj = $this->requestModel->currentQuote($this->getRequest());
            $request = $this->_getJsonBody();
            $quoteShippingAddress = $quoteObj->getShippingAddress();

            if ($request->address) {
                $countryId = $request->address->country_id;
                if ($countryId) {
                    $quoteShippingAddress->setCountryId($countryId)->setCollectShippingrates(true)->save();
                }
            }

            if (is_null($quoteShippingAddress->getId())) {
                $this->_result(500, 'Shipping address is not set');
            }

            try {
                $groupedRates = $quoteShippingAddress->setCollectShippingRates(true)->collectShippingRates()
                    ->getGroupedAllShippingRates();
                $ratesResult = [];

                foreach ($groupedRates as $carrierCode => $rates) {
                    $carrierName = $carrierCode;
                    if (!is_null(Mage::getStoreConfig('carriers/' . $carrierCode . '/title'))) {
                        $carrierName = Mage::getStoreConfig('carriers/' . $carrierCode . '/title');
                    }

                    foreach ($rates as $rate) {
                        $rateItem = $rate->getData();
                        $rateItem['method_title'] = $carrierName;
                        $rateItem['carrier_code'] = $carrierCode;
                        $rateItem['method_code'] = $rateItem['method'];
                        $rateItem['amount'] = $rateItem['price'];

                        $ratesResult[] = $rateItem;
                        unset($rateItem);
                    }
                }

                return $this->_result(200, $ratesResult);
            } catch (Mage_Core_Exception $e) {
                return $this->_result(500, $e->getMessage());
            }
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    /**
     * Add/Update Item in Cart
     */
    public function updateAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        $request = $this->_getJsonBody();

        if (!$request) {
            return $this->_result(500, 'No JSON object found in the request body');
        }

        if (!$request->cartItem) {
            return $this->_result(500, 'No cartItem data provided!');
        }

        if (!$this->requestModel->validateQuote($this->getRequest())) {
            return $this->_result(500, $this->requestModel->getErrorMessage());
        }

        $cartItem = $request->cartItem;
        /** @var Mage_Sales_Model_Quote $quoteObj */
        $quoteObj = $this->_currentQuote($this->getRequest());

        try {
            if (isset($cartItem->item_id) && isset($cartItem->qty)) { // update action
                $item = $this->cartModel->updateItem($quoteObj, $cartItem);

                return $this->_result(200, $item);
            }

            $product = $this->cartModel->getProduct($cartItem->sku);

            if (!$product) {
                return $this->_result(500, 'No product found with given SKU = ' . $cartItem->sku);
            }

            $item = $this->cartModel->addProductToCart($quoteObj, $cartItem);

            return $this->_result(200, $item);
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }

    /**
     * Delete item from cart
     */
    public function deleteAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        $request = $this->_getJsonBody();

        if (!$request) {
            return $this->_result(500, 'No JSON object found in the request body');
        }

        if ((!$request->cartItem)) {
            return $this->_result(500, 'No cartItem data provided!');
        }

        if (!$this->requestModel->validateQuote($this->getRequest())) {
            return $this->_result(500, $this->requestModel->getErrorMessage());
        }

        $cartItem = $request->cartItem;
        $quoteObj = $this->_currentQuote($this->getRequest());

        try {
            if ($cartItem->item_id) { // update action
                $quoteObj->removeItem($cartItem->item_id);
                $this->cartModel->saveQuote($quoteObj);

                return $this->_result(200, true);
            }
        } catch (Exception $err) {
            return $this->_result(500, $err->getMessage());
        }
    }
}
