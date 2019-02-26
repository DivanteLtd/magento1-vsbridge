<?php

/**
 * Class Divante_VueStorefrontBridge_Model_Config
 */
class Divante_VueStorefrontBridge_Model_Config
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
     * @return string
     */
    public function getSecretKey()
    {
        return trim(Mage::getStoreConfig(self::XML_CONFIG_JWT_SECRET));
    }

    /**
     * @return int
     */
    public function getMaxPageSize()
    {
        return (int)trim(Mage::getStoreConfig(self::XML_CONFIG_MAX_PAGE_SIZE));
    }
}
