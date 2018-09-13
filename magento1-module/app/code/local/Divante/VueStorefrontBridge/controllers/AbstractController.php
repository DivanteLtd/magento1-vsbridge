<?php

require_once(__DIR__ . '/../helpers/JWT.php');

/**
 * Divante VueStorefrontBridge AbstractController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Piotr Karwatka <pkarwatka@divante.co>
 * @author      Björn Kraus PhoenixPM - BK
 * @author      Dariusz Oliwa <doliwa@divante.co>
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_AbstractController extends Mage_Core_Controller_Front_Action
{

    /**
     * JWT secret passphrase
     */
    const XML_CONFIG_JWT_SECRET = 'vsbridge/general/jwt_secret';
    /**
     * Maximum page size
     */
    const XML_CONFIG_MAX_PAGE_SIZE = 'vsbridge/general/max_page_size';

    /**
     * Sets response header content type to json
     */
    public function init()
    {
        $this->getResponse()->setHeader('Content-Type', 'application/json');
    }

    /**
     * Checks authorization token
     *
     * @param Mage_Core_Controller_Request_Http $request
     *
     * @return bool
     */
    protected function _authorize(Mage_Core_Controller_Request_Http $request)
    {
        $apikey    = $request->getParam('apikey');
        $secretKey = trim(Mage::getStoreConfig(self::XML_CONFIG_JWT_SECRET));

        try {
            $tokenData = JWT::decode($apikey, $secretKey, 'HS256');
            if ($tokenData->id > 0) {
                return true;
            } else {
                $this->_result(401, 'Unauthorized request');

                return false;
            }
        } catch (Exception $err) {
            $this->_result(500, $err->getMessage());

            return false;
        }

        return false;
    }

    /**
     * Processes parameters
     *
     * @param Mage_Core_Controller_Request_Http $request
     *
     * @return array
     */
    protected function _processParams(Mage_Core_Controller_Request_Http $request)
    {
        $paramsDTO             = [];
        $paramsDTO['page']     = max(abs(intval($request->getParam('page'))), 1);
        $paramsDTO['pageSize'] = min(
            abs(intval($request->getParam('pageSize'))),
            intval(trim(Mage::getStoreConfig(self::XML_CONFIG_MAX_PAGE_SIZE)))
        );
        if ($typeId = $request->getParam('type_id')) {
            $paramsDTO['type_id'] = $typeId;
        }

        return $paramsDTO;
    }

    /**
     * Filters parameters map removing blacklisted
     *
     * @param array      $dtoToFilter
     * @param array|null $blackList
     *
     * @return mixed
     */
    protected function _filterDTO(array $dtoToFilter, array $blackList = null)
    {
        foreach ($dtoToFilter as $key => $val) {
            if ($blackList && in_array($key, $blackList)) {
                unset ($dtoToFilter[$key]);
            } else {
                if (strstr($key, 'is_') || strstr($key, 'has_')) {
                    $dtoToFilter[$key] = boolval($val);
                }
            }
        }

        return $dtoToFilter;
    }

    /**
     * Sends back code and result of performed operation
     *
     * @param $code
     * @param $result
     */
    protected function _result($code, $result)
    {
        $this->getResponse()->setBody(
            json_encode(
                [
                    'code'   => $code,
                    'result' => $result,
                ],
                JSON_NUMERIC_CHECK
            )
        )->setHttpResponseCode($code)->setHeader('Content-Type', 'application/json');
    }
}