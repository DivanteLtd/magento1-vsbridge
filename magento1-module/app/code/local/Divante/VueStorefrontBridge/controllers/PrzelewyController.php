<?php
// @codingStandardsIgnoreStart
require_once 'AbstractController.php';
require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../Model/Payment/Przelewy24.php';
require_once __DIR__ . '/../../../../community/Dialcom/Przelewy/Model/Payment/Przelewy.php';

/**
 * Class Divante_VueStorefrontBridge_PrzelewyController
 *
 * @category  Divante
 * @package   VueStoreFrontBridge
 * @author    Dariusz Oliwa <doliwa@divante.pl>
 * @copyright 2018-2019 Divante Sp. z o.o.
 * @license   See LICENSE_DIVANTE.txt for license details.
 * @link      n/a
 */
class Divante_VueStorefrontBridge_PrzelewyController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * Get active payment methods
     *
     * @return string
     */
    public function paymentMethodsAction()
    {
        try {
            /**
             * Payment model
             *
             * @var Divante_VueStorefrontBridge_Model_Payment_Przelewy24 $paymentModel
             */
            $paymentModel       = new Divante_VueStorefrontBridge_Model_Payment_Przelewy24();
            $allPaymentChannels = $paymentModel->getChannels();

            return $this->_result(200, $allPaymentChannels);
        } catch (Exception $e) {
            return $this->_result(500, $e->getMessage());
        }
    }

    /**
     * Selects user chosen payment method
     *
     * @throws Exception
     * @return string
     */
    public function selectpaymentmethodAction()
    {
        if (!$this->_checkHttpMethod('POST')) {
            return $this->_result(500, 'Only POST method allowed');
        } else {
            /**
             * Quote entity
             *
             * @var Mage_Sales_Model_Quote $quote
             */
            $quote = $this->_currentQuote($this->getRequest());
            if (is_null($quote)) {
                return $this->_result(
                    500,
                    'Missing cartId parameter'
                );
            } else {
                $request = $this->_getJsonBody();
                /**
                 * Quote payment entity
                 *
                 * @var Mage_Sales_Model_Quote_Payment $payment
                 */
                $payment = $quote->getPayment();
                $payment->setMethod('dialcom_przelewy');
                $payment->getMethodInstance()->assignData(
                    [
                        'method_id'   => $request->method_id,
                        'method_name' => $request->method_name,
                        'cc_id'       => '',
                        'cc_name'     => '',
                        'method'      => 'dialcom_przelewy',
                    ]
                );
                $payment->save();
                $quote->setPayment($payment);
                $quote->collectTotals();
                $quote->save();

                return $this->_result(200, 'ok');
            }
        }
    }

    /**
     * Redirect to payment provider external gateway action
     *
     * @return mixed
     */
    public function redirectAction()
    {
        try {
            /**
             * Order object
             *
             * @var Divante_Sales_Model_Order $order
             */
            $order = $this->_currentOrder($this->getRequest());
            if (!is_null($order)) {
                $session = Mage::getSingleton('checkout/session');
                $session->setPrzelewyQuoteId($order->getQuoteId());
                $orderId = $order->getId();
                if ($orderId) {
                    Dialcom_Przelewy_Model_Payment_Przelewy::addExtracharge($orderId);
                    $this->getResponse()->setBody(
                        $this->getLayout()->createBlock(
                            'divante_vuestorefrontbridge/payment_przelewy_redirect'
                        )->setData('order', $order->getIncrementId())->getHtml()
                    );
                    $session->unsQuoteId();
                }
            } else {
                return $this->_result(
                    500,
                    'No order found for order parameter value'
                );
            }
        } catch (Exception $e) {
            return $this->_result(500, $e->getMessage());
        }
    }
}
// @codingStandardsIgnoreEnd