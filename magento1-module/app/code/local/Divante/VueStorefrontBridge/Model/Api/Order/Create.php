<?php

/**
 * Class Divante_VueStorefrontBridge_CartController
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_Model_Api_Order_Create
{

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Cart
     */
    private $cartModel;

    /**
     * @var Mage_Sales_Model_Quote
     */
    private $quote;

    /**
     * @var Mage_Customer_Model_Customer
     */
    private $customer;

    /**
     * Divante_VueStorefrontBridge_Model_Api_Order constructor.
     *
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->quote     = $payload[0];
        $this->customer  = $payload[1];
        $this->cartModel = Mage::getSingleton('vsbridge/api_cart');
    }

    /**
     * Only Guest Order are supported for now
     * @param $requestPayload
     *
     * @return Mage_Sales_Model_Order
     * @throws Mage_Core_Exception
     */
    public function execute($requestPayload)
    {
        if (is_null($this->quote)) {
            Mage::throwException('No quote entity passed to order create model');
        }

        $this->checkAndAddProducts($requestPayload);

        $billingAddress = (array)$requestPayload->addressInformation->billingAddress;
        $shippingAddress = (array)$requestPayload->addressInformation->shippingAddress;

        // Assign customer if exists
        if (!$this->customer) {
            $this->quote->setCustomerIsGuest(true);
        } else {
            $this->quote->setCustomerIsGuest(false);
            $this->quote->assignCustomer($this->customer);
        }

        // Add billing address to quote
        /** @var Mage_Sales_Model_Quote_Address $billingAddressData */
        $billingAddressData = $this->quote->getBillingAddress()->addData($billingAddress);
        $billingAddressData->implodeStreetAddress();
        $this->quote->setCustomerEmail($billingAddressData->getEmail());
        $this->quote->setCustomerFirstname($billingAddressData->getFirstname());
        $this->quote->setCustomerLastname($billingAddressData->getLastname());

        // Add shipping address to quote
        $shippingAddressData = $this->quote->getShippingAddress()->addData($shippingAddress);

        // NA eq to company not defined
        if ($shippingAddress['company'] == 'NA') {
            $shippingAddressData->setCompany(null);
        }

        $shippingAddressData->implodeStreetAddress();
        $shippingMethodCode = $requestPayload->addressInformation->shipping_method_code;
        $paymentMethodCode = $requestPayload->addressInformation->payment_method_code;
        $shippingMethodCarrier = $requestPayload->addressInformation->shipping_carrier_code;
        $shippingMethod = $shippingMethodCarrier  . '_' . $shippingMethodCode;

        // Collect shipping rates on quote shipping address data
        $shippingAddressData->setCollectShippingRates(true)
            ->collectShippingRates();
        // Set shipping and payment method on quote shipping address data
        $shippingAddressData->setShippingMethod($shippingMethod)->setPaymentMethod($paymentMethodCode);

        $this->quote->getPayment()->importData(
            [
                'method' => $paymentMethodCode,
                'additional_information' => (array)$requestPayload->addressInformation->payment_method_additional,
            ]
        );

        $this->quote->getShippingAddress()->setCollectShippingRates(true);
        $this->quote->collectTotals();
        $this->quote->save();

        return $this->createOrderFromQuote();
    }

    /**
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    private function createOrderFromQuote()
    {
        /** @var Mage_Sales_Model_Service_Quote $service */
        $service = Mage::getModel('sales/service_quote', $this->quote);
        $service->submitAll();

        /** @var Mage_Sales_Model_Order $order */
        $order = $service->getOrder();

        if ($order) {
            $order->queueNewOrderEmail();
            $this->quote->save();
            Mage::getModel('sales/order')->getResource()->updateGridRecords($order->getId());
        }

        return $order;
    }

    /**
     * @param $requestPayload
     */
    private function checkAndAddProducts($requestPayload)
    {
        $clientItems = $requestPayload->products;
        $currentQuoteItems = $this->quote->getAllVisibleItems();

        foreach ($clientItems as $product) {
            $sku = $product->sku;
            $serverItem = $this->findProductInQuote($sku, $currentQuoteItems);

            if ($serverItem) {
                if ($product->qty !== $serverItem->getQty()) {
                    $serverItem->setQty($product->qty);
                }
            } else {
                $cartItem = new stdClass();

                if (isset($product->product_option)) {
                    $cartItem->product_option = $product->product_option;
                }

                if (isset($product->qty)) {
                    $cartItem->qty = max($product->qty, 1);
                } else {
                    $cartItem->qty = 1;
                }

                $cartItem->sku = $product->sku;

                $this->addProductToQuote($cartItem);
            }
        }

        foreach ($currentQuoteItems as $item) {
            $clientItem = $this->findProductInRequest($item->getData('sku'), $clientItems);

            if (null === $clientItem) {
                $this->quote->deleteItem($item);
            }
        }
    }

    /**
     * @param stdClass $cartItem
     */
    private function addProductToQuote(stdClass $cartItem)
    {
        $params = $this->cartModel->prepareParams($cartItem);
        $product = $this->cartModel->getProduct($cartItem->sku);
        $this->quote->addProduct($product, new Varien_Object($params));
    }

    /**
     * @param $sku
     * @param $items
     *
     * @return Mage_Sales_Model_Quote_Item|null
     */
    private function findProductInQuote($sku, $items)
    {
        foreach ($items as $item) {
            if ($item->getData('sku') === $sku) {
                return $item;
            }
        }

        return null;
    }

    private function findProductInRequest($cartItemSku, $products)
    {
        foreach ($products as $product) {
            if ($product->sku === $cartItemSku) {
                return $product;
            }
        }

        return null;
    }
}
