<?php

/**
 * Class Divante_VueStorefrontBridge_Model_Payment_Przelewy24
 *
 * @category  Divante
 * @package   VueStoreFrontBridge
 * @author    Dariusz Oliwa <doliwa@divante.pl>
 * @copyright 2018 Divante Sp. z o.o.
 * @license   See LICENSE_DIVANTE.txt for license details.
 * @link      n/a
 */
class Divante_VueStorefrontBridge_Model_Payment_Przelewy24
{

    const P24_CSS_URL = 'https://secure.przelewy24.pl/skrypty/ecommerce_plugin.css.php';
    const P24_FORM_BLOCK = 'dialcom_przelewy/form_przelewy';
    const P24_SELECT_METHOD_ACTION = 'vsbridge/przelewy/selectpaymentmethod';

    /**
     * Gets all available payment channels
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getChannels()
    {
        /**
         * Payment block
         *
         * @var Divante_Przelewy_Block_Form_Przelewy $paymentBlock
         */
        $paymentBlock     = Mage::getBlockSingleton(
            self::P24_FORM_BLOCK
        );

        $selectPaymentUrl = $paymentBlock->getUrl(
            self::P24_SELECT_METHOD_ACTION,
            [
                /*
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED =>
                    $paymentBlock->helper('core/url')->getEncodedUrl(),
                '_secure'                                                 =>
                    Mage::app()->getStore()->isCurrentlySecure(),
                */
                '_secure' => true
            ]
        );

        $paymentBlock->setAjaxPaymentUpdateUrl($selectPaymentUrl);

        $allPaymentChannels   = [];
        $methodNamesAndValues = $paymentBlock->getAvailableChannels(
            Mage::app()->getStore()->getCurrentCurrencyCode()
        );

        $przelewy24Css = $this->fetchPrzelewy24Css();

        $methodBankIconUrls = $this->findBankIcons(
            $przelewy24Css,
            $methodNamesAndValues
        );

        $cleanedItUp = [];

        foreach ($methodNamesAndValues as $bankId => $title) {
            $cleanedItUp[] = [
                'title'       => $title,
                'icon'        => $methodBankIconUrls['method_icon_url_desktop'][$bankId],
                'mobile_icon' => $methodBankIconUrls['method_icon_url_mobile'][$bankId],
                'bank_id'     => $bankId,
            ];
        }

        $allPaymentChannels['available_payment_channels'] = $cleanedItUp;
        $allPaymentChannels['select_payment_url']         = $selectPaymentUrl;

        return $allPaymentChannels;
    }

    /**
     * Fetches latest css content from Przelewy24
     *
     * @return mixed
     */
    protected function fetchPrzelewy24Css()
    {
        $przelewyStyles = '';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::P24_CSS_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $przelewyStyles = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            return $this->_result(500, $e->getMessage());
        }

        return $przelewyStyles;
    }

    /**
     * Finds bank icons
     *
     * @param string $rawCssContent css styles
     * @param array  $methods       array of available methods
     *
     * @return array
     */
    protected function findBankIcons(string $rawCssContent, array $methods)
    {
        $desktopIcons = [];
        $mobileIcons  = [];
        $bankIds      = array_keys($methods);

        foreach ($bankIds as $bankId) {
            if (preg_match(
                    '/.bank-logo-' . $bankId . ' { background-image: url\((.*?)\);/',
                    $rawCssContent,
                    $match
                ) == 1) {
                $desktopIcons[$bankId] = $match[1];
            }

            if (preg_match(
                    '/.mobile .bank-logo-' . $bankId . ' { background-image: url\((.*?)\);/',
                    $rawCssContent,
                    $match
                ) == 1) {
                $mobileIcons[$bankId] = $match[1];
            }
        }

        return [
            'method_icon_url_desktop' => $desktopIcons,
            'method_icon_url_mobile'  => $mobileIcons,
        ];
    }
}