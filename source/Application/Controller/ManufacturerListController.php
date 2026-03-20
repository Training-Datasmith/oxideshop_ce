<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * List of articles for a selected Manufacturer.
 * Collects list of articles, according to it generates links for list gallery,
 * metatags (for search engines). Result - "manufacturerlist" template.
 * OXID eShop -> (Any selected shop product category).
 */
class Manufacturer_List_Controller extends \Oxid_Esales\Eshop\Application\Controller\Article_List_Controller
{
    /**
     * List type
     *
     * @var string
     */
    protected $_s_list_type = 'manufacturer';
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
     * Recommlist
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_o_recomm_list;
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
     * Executes parent::render(), loads active Manufacturer, prepares article
     * list sorting rules. Loads list of articles which belong to this Manufacturer
     * Generates page navigation data
     * such as previous/next window URL, number of available pages, generates
     * metatags info (\OxidEsales\Eshop\Application\Controller\FrontendController::_convertForMetaTags()) and returns
     * name of template to render.
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::render();
        // load Manufacturer
        if ($this->get_manufacturer_tree()) {
            if ($o_manufacturer = $this->get_act_manufacturer()) {
                if ($o_manufacturer->get_id() != 'root') {
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
     * Returns product link type (OXARTICLE_LINKTYPE_MANUFACTURER)
     *
     * @return int
     */
    protected function get_product_link_type()
    {
        return OXARTICLE_LINKTYPE_MANUFACTURER;
    }
    /**
     * Loads and returns article list of active Manufacturer.
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer Manufacturer object
     *
     * @return array
     */
    protected function load_articles($o_manufacturer)
    {
        $s_manufacturer_id = $o_manufacturer->get_id();
        // load only articles which we show on screen
        $i_nrof_cat_articles = (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
        $i_nrof_cat_articles = $i_nrof_cat_articles ?: 1;
        $o_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_art_list->set_sql_limit($i_nrof_cat_articles * $this->get_request_page_nr(), $i_nrof_cat_articles);
        $o_art_list->set_custom_sorting($this->get_sorting_sql($this->get_sort_ident()));
        // load the articles
        $this->_i_all_art_cnt = $o_art_list->load_manufacturer_articles($s_manufacturer_id, $o_manufacturer);
        // counting pages
        $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $i_nrof_cat_articles);
        return [$o_art_list, $this->_i_all_art_cnt];
    }
    /**
     * Returns active product id to load its seo meta info
     *
     * @return string
     */
    protected function get_seo_object_id()
    {
        if ($o_manufacturer = $this->get_act_manufacturer()) {
            return $o_manufacturer->get_id();
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
        if (!Registry::get_utils()->seo_is_active()) {
            return parent::add_page_nr_param($s_url, $i_page, $i_lang);
        }
        if (!$o_manufacturer = $this->get_act_manufacturer()) {
            return parent::add_page_nr_param($s_url, $i_page, $i_lang);
        }
        if ($i_page) {
            // only if page number > 0
            return $o_manufacturer->get_base_seo_link($i_lang, $i_page);
        }
        return parent::add_page_nr_param($s_url, $i_page, $i_lang);
    }
    /**
     * Returns current view Url
     *
     * @return string
     */
    public function generate_page_navigation_url()
    {
        if (Registry::get_utils()->seo_is_active() && $o_manufacturer = $this->get_act_manufacturer()) {
            return $o_manufacturer->get_link();
        }
        return parent::generate_page_navigation_url();
    }
    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return array
     */
    public function has_visible_sub_cats()
    {
        if ($this->_bl_visible_sub_cats === null) {
            $this->_bl_visible_sub_cats = false;
            if ($o_manufacturer_tree = $this->get_manufacturer_tree()) {
                if ($o_manufacturer = $this->get_act_manufacturer()) {
                    if ($o_manufacturer->get_id() == 'root') {
                        $this->_bl_visible_sub_cats = $o_manufacturer_tree->count();
                        $this->_o_sub_cat_list = $o_manufacturer_tree;
                    }
                }
            }
        }
        return $this->_bl_visible_sub_cats;
    }
    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return array
     */
    public function get_sub_cat_list()
    {
        if ($this->_o_sub_cat_list === null) {
            $this->_o_sub_cat_list = $this->has_visible_sub_cats() ? $this->_o_sub_cat_list : [];
        }
        return $this->_o_sub_cat_list;
    }
    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return array
     */
    public function get_article_list()
    {
        if ($this->_a_article_list === null) {
            $this->_a_article_list = [];
            if ($o_manufacturer_tree = $this->get_manufacturer_tree()) {
                $o_manufacturer = $this->get_act_manufacturer();
                if ($o_manufacturer && $o_manufacturer->get_id() != 'root' && $o_manufacturer->get_is_visible()) {
                    [$a_article_list, $i_all_art_cnt] = $this->load_articles($o_manufacturer);
                    if ($i_all_art_cnt) {
                        $this->_a_article_list = $a_article_list;
                    }
                }
            }
        }
        return $this->_a_article_list;
    }
    /**
     * Template variable getter. Returns template location
     *
     * @return string
     */
    public function get_title()
    {
        if ($this->_s_cat_title === null) {
            $this->_s_cat_title = '';
            if ($o_manufacturer_tree = $this->get_manufacturer_tree()) {
                if ($o_manufacturer = $this->get_act_manufacturer()) {
                    $this->_s_cat_title = $o_manufacturer->oxmanufacturers__oxtitle->value;
                }
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
        if ($o_manufacturer_tree = $this->get_manufacturer_tree()) {
            return $o_manufacturer_tree->get_path();
        }
    }
    /**
     * Template variable getter. Returns active Manufacturer
     *
     * @return object
     */
    public function get_active_category()
    {
        if ($this->_o_act_category === null) {
            $this->_o_act_category = false;
            if ($o_manufacturer_tree = $this->get_manufacturer_tree()) {
                if ($o_manufacturer = $this->get_act_manufacturer()) {
                    $this->_o_act_category = $o_manufacturer;
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
            if ($o_manufacturer_tree = $this->get_manufacturer_tree()) {
                $this->_s_cat_tree_path = $o_manufacturer_tree->get_path();
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
        if (is_object($this->get_act_manufacturer()->oxmanufacturers__oxshowsuffix) && $this->get_act_manufacturer()->oxmanufacturers__oxshowsuffix->value) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_shop()->oxshops__oxtitlesuffix->value;
        }
        return '';
    }
    /**
     * Calls and returns result of parent:: collectMetaKeyword();
     *
     * @param mixed $aCatPath                category path
     * @param bool  $blRemoveDuplicatedWords remove duplicated words
     *
     * @return string
     */
    protected function prepare_meta_keyword($a_cat_path, $bl_remove_duplicated_words = true)
    {
        return parent::collect_meta_keyword($a_cat_path);
    }
    /**
     * Meta tags - description and keywords - generator for search
     * engines. Uses string passed by parameters, cleans HTML tags,
     * string duplicates, special chars. Also removes strings defined
     * in $myConfig->aSkipTags (Admin area).
     *
     * @param mixed $aCatPath  category path
     * @param int   $iLength   max length of result, -1 for no truncation
     * @param bool  $blDescTag if true - performs additional duplicate cleaning
     *
     * @return  string  $sString    converted string
     */
    protected function prepare_meta_description($a_cat_path, $i_length = 1024, $bl_desc_tag = false)
    {
        return parent::collect_meta_description($a_cat_path, $i_length, $bl_desc_tag);
    }
    /**
     * returns object, assosiated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $iLang language id
     *
     * @return object
     */
    protected function get_subject($i_lang)
    {
        return $this->get_act_manufacturer();
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
        if ($o_manufacturer = $this->get_act_manufacturer()) {
            $s_add_params .= '&amp;mnid=' . $o_manufacturer->get_id();
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
        $o_cat_tree = $this->get_manufacturer_tree();
        if ($o_cat_tree) {
            foreach ($o_cat_tree->get_path() as $o_cat) {
                $a_cat_path = [];
                $a_cat_path['link'] = $o_cat->get_link();
                $a_cat_path['title'] = $o_cat->oxmanufacturers__oxtitle->value;
                $a_paths[] = $a_cat_path;
            }
        }
        return $a_paths;
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