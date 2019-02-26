<?php

/**
 * Class Divante_VueStorefrontBridge_Model_Api_Wishlist
 *
 * @package     Divante
 * @category    VueStoreFrontIndexer
 * @author      Agata Firlejczyk <afirlejczyk@divante.pl
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 * @license     See LICENSE_DIVANTE.txt for license details.
 */
class Divante_VueStorefrontBridge_Model_Api_Wishlist
{

    /**
     * @var array
     */
    private $productList = [];

    /**
     * @param $customer
     *
     * @return Mage_Wishlist_Model_Wishlist|null
     */
    public function getWishListByCustomer(Mage_Customer_Model_Customer $customer)
    {
        $customerId = $customer->getId();
        /* @var Mage_Wishlist_Model_Wishlist $wishlist */
        $wishlist = Mage::getModel('wishlist/wishlist');
        $wishlist->loadByCustomer($customerId, true);

        if (!$wishlist->getId() || $wishlist->getCustomerId() != $customerId) {
            $wishlist = null;
        }

        return $wishlist;
    }

    /**
     * @param int $productId
     *
     * @return bool
     */
    public function productExists($productId)
    {
        $product = $this->getProduct($productId);

        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $productId
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($productId)
    {
        if (!isset($this->productList[$productId])) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $this->productList[$productId] = $product;
        }

        return $this->productList[$productId];
    }

    /**
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @param                              $productId
     *
     * @return bool
     */
    public function isInWishList(Mage_Wishlist_Model_Wishlist $wishlist, $productId)
    {
        //checking if given product isn't already in wishlist
        foreach ($wishlist->getItemCollection() as $item) {
            if ((int)$item->getProductId() === (int)$productId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @param string                       $productId
     *
     * @return bool
     */
    public function addProductToWishlist(Mage_Wishlist_Model_Wishlist $wishlist, $productId)
    {
        $product = $this->getProduct($productId);

        try {
            $result = $wishlist->addNewItem($product);

            if (is_string($result)) {
                Mage::throwException($result);
            }

            $wishlist->save();

            return $result;
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;
    }

    /**
     * @param Mage_Wishlist_Model_Wishlist $wishlist
     * @param Mage_Wishlist_Model_Item     $item
     *
     * @return bool
     */
    public function removeProductFromWishlist(
        Mage_Wishlist_Model_Wishlist $wishlist,
        Mage_Wishlist_Model_Item $item
    ) {
        if (!$item) {
            return false;
        }

        try {
            $item->delete();
            $wishlist->save();
        } catch (\Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Wishlist_Model_Wishlist $wishList
     * @param array                        $productIds
     *
     * @return bool
     */
    public function overrideWishList(Mage_Wishlist_Model_Wishlist $wishList, array $productIds)
    {
        try {
            $items = $wishList->getItemCollection();
            $productToAdd = [];

            foreach ($productIds as $productId) {
                $item = $items->getItemByColumnValue('product_id', $productId);

                if ($item === null) {
                    $productToAdd[] = $productId;
                }
            }

            /** @var Mage_Wishlist_Model_Item $item */
            foreach ($items as $item) {
                $productId = $item->getProductId();

                if (!in_array($productId, $productIds)) {
                    $item->isDeleted(true);
                }
            }

            foreach ($productToAdd as $productId) {
                $productId = intval($productId);

                try {
                    $result = $wishList->addNewItem($productId, new Varien_Object());

                    /** there might be a problem with adding particular product to wishlsit */
                    if (is_string($result)) {
                        $this->logMessage('Product ID: '. $productId, Zend_Log::INFO);
                        $this->logMessage($result, Zend_Log::INFO);
                    }
                } catch (Exception $exception) {
                    $this->logMessage($exception->getMessage(), Zend_Log::DEBUG);
                }
            }

            $items->save();
            $wishList->save();
        } catch (Exception $e) {
            $this->logMessage($e->getMessage(), Zend_Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Wishlist_Model_Wishlist $wishList
     *
     * @return Mage_Wishlist_Model_Resource_Item_Collection
     * @throws Mage_Core_Model_Store_Exception
     */
    public function reLoadWishListItems(Mage_Wishlist_Model_Wishlist $wishList)
    {
        /** @var $currentWebsiteOnly boolean */
        $currentWebsiteOnly = !Mage::app()->getStore()->isAdmin();

        return Mage::getResourceModel('wishlist/item_collection')
            ->addWishlistFilter($wishList)
            ->addStoreFilter($wishList->getSharedStoreIds($currentWebsiteOnly))
            ->setVisibilityFilter();
    }

    /**
     * @param Mage_Wishlist_Model_Wishlist $wishList
     * @param                              $productId
     *
     * @return Mage_Wishlist_Model_Item
     */
    public function findItemByProductId(Mage_Wishlist_Model_Wishlist $wishList, $productId)
    {
        $wishListItems = $wishList->getItemCollection();

        return $wishListItems->getItemByColumnValue('product_id', $productId);
    }

    /**
     * @param string $message
     * @param int $level
     *
     * @return void
     */
    private function logMessage($message, $level)
    {
        Mage::log($message, $level, 'vsbridge_wishlist.log', true);
    }
}