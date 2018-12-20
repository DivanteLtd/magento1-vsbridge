<?php
require_once('AbstractController.php');
require_once(__DIR__.'/../helpers/JWT.php');

/**
 * Class Divante_VueStorefrontBridge_StockController
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_OrderController extends Divante_VueStorefrontBridge_AbstractController
{

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
        $this->requestModel = Mage::getSingleton('vsbridge/api_request');
    }

    /**
     * Place order for user
     */
    public function placeOrderAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        $request = $this->_getJsonBody();

        if (!$request) {
            return $this->_result(
                500,
                'No JSON object found in the request body'
            );
        }

        if (!empty($request->user_id)) {
            return $this->_result(500, 'Only placing order for guest is supported.');
        }

        $this->getRequest()->setParam(
            'cartId',
            $request->cart_id
        );

        $quoteObj = $this->requestModel->currentQuote($this->getRequest());

        if (!$quoteObj->getIsActive()) {
            return $this->_result(500, sprintf('No such entity with id %s', $request->cart_id));
        }

        try {

            /** @var Divante_VueStorefrontBridge_Model_Api_Order_Create $apiOrderService */
            $apiOrderService = Mage::getModel('vsbridge/api_order_create', $quoteObj);
            $order = $apiOrderService->create($request);

            return $this->_result(200, $order->getId());
        } catch (\Exception $e) {
            return $this->_result(500, $e->getMessage());
        }
    }
}
