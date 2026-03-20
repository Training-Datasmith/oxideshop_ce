<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Class for updating category tree structure in DB.
 */
class Category_Update extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'category_update';
    /**
     * Category list object
     *
     * @var \OxidEsales\Eshop\Application\Model\CategoryList
     */
    protected $_o_cat_list;
    /**
     * Returns category list object
     *
     * @return \OxidEsales\Eshop\Application\Model\CategoryList
     */
    protected function get_category_list()
    {
        if ($this->_o_cat_list == null) {
            $this->_o_cat_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
            $this->_o_cat_list->update_category_tree(false);
        }
        return $this->_o_cat_list;
    }
    /**
     * Returns category list object
     *
     * @return array
     */
    public function get_cat_list_update_info()
    {
        return $this->get_category_list()->get_update_info();
    }
}