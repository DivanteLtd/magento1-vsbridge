<?php
require_once('AbstractController.php');
function _prepareDTO($category) {
    $categoryDTO = $category->getData();
    $categoryDTO['id'] = intval($categoryDTO['entity_id']);
    unset($categoryDTO['entity_id']);
    unset($categoryDTO['path']);

    return $categoryDTO;
}

function _processCategory($category, $level = 0) {

    $childCats = $category->getChildrenCategories();
    $catDTO =  _prepareDTO($category);

    $catDTO['children_data'] = array();
    foreach ($childCats as $childCategory) { 
        $catDTO['children_data'][] = _processCategory($childCategory, $level +  1);
    }
    // $catDTO['level'] = $level;
    $catDTO['children_count'] = count($catDTO['children_data']);
    $catDTO = _filterDTO($catDTO);

    return $catDTO;
}

class Divante_VueStorefrontBridge_CategoriesController extends Divante_VueStorefrontBridge_AbstractController
{
    public function indexAction()
    {
        if ($this->_authorize($this->getRequest())) {

            $params = $this->_processParams($this->getRequest());
            $categories = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('*')->setPage($params['page'], $params['pageSize'])->load(); //$helper->getStoreCategories();

            $catList = array();
            foreach ($categories as $category) {
                $catList[] = _processCategory($category);
            }
            $this->_result(200, $catList);
        }
    }
}
?>