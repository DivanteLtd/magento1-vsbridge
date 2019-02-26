<?php
require_once('AbstractController.php');

/**
 * Divante VueStorefrontBridge CategoriesController Class
 *
 * @category    Divante
 * @package     VueStorefrontBridge
 * @author      Piotr Karwatka <pkarwatka@divante.co>
 * @author      Dariusz Oliwa <doliwa@divante.co>
 * @copyright   Copyright (C) 2018
 * @license     MIT License
 */
class Divante_VueStorefrontBridge_CategoriesController extends Divante_VueStorefrontBridge_AbstractController
{

    /**
     * index action
     */
    public function indexAction()
    {
        if ($this->_authorizeAdminUser($this->getRequest())) {
            $params     = $this->_processParams($this->getRequest());
            $categories = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('*')->setPage(
                $params['page'],
                $params['pageSize']
            )->load(); //$helper->getStoreCategories();

            $catList = [];

            foreach ($categories as $category) {
                $catList[] = $this->_processCategory($category);
            }

            $this->_result(200, $catList);
        }
    }

    /**
     * Prepares category entity data
     *
     * @param Mage_Catalog_Model_Category $category
     *
     * @return mixed
     */
    protected function _prepareDTO(Mage_Catalog_Model_Category $category)
    {
        $categoryDTO       = $category->getData();
        $categoryDTO['id'] = intval($categoryDTO['entity_id']);
        unset($categoryDTO['entity_id']);
        unset($categoryDTO['path']);

        return $categoryDTO;
    }

    /**
     * Processes category data
     *
     * @param Mage_Catalog_Model_Category $category
     * @param int                         $level
     *
     * @return mixed
     */
    protected function _processCategory(Mage_Catalog_Model_Category $category, $level = 0)
    {
        $childCats               = $category->getChildrenCategories();
        $catDTO                  = $this->_prepareDTO($category);
        $catDTO['children_data'] = [];
        foreach ($childCats as $childCategory) {
            $catDTO['children_data'][] = $this->_processCategory($childCategory, $level + 1);
        }
        // $catDTO['level'] = $level;
        $catDTO['children_count'] = count($catDTO['children_data']);
        $catDTO                   = $this->_filterDTO($catDTO);

        return $catDTO;
    }
}
