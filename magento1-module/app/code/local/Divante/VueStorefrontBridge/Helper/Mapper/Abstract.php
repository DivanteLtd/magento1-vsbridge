<?php
/**
 * Divante VueStorefrontBridge AbstractMapper Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Mathias Arlaud <marlaud@sutunam.com>
 * @copyright   Copyright (C) 2019
 * @license     MIT License
 */
abstract class Divante_VueStorefrontBridge_Helper_Mapper_Abstract extends Mage_Core_Helper_Abstract
{
    /**
     * Name to address custom mappers via config.xml
     * @var string $_mapperIdentifier
     */
    protected $_mapperIdentifier = '';

    /**
     * Get and process Dto from entity
     *
     * @param array $initialDto
     * @param bool $callMapperExtensions
     *
     * @return array
     */
    final public function filterDto($initialDto, $callMapperExtensions = true)
    {
        $dto = $this->customDtoFiltering($initialDto);

        foreach ($dto as $key => $value) {
            $isCustom = false;

            if (in_array($key, $this->getBlacklist())) {
                unset($dto[$key]);
                continue;
            }

            if (in_array($key, $this->getAttributesToCastInt())) {
                $dto[$key] = $dto[$key] != null ? intval($dto[$key]) : null;
                $isCustom = true;
            }

            if (in_array($key, $this->getAttributesToCastBool())) {
                $dto[$key] = $dto[$key] != null ? boolval($dto[$key]) : null;
                $isCustom = true;
            }

            if (in_array($key, $this->getAttributesToCastStr())) {
                $dto[$key] = $dto[$key] != null ? strval($dto[$key]) : null;
                $isCustom = true;
            }

            if (in_array($key, $this->getAttributesToCastFloat())) {
                $dto[$key] = $dto[$key] != null ? floatval($dto[$key]) : null;
                $isCustom = true;
            }

            if (!$isCustom) {
                // If beginning by "use", "has", ... , then must be a boolean (can be overriden using cast array)
                if (preg_match('#^(use|has|is|enable|disable)_.*$#', $key)) {
                    $dto[$key] = $dto[$key] != null ? boolval($value) : null;
                }

                // If ending by "id", then must be an integer (can be overriden using cast array)
                if (preg_match('#^.*_?id$#', $key)) {
                    $dto[$key] = $dto[$key] != null ? intval($value) : null;
                }
            }
        }

        if ($callMapperExtensions) {
            $this->callMapperExtensions($dto);
        }

        return $dto;
    }

    /**
     * Allow to extend the mappers using config.xml
     *
     * @param array $dto
     * @return array
     */
    protected function callMapperExtensions(&$dto)
    {
        /** @var Divante_VueStorefrontBridge_Model_Config_Mapper $model */
        $model = Mage::getModel('vsbridge/config_mapper');
        return $model->applyExtendedMapping($this->_mapperIdentifier, $dto);
    }

    /**
     * Apply custom dto filtering
     *
     * @param array $dto
     *
     * @return array
     */
    protected function customDtoFiltering($dto)
    {
        return $dto;
    }

    /**
     * Get Dto attribute blacklist
     *
     * @return array
     */
    protected function getBlacklist()
    {
        return [];
    }

    /**
     * Get attribute list to cast to integer
     *
     * @return array
     */
    protected function getAttributesToCastInt()
    {
        return [];
    }

    /**
     * Get attribute list to cast to boolean
     *
     * @return array
     */
    protected function getAttributesToCastBool()
    {
        return [];
    }

    /**
     * Get attribute list to cast to string
     *
     * @return array
     */
    protected function getAttributesToCastStr()
    {
        return [];
    }

    /**
     * Get attribute list to cast to float
     *
     * @return array
     */
    protected function getAttributesToCastFloat()
    {
        return [];
    }
}
