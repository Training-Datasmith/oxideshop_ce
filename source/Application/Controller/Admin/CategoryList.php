<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin category list manager.
 * Collects attributes base information (sorting, title, etc.), there is ability to
 * filter them by sorting, title or delete them.
 * Admin Menu: Manage Products -> Categories.
 */
class Category_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxcategory';
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_s_list_type = 'oxcategorylist';
    /**
     * Returns sorting fields array
     *
     * @return array
     */
    public function get_list_sorting()
    {
        $s_sort_parameter = Registry::get_request()->get_request_escaped_parameter('sort');
        if ($this->_a_curr_sorting === null && !$s_sort_parameter && $o_base_object = $this->get_item_list_base_object()) {
            $s_cat_view = $o_base_object->get_core_table_name();
            $this->_a_curr_sorting[$s_cat_view]['oxrootid'] = 'desc';
            $this->_a_curr_sorting[$s_cat_view]['oxleft'] = 'asc';
            return $this->_a_curr_sorting;
        }
        return parent::get_list_sorting();
    }
    /**
     * Loads category tree, passes data to template engine and returns name of
     * template file "category_list".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $i_lang = $o_lang->get_tpl_language();
        // parent category tree
        $o_cat_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
        $o_cat_tree->load_list();
        // add Root as fake category
        // rebuild list as we need the root entry at the first position
        $a_new_list = [];
        $o_root = new stdClass();
        $o_root->oxcategories__oxid = new \Oxid_Esales\Eshop\Core\Field(null, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $o_root->oxcategories__oxtitle = new \Oxid_Esales\Eshop\Core\Field($o_lang->translate_string('viewAll', $i_lang), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $a_new_list[] = $o_root;
        $o_root = new stdClass();
        $o_root->oxcategories__oxid = new \Oxid_Esales\Eshop\Core\Field('oxrootid', \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $o_root->oxcategories__oxtitle = new \Oxid_Esales\Eshop\Core\Field('-- ' . $o_lang->translate_string('mainCategory', $i_lang) . ' --', \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $a_new_list[] = $o_root;
        foreach ($o_cat_tree as $o_category) {
            $a_new_list[] = $o_category;
        }
        $o_cat_tree->assign($a_new_list);
        $a_filter = $this->get_list_filter();
        if (is_array($a_filter) && isset($a_filter['oxcategories']['oxparentid'])) {
            foreach ($o_cat_tree as $o_category) {
                if ($o_category->oxcategories__oxid->value == $a_filter['oxcategories']['oxparentid']) {
                    $o_category->selected = 1;
                    break;
                }
            }
        }
        $this->_a_view_data['cattree'] = $o_cat_tree;
        return 'category_list';
    }
}