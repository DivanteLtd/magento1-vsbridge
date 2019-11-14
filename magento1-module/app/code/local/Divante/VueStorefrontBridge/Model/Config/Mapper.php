<?php

/**
 * Class Divante_VueStorefrontBridge_Model_Config_Mapper
 */
class Divante_VueStorefrontBridge_Model_Config_Mapper
{
    /**
     * Mapper xml config path
     */
    const MAPPER_CONFIG_ROOT_NODE  = 'global/vsf_bridge/mapper/types';

    /**
     * @var array
     */
    protected $config = array();

    /**
     * @return array
     */
    public function __construct()
    {
        $mappingConfigTypes = Mage::getConfig()->getNode(self::MAPPER_CONFIG_ROOT_NODE)->asArray();
        foreach ($mappingConfigTypes as $mappingIdentifier => $mapper) {
            $this->config[$mappingIdentifier] =  $mapper['mapper'];
        }

        return $this->config;
    }

    /**
     * @param string $type
     * @param array $initialDto
     * @return array
     */
    public function applyExtendedMapping($type, &$initialDto)
    {
        if ($this->config[$type] && !empty($this->config[$type])) {
            foreach ($this->config[$type] as $name => $className) {
                $model = Mage::getModel($className);
                if (!$model || !$model instanceof Divante_VueStorefrontBridge_Helper_Mapper_Abstract) {
                    $error = 'Class "%s" is not an instance on "Divante_VueStorefrontBridge_Helper_Mapper_Abstract".';
                    Mage::log(sprintf($error, $className), 'system.log', true);
                    continue;
                }

                $initialDto = $model->filterDto($initialDto, false);
            }
        }

        return $initialDto;
    }
}
