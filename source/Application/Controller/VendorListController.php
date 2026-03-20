<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * List of articles for a selected vendor.
 * Collects list of articles, according to it generates links for list gallery,
 * meta tags (for search engines). Result - "vendorlist" template.
 * OXID eShop -> (Any selected shop product category).
 */
class Vendor_List_Controller extends \Oxid_Esales\Eshop\Application\Controller\Article_List_Controller
{
    /**
     * List type
     *
     * @var string
     */
    protected $_s_list_type = 'vendor';
    /**
     * List type
     *
     * @var string
     */
    protected $_bl_visible_sub_cats;
    /**
     * List type
     *
     * @var string
     */
    protected $_o_sub_cat_list;
    /**
     * Template location
     *
     * @var string
     */
    protected $_s_tpl_location;
    /**
     * Template location
     *
     * @var string
     */
    protected $_s_cat_title;
    /**
     * Page navigation
     *
     * @var object
     */
    protected $_o_page_navigation;
    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_bl_show_sorting = true;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_INDEX;
    /**
     * Vendor list object.
     *
     * @var object
     */
    protected $_o_vendor_tree;
    /**
     * Executes parent::render(), loads active vendor, prepares article
     * list sorting rules. Loads list of articles which belong to this vendor
     * Generates page navigation data
     * such as previous/next window URL, number of available pages, generates
     * meta tags info (\OxidEsales\Eshop\Application\Controller\FrontendController::_convertForMetaTags()) and returns
     * name of template to render.
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::render();
        // load vendor
        if ($this->get_vendor_id_from_request() && $this->get_vendor_tree()) {
            if ($o_vendor = $this->get_act_vendor()) {
                if ($o_vendor->get_id() != 'root') {
                    // load the articles
                    $this->get_article_list();
                    // checking if requested page is correct
                    $this->check_requested_page();
                    // processing list articles
                    $this->process_list_articles();
                }
            }
        }
        return $this->_s_this_template;
    }
    /**
     * Returns product link type (OXARTICLE_LINKTYPE_VENDOR)
     *
     * @return int
     */
    protected function get_product_link_type()
    {
        return OXARTICLE_LINKTYPE_VENDOR;
    }
    /**
     * Loads and returns article list of active vendor.
     *
     * @param object $oVendor vendor object
     *
     * @return array
     */
    protected function load_articles($o_vendor)
    {
        $s_vendor_id = $o_vendor->get_id();
        // load only articles which we show on screen
        $i_nr_of_cat_articles = (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
        $i_nr_of_cat_articles = $i_nr_of_cat_articles ?: 1;
        $o_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_art_list->set_sql_limit($i_nr_of_cat_articles * $this->get_request_page_nr(), $i_nr_of_cat_articles);
        $o_art_list->set_custom_sorting($this->get_sorting_sql($this->get_sort_ident()));
        // load the articles
        $this->_i_all_art_cnt = $o_art_list->load_vendor_articles($s_vendor_id, $o_vendor);
        // counting pages
        $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $i_nr_of_cat_articles);
        return [$o_art_list, $this->_i_all_art_cnt];
    }
    /**
     * Returns active product id to load its seo meta info
     *
     * @return string
     */
    protected function get_seo_object_id()
    {
        if ($o_vendor = $this->get_act_vendor()) {
            return $o_vendor->get_id();
        }
    }
    /**
     * Modifies url by adding page parameters. When seo is on, url is additionally
     * formatted by SEO engine
     *
     * @param string $sUrl  current url
     * @param int    $iPage page number
     * @param int    $iLang active language id
     *
     * @return string
     */
    protected function add_page_nr_param($s_url, $i_page, $i_lang = null)
    {
        if (\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active() && $o_vendor = $this->get_act_vendor()) {
            if ($i_page) {
                // only if page number > 0
                $s_url = $o_vendor->get_base_seo_link($i_lang, $i_page);
            }
        } else {
            $s_url = \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::add_page_nr_param($s_url, $i_page, $i_lang);
        }
        return $s_url;
    }
    /**
     * Returns current view Url
     *
     * @return string
     */
    public function generate_page_navigation_url()
    {
        if (\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active() && $o_vendor = $this->get_act_vendor()) {
            return $o_vendor->get_link();
        }
        return parent::generate_page_navigation_url();
    }
    /**
     * Returns if vendor has visible sub-cats and load them.
     *
     * @return bool
     */
    public function has_visible_sub_cats()
    {
        if ($this->_bl_visible_sub_cats === null) {
            $this->_bl_visible_sub_cats = false;
            if ($this->get_vendor_id_from_request() && $o_vendor_tree = $this->get_vendor_tree()) {
                if ($o_vendor = $this->get_act_vendor()) {
                    if ($o_vendor->get_id() == 'root') {
                        $this->_bl_visible_sub_cats = $o_vendor_tree->count();
                        $this->_o_sub_cat_list = $o_vendor_tree;
                    }
                }
            }
        }
        return $this->_bl_visible_sub_cats;
    }
    /**
     * Returns vendor subcategories
     *
     * @return array
     */
    public function get_sub_cat_list()
    {
        if ($this->_o_sub_cat_list === null) {
            $this->_o_sub_cat_list = [];
            if ($this->has_visible_sub_cats()) {
                return $this->_o_sub_cat_list;
            }
        }
        return $this->_o_sub_cat_list;
    }
    /**
     * Get vendor article list
     *
     * @return array
     */
    public function get_article_list()
    {
        if ($this->_a_article_list === null) {
            $this->_a_article_list = [];
            if (($o_vendor = $this->get_act_vendor()) && $o_vendor->get_id() != 'root') {
                [$a_article_list, $i_all_art_cnt] = $this->load_articles($o_vendor);
                if ($i_all_art_cnt) {
                    $this->_a_article_list = $a_article_list;
                }
            }
        }
        return $this->_a_article_list;
    }
    /**
     * Return vendor title
     *
     * @return string
     */
    public function get_title()
    {
        if ($this->_s_cat_title === null) {
            $this->_s_cat_title = '';
            if ($o_vendor = $this->get_act_vendor()) {
                $this->_s_cat_title = $o_vendor->oxvendor__oxtitle->value;
            }
        }
        return $this->_s_cat_title;
    }
    /**
     * Template variable getter. Returns category path array
     *
     * @return array
     */
    public function get_tree_path()
    {
        if ($this->get_vendor_id_from_request() && $o_vendor_tree = $this->get_vendor_tree()) {
            return $o_vendor_tree->get_path();
        }
    }
    /**
     * Returns request parameter of vendor id.
     *
     * @return string
     */
    protected function get_vendor_id_from_request()
    {
        return Registry::get_request()->get_request_escaped_parameter('cnid');
    }
    /**
     * Template variable getter. Returns active vendor
     *
     * @return object
     */
    public function get_active_category()
    {
        if ($this->_o_act_category === null) {
            $this->_o_act_category = false;
            if ($this->get_vendor_id_from_request() && $o_vendor_tree = $this->get_vendor_tree()) {
                if ($o_vendor = $this->get_act_vendor()) {
                    $this->_o_act_category = $o_vendor;
                }
            }
        }
        return $this->_o_act_category;
    }
    /**
     * Template variable getter. Returns template location
     *
     * @return string
     */
    public function get_cat_tree_path()
    {
        if ($this->_s_cat_tree_path === null) {
            $this->_s_cat_tree_path = false;
            if ($o_vendor_tree = $this->get_vendor_tree()) {
                $this->_s_cat_tree_path = $o_vendor_tree->get_path();
            }
        }
        return $this->_s_cat_tree_path;
    }
    /**
     * Returns title suffix used in template
     *
     * @return string
     */
    public function get_title_suffix()
    {
        if ($this->get_act_vendor()->get_field_data('oxshowsuffix')) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_shop()->get_field_data('oxtitlesuffix');
        }
    }
    /**
     * Returns current view keywords separated by comma
     * (calls parent::collectMetaKeyword())
     *
     * @param string $sKeywords               data to use as keywords
     * @param bool   $blRemoveDuplicatedWords remove duplicated words
     *
     * @return string
     */
    protected function prepare_meta_keyword($s_keywords, $bl_remove_duplicated_words = true)
    {
        return parent::collect_meta_keyword($s_keywords);
    }
    /**
     * Returns current view meta description data
     * (calls parent::collectMetaDescription())
     *
     * @param string $sMeta     category path
     * @param int    $iLength   max length of result, -1 for no truncation
     * @param bool   $blDescTag if true - performs additional duplicate cleaning
     *
     * @return string
     */
    protected function prepare_meta_description($s_meta, $i_length = 1024, $bl_desc_tag = false)
    {
        return parent::collect_meta_description($s_meta, $i_length, $bl_desc_tag);
    }
    /**
     * returns object, associated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $iLang language id
     *
     * @return object
     */
    protected function get_subject($i_lang)
    {
        return $this->get_act_vendor();
    }
    /**
     * Returns additional URL parameters which must be added to list products dynamic urls
     *
     * @return string
     */
    public function get_add_url_params()
    {
        $s_add_params = parent::get_add_url_params();
        $s_add_params .= ($s_add_params ? '&amp;' : '') . "listtype={$this->_s_list_type}";
        if ($o_vendor = $this->get_act_vendor()) {
            $s_add_params .= '&amp;cnid=v_' . $o_vendor->get_id();
        }
        return $s_add_params;
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $a_paths = [];
        $o_cat_tree = $this->get_vendor_tree();
        if ($o_cat_tree) {
            foreach ($o_cat_tree->get_path() as $o_cat) {
                $a_cat_path = [];
                $a_cat_path['link'] = $o_cat->get_link();
                $a_cat_path['title'] = $o_cat->oxcategories__oxtitle->value;
                $a_paths[] = $a_cat_path;
            }
        }
        return $a_paths;
    }
    /**
     * Returns vendor tree
     *
     * @return \OxidEsales\Eshop\Application\Model\VendorList
     */
    public function get_vendor_tree()
    {
        if ($this->get_vendor_id_from_request() && $this->_o_vendor_tree === null) {
            /** @var \OxidEsales\Eshop\Application\Model\VendorList $oVendorTree */
            $o_vendor_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor_List::class);
            $o_vendor_tree->build_vendor_tree('vendorlist', $this->get_act_vendor()->get_id(), \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_home_url());
            $this->_o_vendor_tree = $o_vendor_tree;
        }
        return $this->_o_vendor_tree;
    }
    /**
     * Vendor tree setter
     *
     * @param \OxidEsales\Eshop\Application\Model\VendorList $oVendorTree vendor tree
     */
    public function set_vendor_tree($o_vendor_tree): void
    {
        $this->_o_vendor_tree = $o_vendor_tree;
    }
    /**
     * Template variable getter. Returns array of attribute values
     * we do have here in this category
     *
     * @return array
     */
    public function get_attributes()
    {
        return null;
    }
}