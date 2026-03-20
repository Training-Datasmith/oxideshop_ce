<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
/**
 * Transparent category manager class (executed automatically).
 *
 * @subpackage oxcmp
 */
class Categories_Component extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * More category object.
     *
     * @var object
     */
    protected $_o_more_cat;
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_bl_is_component = true;
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_o_category_tree;
    /**
     * Marking object as component
     *
     * @var \OxidEsales\Eshop\Application\Model\ManufacturerList
     */
    protected $_o_manufacturer_tree;
    /**
     * Executes parent::init(), searches for active category in URL,
     * session, post variables ("cnid", "cdefnid"), active article
     * ("anid", usually article details), then loads article and
     * category if any of them available. Generates category/navigation
     * list.
     */
    public function init(): void
    {
        parent::init();
        // Performance
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($my_config->get_config_param('blDisableNavBars') && $my_config->get_top_active_view()->get_is_order_step()) {
            return;
        }
        $s_act_cat = $this->get_act_cat();
        if ($my_config->get_config_param('bl_perfLoadManufacturerTree')) {
            // building Manufacturer tree
            $s_act_manufacturer = Registry::get_request()->get_request_escaped_parameter('mnid');
            $this->load_manufacturer_tree($s_act_manufacturer);
        }
        // building category tree for all purposes (nav, search and simple category trees)
        $this->load_category_tree($s_act_cat);
    }
    /**
     * get active article
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_product()
    {
        if ($s_act_product = Registry::get_request()->get_request_escaped_parameter('anid')) {
            $o_parent_view = $this->get_parent();
            if ($o_product = $o_parent_view->get_view_product()) {
                return $o_product;
            }
            $o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($o_product->load($s_act_product)) {
                // storing for reuse
                $o_parent_view->set_view_product($o_product);
                return $o_product;
            }
        }
    }
    /**
     * get active category id
     *
     * @return string
     */
    protected function get_act_cat()
    {
        $s_act_manufacturer = Registry::get_request()->get_request_escaped_parameter('mnid');
        $s_act_cat = $s_act_manufacturer ? null : Registry::get_request()->get_request_escaped_parameter('cnid');
        // loaded article - then checking additional parameters
        $o_product = $this->get_product();
        if ($o_product) {
            $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            $s_act_manufacturer = $my_config->get_config_param('bl_perfLoadManufacturerTree') ? $s_act_manufacturer : null;
            $s_act_vendor = Str::get_str()->preg_match('/^v_.?/i', $s_act_cat) ? $s_act_cat : null;
            $s_act_cat = $this->add_additional_params($o_product, $s_act_cat, $s_act_manufacturer, $s_act_vendor);
        }
        // Checking for the default category
        if ($s_act_cat === null && !$o_product && !$s_act_manufacturer) {
            // set remote cat
            $s_act_cat = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_shop()->oxshops__oxdefcat->value;
            if ($s_act_cat == 'oxrootid') {
                // means none selected
                $s_act_cat = null;
            }
        }
        return $s_act_cat;
    }
    /**
     * Category tree loader
     *
     * @param string $sActCat active category id
     */
    protected function load_category_tree($s_act_cat)
    {
        /** @var \OxidEsales\Eshop\Application\Model\CategoryList $oCategoryTree */
        $o_category_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
        $o_category_tree->build_tree($s_act_cat);
        $o_parent_view = $this->get_parent();
        // setting active category tree
        $o_parent_view->set_category_tree($o_category_tree);
        $this->set_category_tree($o_category_tree);
        // setting active category
        $o_parent_view->set_active_category($o_category_tree->get_click_cat());
    }
    /**
     * Manufacturer tree loader
     *
     * @param string $sActManufacturer active Manufacturer id
     */
    protected function load_manufacturer_tree($s_act_manufacturer)
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($my_config->get_config_param('bl_perfLoadManufacturerTree')) {
            $o_manufacturer_tree = $this->get_manufacturer_list();
            $shop_home_url = $my_config->get_shop_home_url();
            $o_manufacturer_tree->build_manufacturer_tree('manufacturerlist', $s_act_manufacturer, $shop_home_url);
            $o_parent_view = $this->get_parent();
            // setting active Manufacturer list
            $o_parent_view->set_manufacturer_tree($o_manufacturer_tree);
            $this->set_manufacturer_tree($o_manufacturer_tree);
            // setting active Manufacturer
            if ($o_manufacturer = $o_manufacturer_tree->get_click_manufacturer()) {
                $o_parent_view->set_act_manufacturer($o_manufacturer);
            }
        }
    }
    /**
     * Executes parent::render(), loads expanded/clicked category object,
     * adds parameters template engine and returns list of category tree.
     *
     * @return \OxidEsales\Eshop\Application\Model\CategoryList
     */
    public function render()
    {
        parent::render();
        // Performance
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $o_parent_view = $this->get_parent();
        if ($my_config->get_config_param('bl_perfLoadManufacturerTree') && $this->_o_manufacturer_tree) {
            $o_parent_view->set_manufacturerlist($this->_o_manufacturer_tree);
            $o_parent_view->set_root_manufacturer($this->_o_manufacturer_tree->get_root_cat());
        }
        if ($this->_o_category_tree) {
            return $this->_o_category_tree;
        }
    }
    /**
     * Adds additional parameters: active category, list type and category id
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oProduct         loaded product
     * @param string                                      $sActCat          active category id
     * @param string                                      $sActManufacturer active manufacturer id
     * @param string                                      $sActVendor       active vendor
     *
     * @return string $sActCat
     */
    protected function add_additional_params($o_product, $s_act_cat, $s_act_manufacturer, $s_act_vendor)
    {
        $s_search_par = Registry::get_request()->get_request_escaped_parameter('searchparam');
        $s_search_cat = Registry::get_request()->get_request_escaped_parameter('searchcnid');
        $s_search_vnd = Registry::get_request()->get_request_escaped_parameter('searchvendor');
        $s_search_man = Registry::get_request()->get_request_escaped_parameter('searchmanufacturer');
        $s_list_type = Registry::get_request()->get_request_escaped_parameter('listtype');
        // search ?
        if ((!$s_list_type || $s_list_type == 'search') && ($s_search_par || $s_search_cat || $s_search_vnd || $s_search_man)) {
            // setting list type directly
            $s_list_type = 'search';
        } else if ($s_act_manufacturer && $s_act_manufacturer == $o_product->get_manufacturer_id()) {
            // setting list type directly
            $s_list_type = 'manufacturer';
            $s_act_cat = $s_act_manufacturer;
        } elseif ($s_act_vendor && substr($s_act_vendor, 2) == $o_product->get_vendor_id()) {
            // such vendor is available ?
            $s_list_type = 'vendor';
            $s_act_cat = $s_act_vendor;
        } elseif ($s_act_cat && $o_product->is_assigned_to_category($s_act_cat)) {
            // category ?
        } else {
            [$s_list_type, $s_act_cat] = $this->get_default_params($o_product);
        }
        $o_parent_view = $this->get_parent();
        //set list type and category id
        $o_parent_view->set_list_type($s_list_type);
        $o_parent_view->set_category_id($s_act_cat);
        return $s_act_cat;
    }
    /**
     * Returns array containing default list type and category (or manufacturer ir vendor) id
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oProduct current product object
     *
     * @return array
     */
    protected function get_default_params($o_product)
    {
        $s_list_type = null;
        $a_article_cats = $o_product->get_category_ids(true);
        if (is_array($a_article_cats) && count($a_article_cats)) {
            $s_act_cat = reset($a_article_cats);
        } elseif ($s_act_cat = $o_product->get_manufacturer_id()) {
            // not assigned to any category ? maybe it is assigned to Manufacturer ?
            $s_list_type = 'manufacturer';
        } elseif ($s_act_cat = $o_product->get_vendor_id()) {
            // not assigned to any category ? maybe it is assigned to vendor ?
            $s_list_type = 'vendor';
        } else {
            $s_act_cat = null;
        }
        return [$s_list_type, $s_act_cat];
    }
    /**
     * Setter of category tree
     *
     * @param \OxidEsales\Eshop\Application\Model\CategoryList $oCategoryTree category list
     */
    public function set_category_tree($o_category_tree): void
    {
        $this->_o_category_tree = $o_category_tree;
    }
    /**
     * Setter of manufacturer tree
     *
     * @param \OxidEsales\Eshop\Application\Model\ManufacturerList $oManufacturerTree manufacturer list
     */
    public function set_manufacturer_tree($o_manufacturer_tree): void
    {
        $this->_o_manufacturer_tree = $o_manufacturer_tree;
    }
    /**
     * @return \OxidEsales\Eshop\Application\Model\ManufacturerList
     */
    protected function get_manufacturer_list()
    {
        return ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer_List::class);
    }
}