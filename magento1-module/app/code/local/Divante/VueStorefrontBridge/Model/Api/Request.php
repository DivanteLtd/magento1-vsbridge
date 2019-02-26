<?php

/**
 * Class Divante_VueStorefrontBridge_WishlistController
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @author      Agata Firlejczyk <afirlejczyk@divante.pl
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_Model_Api_Request
{

    /**
     * @var Mage_Sales_Model_Quote
     */
    private $quote = null;

    /**
     * @var String
     */
    private $errorMessage;

    /**
     * @var Divante_VueStorefrontBridge_Model_Config
     */
    private $configSettings;

    /**
     * Divante_VueStorefrontBridge_Model_Api_Request constructor.
     */
    public function __construct()
    {
        $this->configSettings = Mage::getSingleton('vsbridge/config');
    }

    /**
     * @param $request
     *
     * @return bool
     */
    public function validateQuote($request)
    {
        $customer = $this->currentCustomer($request);
        $quoteObj = $this->currentQuote($request);
        $cartId = $request->getParam('cartId');

        if (!$quoteObj) {
            $this->errorMessage = 'No quote found for cartId = ' . $cartId;

            return false;
        }

        if (!$this->checkQuotePerms($quoteObj, $customer)) {
            $this->errorMessage = 'User is not authroized to access cartId = ' . $cartId;

            return false;
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Quote $quoteObj
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool
     */
    private function checkQuotePerms(Mage_Sales_Model_Quote $quoteObj, $customer)
    {
        $quoteCustomer = $quoteObj->getCustomer();

        return (($customer && $quoteCustomer && $quoteCustomer->getId() === $customer->getId()) || (!$quoteCustomer || !$quoteCustomer->getId()));
    }

    /**
     * Authorize the request against customers (not admin) db and return current customer
     * @param $request
     * @return Mage_Customer_Model_Customer|null
     */
    public function currentCustomer($request)
    {
        $token = $request->getParam('token');
        $secretKey = $this->configSettings->getSecretKey();

        try {
            $tokenData = JWT::decode($token, $secretKey, 'HS256');

            if ($tokenData->id > 0){
                $customer = Mage::getModel('customer/customer')->load($tokenData->id);

                if ($customer->getId()) {
                    return $customer;
                }
            }

            return null;
        } catch (Exception $err) {
            return null;
        }

        return null;
    }

    /**
     * @return string
     */
    private function getSecretKey()
    {
        return $this->configSettings->getSecretKey();
    }

    /**
     * @param $request
     *
     * @return Mage_Sales_Model_Quote|null
     */
    public function currentQuote($request)
    {
        if (null === $this->quote) {
            $cartId = $request->getParam('cartId');

            if (intval(($cartId)) > 0) {
                $this->quote = Mage::getModel('sales/quote')->load($cartId);
            } elseif ($cartId) {
                $secretKey = $this->getSecretKey();
                $tokenData = JWT::decode($cartId, $secretKey, 'HS256');
                $this->quote =  Mage::getModel('sales/quote')->load($tokenData->cartId);
            }
        }

        return $this->quote;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
