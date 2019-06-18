<?php

/**
 * Class Divante_VueStorefrontBridge_Block_Payment_Przelewy_Redirect
 *
 * @category  Divante
 * @package   VueStoreFrontBridge
 * @author    Dariusz Oliwa <doliwa@divante.pl>
 * @copyright 2018-2019 Divante Sp. z o.o.
 * @license   See LICENSE_DIVANTE.txt for license details.
 * @link      n/a
 */
class Divante_VueStorefrontBridge_Block_Payment_Przelewy_Redirect
    extends Dialcom_Przelewy_Block_Payment_Przelewy_Redirect
{

    /**
     * Vsf host name
     */
    const MEDI_MAGENTO_HOST = 'medicover.vuestorefront.io';
    /**
     * P24 back urls map
     *
     * @var array $backUrls
     */
    protected $backUrls = [
        'p24_url_return' => '/thank-you',
        'p24_url_cancel' => '/failure',
    ];

    /**
     * To html
     *
     * @return string
     * @throws Exception
     */
    protected function _toHtml()
    {
        $orderIncrementId = $this->getData('order');

        if (is_null($orderIncrementId)) {
            throw new Exception('No order increment id found for P24!');
        }

        $redirectDelay = 1000 * $this->helper('przelewy')->getRedirectDelay();

        /**
         * P24 payment model
         *
         * @var Divante_Przelewy_Model_Payment_Przelewy $przelewy
         */
        $przelewy = Mage::getSingleton('przelewy/payment_przelewy');

        $form = new Varien_Data_Form();

        $form->setAction($przelewy->getPaymentURI())
            ->setId('przelewy_przelewy_checkout')
            ->setName('przelewy_przelewy_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);

        $formFields = $przelewy->getRedirectionFormData($orderIncrementId);

        $urlToFix = [
            'p24_url_return',
            'p24_url_cancel',
        ];

        foreach ($urlToFix as $keyName) {
            if (array_key_exists($keyName, $formFields) && !empty($formFields[$keyName])) { // @codingStandardsIgnoreLine
                $url                  = parse_url($formFields[$keyName]);
                $host = $_SERVER["HTTP_REFERER"];

                if (empty($host)) {
                    $host = self::MEDI_MAGENTO_HOST;
                } else {
                    $host = parse_url($host, PHP_URL_HOST);
                }

                $formFields[$keyName] = $url['scheme'] . '://' .
                                        $host . $this->backUrls[$keyName];

                if (array_key_exists('query', $url) && !empty($url['query'])) {
                    $formFields[$keyName] .= $url['query'];
                }
            }
        }

        foreach ($formFields as $field => $value) {
            $form->addField(
                $field,
                'hidden',
                [
                    'name'  => $field,
                    'value' => $value,
                ]
            );
        }

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>';
        $html .= $this->__('You will be redirected to the Przelewy24 payment service website');
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">setTimeout(function(){	document.getElementById("przelewy_przelewy_checkout").submit();}, ' . $redirectDelay . ');</script>';
        $html .= '</body></html>';

        return $html;
    }
}