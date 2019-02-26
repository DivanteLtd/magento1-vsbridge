<?php
require_once(Mage::getModuleDir('controllers', 'Divante_VueStorefrontBridge') . DS . 'AbstractController.php');

/**
 * Divante VueStorefrontBridge Cms_HierarchyController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Vladimir Plastovets Phoenix BE - VP
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Cms_HierarchyController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * Fields for import to Elasticserach
     *
     * @var array
     */
    protected $_cmsHierarchyData = [
        'node_id',
        'parent_node_id',
        'page_id',
        'identifier',
        'label',
        'level',
        'request_url',
        'xpath',
        'scope_id'
    ];

    /**
     * Index Action
     */
    public function indexAction()
    {
        try {
            if ($this->_authorizeAdminUser($this->getRequest())) {
                $params    = $this->_processParams($this->getRequest());
                $hierarchy = $this->_getCmsHierarchyCollection($params);
                $this->_result(200, $hierarchy);
            }
        } catch (Exception $e) {
            $this->_result(500, $e->getMessage());
        }
    }

    /**
     * Take CMS hierarchy collection with needed attributes
     *
     * @param $params
     * @return array
     */
    protected function _getCmsHierarchyCollection($params)
    {
        $hierarchy = [];
        if (!$this->_isMageEnterprise()) {
            Mage::throwException('Sorry, to get CMS hierarchy data you need to use Magento Enterprise edition.');
        }
        $collection = Mage::getModel('enterprise_cms/hierarchy_node')
            ->getCollection()
            ->addFieldToSelect($this->_cmsHierarchyData)
            ->setCurPage($params['page'])
            ->setPageSize($params['pageSize']);

        $i = 0;
        foreach ($collection as $node) {
            $id                        = $node->getId();
            $hierarchy[$i]             = $node->getData();
            $hierarchy[$i]['id']       = $id;
            $hierarchy[$i]['xpath']    = (string)$node->getXpath();
            $hierarchy[$i]['store_id'] = $node->getScopeId();
            unset($hierarchy[$i]['scope_id']);
            $i++;
        }

        return $hierarchy;
    }
}
