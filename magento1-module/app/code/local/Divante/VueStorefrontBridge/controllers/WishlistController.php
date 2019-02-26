<?php

require_once('AbstractController.php');
require_once(__DIR__ . '/../helpers/JWT.php');

/**
 * Class Divante_VueStorefrontBridge_WishlistController
 *
 * @package     Divante
 * @category    VueStorefrontBridge
 * @author      Bartosz Liburski <bliburski@divante.pl>, Agata Firlejczyk <afirlejczyk@divante.pl
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 */
class Divante_VueStorefrontBridge_WishlistController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Wishlist
     */
    private $wishListModel;

    /**
     * @var Divante_VueStorefrontBridge_Model_Api_Cart
     */
    private $cartModel;

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
        $this->wishListModel = Mage::getSingleton('vsbridge/api_wishlist');
        $this->cartModel = Mage::getSingleton('vsbridge/api_cart');
    }

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function pullAction()
    {
        if (!$this->_checkHttpMethod('GET')) {
            return $this->_result(500, 'Only GET method allowed');
        }

        $customer = $this->currentCustomer($this->getRequest());

        if (!$customer || !$customer->getId()) {
            return $this->_result(500, 'No customer found');
        }

        $wishList = $this->wishListModel->getWishListByCustomer($customer);

        if (!$wishList) {
            return $this->_result(500, 'Cannot find WishList for customer.');
        }

        $wishListItems = $wishList->getItemCollection();
        $items = $this->prepareWishListItems($wishListItems);

        return $this->_result(200, ['items' => $items]);
    }

    /**
     * @return Zend_Controller_Response_Abstract
     */
    public function updateAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        }

        $request = $this->_getJsonBody();

        if (!isset($request->wishListItem)) {
            return $this->_result(500, 'No wishlist Item data provided');
        }

        $customer = $this->currentCustomer($this->getRequest());

        if (!$customer || !$customer->getId()) {
            return $this->_result(500, 'No customer found');
        }

        $wishlist = $this->wishListModel->getWishListByCustomer($customer);

        if (!$wishlist) {
            return $this->_result(500, 'no wishlist');
        }

        if (isset($request->wishListItem->forceUpdate) && $request->wishListItem->forceUpdate) {
            if (!isset($request->wishListItem->productIds)) {
                return $this->_result(500, 'No product ids');
            }

            $productIds = $request->wishListItem->productIds;
            $wihlistUpdated = $this->wishListModel->overrideWishList($wishlist, $productIds);

            if ($wihlistUpdated) {
                $itemsCollection = $this->wishListModel->reLoadWishListItems($wishlist);
                $items = $this->prepareWishListItems($itemsCollection);

                return $this->_result(200, ['items' => $items]);
            }

            return $this->_result(500, 'There was a problem with updating WishList.');
        }

        if (!isset($request->wishListItem->productId)) {
            return $this->_result(500, 'no product');
        }

        $productId = (int)$request->wishListItem->productId;

        if ($this->wishListModel->isInWishList($wishlist, $productId)) {
            return $this->_result(
                200,
                sprintf('Product with given ID = %d is already in wishlist', $productId)
            );
        }

        $product = $this->wishListModel->getProduct($productId);

        if (!$product) {
            return $this->_result(500, 'No product found with given ID = ' . $productId);
        }

        if (!$this->wishListModel->productExists($productId)) {
            return $this->_result(500, 'no product with given ID = ' . $productId);
        }

        $res = $this->wishListModel->addProductToWishlist($wishlist, $productId);

        if ($res instanceof Mage_Wishlist_Model_Item) {
            $res = $this->prepareWishListItem($res);
        }

        return $this->_result(200, $res);
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Exception
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

        if (!$request->wishListItem || !$request->wishListItem->productId) {
            return $this->_result(500, 'No wishlist Item data provided');
        }

        $customer = $this->currentCustomer($this->getRequest());

        if (!$customer || !$customer->getId()) {
            return $this->_result(500, 'No customer found');
        }

        $wishlist = $this->wishListModel->getWishListByCustomer($customer);

        if (!$wishlist) {
            $this->_result(500, 'No wishlist');
        }

        $productId = $request->wishListItem->productId;

        if (!$productId) {
            return $this->_result(500, 'No Product provided');
        }

        $item = $this->wishListModel->findItemByProductId($wishlist, $productId);

        if (null === $item) {
            return $this->_result(500, 'No Product with ID = ' . $productId);
        }

        $itemRemoved = $this->wishListModel->removeProductFromWishlist($wishlist, $item);

        return $this->_result(200, $itemRemoved);
    }

    /**
     * @param $wishListItems
     *
     * @return array
     */
    private function prepareWishListItems($wishListItems)
    {
        $result = [];

        /** @var Mage_Wishlist_Model_Item $item */
        foreach ($wishListItems as $item) {
            $result[] = $this->prepareWishListItem($item);
        }

        return $result;
    }

    /**
     * @param Mage_Wishlist_Model_Item $item
     *
     * @return array
     */
    private function prepareWishListItem(Mage_Wishlist_Model_Item $item)
    {
        $wishListDTO = [
            'product_id' => $item->getProductId(),
            'added_at' => $item->getAddedAt(),
            'store_id' => $item->getStoreId(),
            'qty' => $item->getQty(),
            'wishlist_item_id' => $item->getId(),
        ];

        return $wishListDTO;
    }

    /**
     * Authorize the request against customers (not admin) db and return current customer
     * @param $request
     * @return Mage_Customer_Model_Customer|null
     */
    private function currentCustomer($request)
    {
        return $this->_currentCustomer($request);
    }
}
