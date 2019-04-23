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
     * Get and process Dto from entity
     *
     * @param array $initialDto
     *
     * @return array
     */
    final public function filterDto($initialDto)
    {
        $dto = $this->customDtoFiltering($initialDto);

        foreach ($dto as $key => $value) {
            if (in_array($key, $this->getBlacklist())) {
                unset($dto[$key]);
            } else {
                // If beginning by "use", "has", ... , then must be a boolean (can be overriden using cast array)
                if (preg_match('#^(use|has|is|enable|disable)_.*$#', $key)) {
                    $dto[$key] = $dto[$key] != null ? boolval($value) : null;
                }

                // If ending by "id", then must be an integer (can be overriden using cast array)
                if (preg_match('#^.*_?id$#', $key)) {
                    $dto[$key] = $dto[$key] != null ? intval($value) : null;
                }
            }

            if (in_array($key, $this->getAttributesToCastInt())) {
                $dto[$key] = $dto[$key] != null ? intval($dto[$key]) : null;
            }

            if (in_array($key, $this->getAttributesToCastBool())) {
                $dto[$key] = $dto[$key] != null ? boolval($dto[$key]) : null;
            }

            if (in_array($key, $this->getAttributesToCastStr())) {
                $dto[$key] = $dto[$key] != null ? strval($dto[$key]) : null;
            }

            if (in_array($key, $this->getAttributesToCastFloat())) {
                $dto[$key] = $dto[$key] != null ? floatval($dto[$key]) : null;
            }
        }

        return $dto;
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
