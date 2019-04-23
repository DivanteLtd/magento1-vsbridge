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
     * @param Varien_Object $entity
     *
     * @return array
     */
    final public function toDto($entity)
    {
        $dto = $this->getDto($entity);
        $blacklist = $this->getBlacklist();
        $toIntList = $this->getAttributesToCastInt();

        foreach ($dto as $key => $value) {
            if ($blacklist && in_array($key, $blacklist)) {
                unset ($dto[$key]);
            } else {
                if (strstr($key, 'is_') || strstr($key, 'has_') || strstr($key, 'use_') || strstr($key, 'enable_')) {
                    $dto[$key] = boolval($value);
                }
            }

            if ($toIntList && in_array($key, $toIntList)) {
                $dto[$key] = $dto[$key] != null ? intval($dto[$key]) : null;
            }
        }

        return $dto;
    }

    /**
     * Get Dto from entity
     *
     * @param Varien_Object $entity
     *
     * @return array
     */
    abstract protected function getDto($entity);

    /**
     * Get Dto attribute blacklist
     *
     * @return array
     */
    abstract protected function getBlacklist();

    /**
     * Get attribute list to cast to integer
     *
     * @return array
     */
    abstract protected function getAttributesToCastInt();
}
