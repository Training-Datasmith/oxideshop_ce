<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Articles searching class.
 * Performs searching through articles in database.
 */
class Search_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Count of all found articles.
     *
     * @var integer
     */
    protected $_i_all_art_cnt = 0;
    /**
     * Number of possible pages.
     *
     * @var integer
     */
    protected $_i_cnt_pages;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/search/search';
    /**
     * List type
     *
     * @var string
     */
    protected $_s_list_type = 'search';
    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_bl_show_sorting = true;
    /**
     * If search was empty
     *
     * @var bool
     */
    protected $_bl_empty_search;
    /**
     * Similar recommendation lists
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_o_recomm_list;
    /**
     * Search parameter for Html
     *
     * @var string
     */
    protected $_s_search_param_for_html;
    /**
     * Search parameter
     *
     * @var string
     */
    protected $_s_search_param;
    /**
     * Searched category
     *
     * @var string
     */
    protected $_s_search_cat_id;
    /**
     * Searched vendor
     *
     * @var string
     */
    protected $_s_search_vendor;
    /**
     * Searched manufacturer
     *
     * @var string
     */
    protected $_s_search_manufacturer;
    /**
     * If called class is search
     *
     * @var bool
     */
    protected $_bl_search_class;
    /**
     * Page navigation
     *
     * @var object
     */
    protected $_o_page_navigation;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * Fetches search parameter from GET/POST/session, prepares search
     * SQL (search::GetWhere()), and executes it forming the list of
     * found articles. Article list is stored at search::_aArticleList
     * array.
     */
    public function init()
    {
        parent::init();
        // #1184M - special char search
        $search_parameter = Registry::get_request()->get_request_parameter('searchparam');
        $search_param_for_query = !empty($search_parameter) ? trim((string) $search_parameter) : null;
        // searching in category ?
        $search_category = Registry::get_request()->get_request_escaped_parameter('searchcnid');
        $initial_search_cat = $search_category ? rawurldecode((string) $search_category) : null;
        $this->_s_search_cat_id = $initial_search_cat;
        // searching in vendor #671
        $search_vendor = Registry::get_request()->get_request_escaped_parameter('searchvendor');
        $initial_search_vendor = $search_vendor ? rawurldecode((string) $search_vendor) : null;
        // searching in Manufacturer #671
        $search_manufacturer = Registry::get_request()->get_request_escaped_parameter('searchmanufacturer');
        $initial_search_manufacturer = $search_manufacturer ? rawurldecode((string) $search_manufacturer) : null;
        $this->_s_search_manufacturer = $initial_search_manufacturer;
        $this->_bl_empty_search = false;
        if (!$search_param_for_query && !$initial_search_cat && !$initial_search_vendor && !$initial_search_manufacturer) {
            //no search string
            $this->_a_article_list = null;
            $this->_bl_empty_search = true;
            return false;
        }
        // config allows to search in Manufacturers ?
        if (!Registry::get_config()->get_config_param('bl_perfLoadManufacturerTree')) {
            $initial_search_manufacturer = null;
        }
        // searching ..
        /** @var \OxidEsales\Eshop\Application\Model\Search $oSearchHandler */
        $o_search_handler = ox_new(\Oxid_Esales\Eshop\Application\Model\Search::class);
        $o_search_list = $o_search_handler->get_search_articles($search_param_for_query, $initial_search_cat, $initial_search_vendor, $initial_search_manufacturer, $this->get_sorting_sql($this->get_sort_ident()));
        // list of found articles
        $this->_a_article_list = $o_search_list;
        $this->_i_all_art_cnt = 0;
        // skip count calculation if no articles in list found
        if ($o_search_list->count()) {
            $this->_i_all_art_cnt = $o_search_handler->get_search_article_count($search_param_for_query, $initial_search_cat, $initial_search_vendor, $initial_search_manufacturer);
        }
        $i_nrof_cat_articles = (int) Registry::get_config()->get_config_param('iNrofCatArticles');
        $i_nrof_cat_articles = $i_nrof_cat_articles ?: 1;
        $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $i_nrof_cat_articles);
    }
    /**
     * Forms search navigation URLs, executes parent::render() and
     * returns name of template to render search::_sThisTemplate.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        parent::render();
        // processing list articles
        $this->process_list_articles();
        return $this->_s_this_template;
    }
    /**
     * Iterates through list articles and performs list view specific tasks:
     *  - sets type of link which needs to be generated (Manufacturer link)
     */
    protected function process_list_articles()
    {
        $s_add_dyn_params = $this->get_add_url_params();
        if ($s_add_dyn_params && $a_art_list = $this->get_article_list()) {
            $bl_seo = \Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active();
            foreach ($a_art_list as $o_article) {
                // appending std and dynamic urls
                if (!$bl_seo) {
                    // only if seo is off..
                    $o_article->append_std_link($s_add_dyn_params);
                }
                $o_article->append_link($s_add_dyn_params);
            }
        }
    }
    /**
     * Returns additional URL parameters which must be added to list products urls
     *
     * @return string
     */
    public function get_add_url_params()
    {
        $s_add_params = parent::get_add_url_params();
        $s_add_params .= ($s_add_params ? '&amp;' : '') . "listtype={$this->_s_list_type}";
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($s_param = Registry::get_request()->get_request_parameter('searchparam')) {
            $s_add_params .= '&amp;searchparam=' . rawurlencode((string) $s_param);
        }
        if ($s_param = Registry::get_request()->get_request_escaped_parameter('searchcnid')) {
            $s_add_params .= "&amp;searchcnid={$s_param}";
        }
        if ($s_param = rawurldecode((string) Registry::get_request()->get_request_escaped_parameter('searchvendor'))) {
            $s_add_params .= "&amp;searchvendor={$s_param}";
        }
        if ($s_param = rawurldecode((string) Registry::get_request()->get_request_escaped_parameter('searchmanufacturer'))) {
            $s_add_params .= "&amp;searchmanufacturer={$s_param}";
        }
        return $s_add_params;
    }
    /**
     * Template variable getter. Returns similar recommendation lists
     *
     * @return object
     */
    protected function is_search_class()
    {
        if ($this->_bl_search_class === null) {
            $this->_bl_search_class = false;
            if ('search' == strtolower((string) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_request_controller_id())) {
                $this->_bl_search_class = true;
            }
        }
        return $this->_bl_search_class;
    }
    /**
     * Template variable getter. Returns if searched was empty
     *
     * @return bool
     */
    public function is_empty_search()
    {
        return $this->_bl_empty_search;
    }
    /**
     * Template variable getter. Returns searched article list
     *
     * @return array
     */
    public function get_article_list()
    {
        return $this->_a_article_list;
    }
    /**
     * Return array of id to form recommend list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array
     */
    public function get_similar_recomm_list_ids()
    {
        if ($this->_a_similar_recomm_list_ids === null) {
            $this->_a_similar_recomm_list_ids = false;
            $a_list = $this->get_article_list();
            if ($a_list && $a_list->count() > 0) {
                $this->_a_similar_recomm_list_ids = $a_list->array_keys();
            }
        }
        return $this->_a_similar_recomm_list_ids;
    }
    /**
     * Template variable getter. Returns search parameter for Html
     *
     * @return string
     */
    public function get_search_param_for_html()
    {
        if ($this->_s_search_param_for_html === null) {
            $this->_s_search_param_for_html = false;
            if ($this->is_search_class()) {
                $this->_s_search_param_for_html = Registry::get_request()->get_request_escaped_parameter('searchparam');
            }
        }
        return $this->_s_search_param_for_html;
    }
    /**
     * Template variable getter. Returns search parameter
     *
     * @return string
     */
    public function get_search_param()
    {
        if ($this->_s_search_param === null) {
            $this->_s_search_param = false;
            if ($this->is_search_class()) {
                $this->_s_search_param = rawurlencode((string) Registry::get_request()->get_request_parameter('searchparam'));
            }
        }
        return $this->_s_search_param;
    }
    /**
     * Template variable getter. Returns searched category id
     *
     * @return string
     */
    public function get_search_cat_id()
    {
        if ($this->_s_search_cat_id === null) {
            $this->_s_search_cat_id = false;
            if ($this->is_search_class()) {
                $this->_s_search_cat_id = rawurldecode((string) Registry::get_request()->get_request_escaped_parameter('searchcnid'));
            }
        }
        return $this->_s_search_cat_id;
    }
    /**
     * Template variable getter. Returns searched vendor id
     *
     * @return string
     */
    public function get_search_vendor()
    {
        if ($this->_s_search_vendor === null) {
            $this->_s_search_vendor = false;
            if ($this->is_search_class()) {
                // searching in vendor #671
                $this->_s_search_vendor = rawurldecode((string) Registry::get_request()->get_request_escaped_parameter('searchvendor'));
            }
        }
        return $this->_s_search_vendor;
    }
    /**
     * Template variable getter. Returns searched Manufacturer id
     *
     * @return string
     */
    public function get_search_manufacturer()
    {
        if ($this->_s_search_manufacturer === null) {
            $this->_s_search_manufacturer = false;
            if ($this->is_search_class()) {
                // searching in Manufacturer #671
                $s_manufacturer_parameter = Registry::get_request()->get_request_escaped_parameter('searchmanufacturer');
                $this->_s_search_manufacturer = rawurldecode((string) $s_manufacturer_parameter);
            }
        }
        return $this->_s_search_manufacturer;
    }
    /**
     * Template variable getter. Returns page navigation
     *
     * @return object
     */
    public function get_page_navigation()
    {
        if ($this->_o_page_navigation === null) {
            $this->_o_page_navigation = false;
            $this->_o_page_navigation = $this->generate_page_navigation();
        }
        return $this->_o_page_navigation;
    }
    /**
     * Template variable getter. Returns active search
     *
     * @return object
     */
    public function get_active_category()
    {
        return $this->get_act_search();
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $a_paths = [];
        $a_path = [];
        $i_base_language = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        $a_path['title'] = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('SEARCH', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Returns config parameters blShowListDisplayType value
     *
     * @return boolean
     */
    public function can_select_display_type()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowListDisplayType');
    }
    /**
     * Checks if current request parameters does not block SEO redirection process
     *
     * @return bool
     */
    protected function can_redirect()
    {
        return false;
    }
    /**
     * Article count getter
     *
     * @return int
     */
    public function get_article_count()
    {
        return $this->_i_all_art_cnt;
    }
    /**
     * Return page title
     *
     * @return string
     */
    public function get_title()
    {
        $s_title = '';
        $s_title .= $this->get_article_count();
        $i_base_language = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        $s_title .= ' ' . \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('HITS_FOR', $i_base_language, false);
        return $s_title . (' "' . $this->get_search_param_for_html() . '"');
    }
}