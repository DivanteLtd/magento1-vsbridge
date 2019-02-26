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
class Divante_VueStorefrontBridge_Cms_PageController extends Divante_VueStorefrontBridge_AbstractController
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
    protected $_cmsPageData = [
        'page_id',
        'title',
        'identifier',
        'content',
        'content_heading',
        'creation_time',
        'update_time',
        'meta_keywords',
        'meta_description',
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
                $pages  = $this->_getCmsPageCollection($params);
                $this->_result(200, $pages);
            }
        } catch (Exception $e) {
            $this->_result(500, $e->getMessage());
        }
    }

    /**
     * Take CMS pages collection with needed attributes
     *
     * @param $params
     * @return array
     */
    protected function _getCmsPageCollection($params)
    {
        $pages      = [];
        $helper     = $this->_getCmsHelper();
        $store      = Mage::app()->getDefaultStoreView();
        $collection = Mage::getModel('cms/page')
            ->getCollection()
            ->addFieldToSelect($this->_cmsPageData)
            ->addStoreFilter($store)
            ->addFieldToFilter('is_active', 1)
            ->setCurPage($params['page'])
            ->setPageSize($params['pageSize']);

        $i = 0;
        foreach ($collection as $page) {
            $id                    = $page->getId();
            $content               = $helper->getPageTemplateProcessor()->filter($page->getContent());
            $pages[$i]             = $page->getData();
            $pages[$i]['id']       = $id;
            $pages[$i]['content']  = $content;
            $pages[$i]['store_id'] = $store->getId();
            // Comment due to we don't use multi stores
            //$pages[$i]['store_id'] = $page->getResource()->lookupStoreIds($id);
            $i++;
        }

        return $pages;
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
