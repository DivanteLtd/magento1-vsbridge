<?php

require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');

/**
 * Class Divante_VueStorefrontBridge_AddressController
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @author      Agata Firlejczyk <afirlejczyk@divante.com>
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_AddressController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * @var array
     */
    private $addressBlackList = [
        'attribute_set_id',
        'entity_type_id',
        'is_default_billing',
        'is_default_shipping',
        'entity_id',
        'customer_id',
        'created_at',
        'updated_at',
    ];

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Customer_Address
     */
    private $addressModel;

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Request
     */
    private $requestModel;

    /**
     * @var Mage_Customer_Helper_Data
     */
    private $helper;

    /**
     * Divante_VueStorefrontBridge_AddressController constructor.
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

        $this->requestModel = Mage::getSingleton('vsbridge/api_request');
        $this->addressModel = Mage::getSingleton('vsbridge/api_customer_address');
        $this->helper = Mage::helper('customer');
    }

    /**
     * Retrieve customer addresses
     */
    public function listAction()
    {
        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->requestModel->currentCustomer($this->getRequest());

        if ($customer && $customer->getId()) {
            try {
                $customerAddresses = [];

                foreach ($customer->getAddresses() as $address) {
                    $customerAddresses[] = $this->prepareAddress($address, $customer);
                }

                return $this->_result(200, $customerAddresses);
            } catch (Exception $e) {
                return $this->_result(500, $e->getMessage());
            }
        }

        return $this->_result(500, 'User is not authroized to access self');
    }

    /**
     * Delete customer address
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

        if (!isset($request->address) || (!isset($request->address->id))) {
            return $this->_result(500, 'No address data provided!');
        }

        $customer = $this->requestModel->currentCustomer($this->getRequest());

        if ($customer && $customer->getId()) {
            $address = $request->address;
            $customerAddress = $this->addressModel->loadCustomerAddressById($address);

            if (!$customerAddress->getId()) {
                return $this->_result(500, sprintf('Address with %d does not exist.', $address->id));
            }

            if ($customerAddress->getCustomerId() !== $customer->getId()) {
                return $this->_result(500, $this->helper->__('The address does not belong to this customer.'));
            }

            try {
                $customerAddress->delete();

                return $this->_result(200, $this->helper->__('The address has been deleted.'));
            } catch (Exception $e) {
                Mage::logException($e);
                return $this->_result(500, $this->helper->__('An error occurred while deleting the address.'));
            }
        }

        return $this->_result(500, 'User is not authroized to access self');
    }

    /**
     * Retreive customer address by id
     */
    public function getAction()
    {
        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }

        $request = $this->getRequest();
        $addressId = $request->getParam('addressId', 0);

        if (!$addressId) {
            return $this->_result(500, 'No address data provided!');
        }

        $customer = $this->requestModel->currentCustomer($this->getRequest());

        if ($customer && $customer->getId()) {
            $address = new stdClass();
            $address->id = $addressId;
            $customerAddress = $this->addressModel->loadCustomerAddressById($address);

            if (!$customerAddress->getId()) {
                return $this->_result(500, sprintf('Address with %d does not exist.', $address->id));
            }

            if ($customerAddress->getCustomerId() !== $customer->getId()) {
                return $this->_result(500, $this->helper->__('The address does not belong to this customer.'));
            }

            return $this->_result(200, $this->prepareAddress($customerAddress, $customer));
        }

        return $this->_result(500, 'User is not authorized to access self');
    }

    /**
     * Update customer address
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

        if (!$request->address) {
            return $this->_result(500, 'No address data provided!');
        }

        $customer = $this->requestModel->currentCustomer($this->getRequest());

        if ($customer && $customer->getId()) {
            $addressData = $request->address;
            $address = $this->addressModel->loadCustomerAddressById($addressData);

            if ($address->getId() && $address->getCustomerId() !== $customer->getId()) {
                return $this->_result(500, 'User is not authorized to access self');
            }

            try {
                $this->addressModel->saveAddress($address, _object_to_array($addressData), $customer);
                $addressData = new stdClass();
                $addressData->id = $address->getId();
                $address = $this->addressModel->loadCustomerAddressById($addressData);
                $customer = Mage::getModel('customer/customer')->load($customer->getId());
            } catch (\Exception $e) {
                return $this->_result(500, $e->getMessage());
            }

            return $this->_result(200, $this->prepareAddress($address, $customer));
        }

        return $this->_result(500, 'User is not authorized to access self');
    }

    /**
     * @param Mage_Customer_Model_Address  $address
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return array
     */
    private function prepareAddress(Mage_Customer_Model_Address $address, Mage_Customer_Model_Customer $customer)
    {
        $addressDTO = $this->addressModel->prepareAddress($address, $customer);

        return $this->_filterDTO($addressDTO, $this->addressBlackList);
    }
}
