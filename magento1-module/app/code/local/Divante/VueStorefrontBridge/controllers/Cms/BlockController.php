<?php
require_once(Mage::getModuleDir('controllers', 'Divante_VueStorefrontBridge') . DS . 'AbstractController.php');

/**
 * Divante VueStorefrontBridge Cms_PageController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Vladimir Plastovets Phoenix BE - VP
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_Cms_BlockController extends Divante_VueStorefrontBridge_AbstractController
{
    /**
     * @var Mage_Cms_Helper_Data
     */
    protected $_cmsHelper;

    /**
     * Fields for import to Elasticserach
     *
     * @var array
     */
    protected $_cmsBlockData = [
        'block_id',
        'title',
        'identifier',
        'content',
        'creation_time',
        'is_active'
    ];

    /**
     * Index Action
     */
    public function indexAction()
    {
        try {
            if ($this->_authorizeAdminUser($this->getRequest())) {
                $params = $this->_processParams($this->getRequest());
                $blocks = $this->_getCmsBlockCollection($params);
                $this->_result(200, $blocks);
            }
        } catch (Exception $e) {
            $this->_result(500, $e->getMessage());
        }

    }

    /**
     * Take CMS blocks collection with needed attributes
     *
     * @param $params
     * @return array
     */
    protected function _getCmsBlockCollection($params)
    {
        $blocks     = [];
        $helper     = $this->_getCmsHelper();
        $store      = Mage::app()->getDefaultStoreView();
        $collection = Mage::getModel('cms/block')
            ->getCollection()
            ->addFieldToSelect($this->_cmsBlockData)
            ->addStoreFilter($store)
            ->addFieldToFilter('is_active', 1)
            ->setCurPage($params['page'])
            ->setPageSize($params['pageSize']);

        $i = 0;
        foreach ($collection as $block) {
            $id                     = $block->getId();
            $content                = $helper->getPageTemplateProcessor()->filter($block->getContent());
            $blocks[$i]             = $block->getData();
            $blocks[$i]['id']       = $id;
            $blocks[$i]['content']  = $content;
            $blocks[$i]['store_id'] = $store->getId();
            // Comment due to we don't use multi stores
            //blocks[$i]['store_id'] = $block->getResource()->lookupStoreIds($id);
            $i++;
        }

        return $blocks;
    }

    /**
     * @return Mage_Cms_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function _getCmsHelper()
    {
        if (!is_object($this->_cmsHelper)) {
            $this->_cmsHelper = Mage::helper('cms');
        }

        return $this->_cmsHelper;
    }
}
