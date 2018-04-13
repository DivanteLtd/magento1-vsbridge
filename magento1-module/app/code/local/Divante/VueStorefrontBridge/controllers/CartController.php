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
        if ($this->getRequest()->getMethod() !== 'POST') {
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
                    $itemsDto = array();
                    foreach ($quoteObj->getAllItems() as $item) {
                        $items = array();
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
                                    $product_id = Mage::getModel("catalog/product")->getIdBySku($cartItem->sku);
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