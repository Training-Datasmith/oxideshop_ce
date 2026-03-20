<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Controller\Base_Controller;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Price;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Request;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Core\Sorting_Validator;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge\User_Review_And_Rating_Bridge_Interface;
use function rawurlencode;
use stdClass;
// view indexing state for search engines:
define('VIEW_INDEXSTATE_INDEX', 0);
//  index without limitations
define('VIEW_INDEXSTATE_NOINDEXNOFOLLOW', 1);
//  no index / no follow
define('VIEW_INDEXSTATE_NOINDEXFOLLOW', 2);
//  no index / follow
/**
 * Base view class.
 * Class is responsible for managing of components that must be
 * loaded and executed before any regular operation.
 */
#[\Allow_Dynamic_Properties]
class Frontend_Controller extends Base_Controller
{
    private const SEARCH_PARAM = 'searchparam';
    private const SEARCH_CATEGORY_ID = 'searchcnid';
    private const SEARCH_VENDOR = 'searchvendor';
    private const SEARCH_MANUFACTURER = 'searchmanufacturer';
    private const CATEGORY_ID = 'cnid';
    private const MANUFACTURER_ID = 'mnid';
    private const SEARCH_RECOMMENDATION = 'searchrecomm';
    private const RECOMMENDATION_ID = 'recommid';
    private const ACTIVE_PRODUCT_ID = 'anid';
    private const LOAD_ID = 'oxloadid';
    private const PAGE = 'page';
    private const TEMPLATE = 'tpl';
    private const PAGE_NUMBER = 'pgNr';
    /**
     * Characters which should be removed while preparing meta keywords
     *
     * @var string
     */
    protected $_s_remove_meta_chars = '.\+*?[^]$(){}=!<>|:&';
    /**
     * Array of component objects.
     *
     * @var array of object
     */
    protected $_oa_components = [];
    /**
     * Flag if current view is an order view
     *
     * @var bool
     */
    protected $_bl_is_order_step = false;
    /**
     * List type
     *
     * @var string
     */
    protected $_s_list_type;
    /**
     * Possible list display types
     *
     * @var array
     */
    protected $_a_list_display_types = ['grid', 'line', 'infogrid'];
    /**
     * List display type
     *
     * @var string
     */
    protected $_s_list_display_type;
    /**
     * List display type
     *
     * @var string
     */
    protected $_s_custom_list_display_type;
    /**
     * Active articles category object.
     *
     * @var \OxidEsales\Eshop\Application\Model\Category
     */
    protected $_o_act_category;
    /**
     * Active Manufacturer object.
     *
     * @var \OxidEsales\Eshop\Application\Model\Manufacturer
     */
    protected $_o_act_manufacturer;
    /**
     * Active vendor object.
     *
     * @var \OxidEsales\Eshop\Application\Model\Vendor
     */
    protected $_o_act_vendor;
    /**
     * Active recommendation's list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     */
    protected $_o_active_recomm_list;
    /**
     * Active search object - stdClass object which keeps navigation info
     *
     * @var stdClass
     */
    protected $_o_act_search;
    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_bl_show_sorting = false;
    /**
     * Load currency option
     *
     * @var bool
     */
    protected $_bl_load_currency;
    /**
     * Load Manufacturers option
     *
     * @var bool
     */
    protected $_bl_load_manufacturer_tree;
    /**
     * Don't show empty cats
     *
     * @var bool
     */
    protected $_bl_dont_show_empty_cats;
    /**
     * Load language option
     *
     * @var bool
     */
    protected $_bl_load_language;
    /**
     * Item count in category top navigation
     *
     * @var integer
     */
    protected $_i_top_cat_nav_itm_cnt;
    /**
     * List's "order by"
     *
     * @var string
     */
    protected $_s_list_order_by;
    /**
     * Order direction of list
     *
     * @var string
     */
    protected $_s_list_order_dir;
    /**
     * Meta description
     *
     * @var string
     */
    protected $_s_meta_description;
    /**
     * Meta keywords
     *
     * @var string
     */
    protected $_s_meta_keywords;
    /**
     * Start page meta description CMS ident
     *
     * @var string
     */
    protected $_s_meta_description_ident;
    /**
     * Start page meta keywords CMS ident
     *
     * @var string
     */
    protected $_s_meta_keywords_ident;
    /**
     * Additional params for url.
     *
     * @var string
     */
    protected $_s_additional_params;
    /**
     * Active currency object.
     *
     * @var object
     */
    protected $_o_act_currency;
    /**
     * Private sales on/off state
     *
     * @var bool
     */
    protected $_bl_enabled_private_sales;
    /**
     * Sign if any new component is added. On this case will be
     * executed components stored in oxBaseView::_aComponentNames
     * plus oxBaseView::_aComponentNames.
     *
     * @var bool
     */
    protected $_bl_common_added = false;
    /**
     * Current view search engine indexing state:
     *     VIEW_INDEXSTATE_INDEX - index without limitations
     *     VIEW_INDEXSTATE_NOINDEXNOFOLLOW - no index / no follow
     *     VIEW_INDEXSTATE_NOINDEXFOLLOW - no index / follow
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_INDEX;
    /**
     * If true, forces FrontendController::noIndex returns VIEW_INDEXSTATE_NOINDEXFOLLOW
     * (FrontendController::$_iViewIndexState = VIEW_INDEXSTATE_NOINDEXFOLLOW; index / follow)
     *
     * @var bool
     */
    protected $_bl_force_no_index = false;
    /**
     * Number of products in compare list.
     *
     * @var integer
     */
    protected $_i_comp_items_cnt;
    /**
     * Default content id
     *
     * @return string
     */
    protected $_s_content_id;
    /** @return \OxidEsales\Eshop\Application\Model\Content Default content. */
    protected $_o_content;
    /** @var string View id. */
    protected $_s_view_reset_id;
    /** @var array Menu list. */
    protected $_a_menue_list;
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     *
     * @var array
     */
    protected $_a_component_names = [
        'oxcmp_user' => 1,
        // 0 means dont init if cached
        'oxcmp_lang' => 0,
        'oxcmp_cur' => 1,
        'oxcmp_shop' => 1,
        'oxcmp_categories' => 0,
        'oxcmp_utils' => 1,
        'oxcmp_basket' => 1,
    ];
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation. User may modify this himself.
     *
     * @var array
     */
    protected $_a_user_component_names = [];
    /** @var \OxidEsales\Eshop\Application\Model\Article Current view product object. */
    protected $_o_product;
    /** @var int Number of current list page. */
    protected $_i_act_page;
    /** @var array A list of articles. */
    protected $_a_article_list;
    /** @var \OxidEsales\Eshop\Application\Model\ManufacturerList Manufacturer list object. */
    protected $_o_manufacturer_tree;
    /** @var \OxidEsales\Eshop\Application\Model\CategoryList Category tree object. */
    protected $_o_category_tree;
    /** @var array Top 5 article list. */
    protected $_a_top5article_list;
    /** @var array Bargain article list. */
    protected $_a_bargain_article_list;
    /** @var integer If order price to low. */
    protected $_bl_low_order_price;
    /** @var string Min order price. */
    protected $_s_min_order_price;
    /** @var string Real newsletter status. */
    protected $_i_news_real_status;
    /** @return array Url parameters which block redirection. */
    protected $_a_block_redirect_params = ['fnc', 'stoken', 'force_sid', 'force_admin_sid'];
    /** @var \OxidEsales\Eshop\Application\Model\Vendor Root vendor object. */
    protected $_o_root_vendor;
    /** @var string Vendor id. */
    protected $_s_vendor_id;
    /** @var array Manufacturer list for search. */
    protected $_a_manufacturerlist;
    /** @var \OxidEsales\Eshop\Application\Model\Manufacturer Root manufacturer object. */
    protected $_o_root_manufacturer;
    /** @var string Manufacturer id. */
    protected $_s_manufacturer_id;
    /** @var bool Has user newsletter subscribed. */
    protected $_bl_news_subscribed;
    /** @var \OxidEsales\Eshop\Application\Model\Address Delivery address. */
    protected $_o_del_address;
    /** @var array Category tree path. */
    protected $_s_cat_tree_path;
    /** @var array Loaded contents array (cache). */
    protected $_a_contents = [];
    /** @var bool Sign if to load and show top5articles action. */
    protected $_bl_top5action = false;
    /** @var bool Sign if to load and show bargain action. */
    protected $_bl_bargain_action = false;
    /** @var array check all "must-be-fields" if they are completely. */
    protected $_a_must_fill_fields;
    /** @var bool If active root category was changed. */
    protected $_bl_root_cat_changed = false;
    /** @var array User address. */
    protected $_a_invoice_address;
    /** @var array User delivery address. */
    protected $_a_delivery_address;
    /** @var string Logged in user name. */
    protected $_s_active_username;
    /** @var boolean is VAT included in prices */
    protected $_bl_is_vat_included;
    /** @var array Components which needs to be initialized/rendered (depending on cache and its cache status). */
    protected static $_a_collected_component_names;
    /** @var array If active load components. By default active. */
    protected $_bl_load_components = true;
    /** @var array Sorting columns list. */
    protected $_a_sort_columns;
    /** @var StdClass Page navigation. */
    protected $_o_page_navigation;
    /** @var integer Number of possible pages. */
    protected $_i_cnt_pages;
    /** @var string Form id. */
    protected $_s_form_id;
    /** @var bool Whether session form id matches with request form id. */
    protected $_bl_can_accept_form_data;
    /**
     * Return true, if the review manager should be shown.
     *
     * @return bool
     */
    public function is_user_allowed_to_manage_own_reviews()
    {
        return (bool) Registry::get_config()->get_config_param('blAllowUsersToManageTheirReviews');
    }
    /**
     * Get the total number of reviews for the active user.
     *
     * @return integer Number of reviews
     */
    public function get_review_and_rating_items_count()
    {
        $user = $this->get_user();
        if ($user) {
            return Container_Facade::get(User_Review_And_Rating_Bridge_Interface::class)->get_review_and_rating_list_count($user->get_id());
        }
        return 0;
    }
    /**
     * Returns component names.
     *
     * At the moment it is not possible to override $_aCollectedComponentNames in oxUBase.
     *
     * @return array
     */
    protected function get_component_names()
    {
        if (self::$_a_collected_component_names === null) {
            self::$_a_collected_component_names = array_merge($this->_a_component_names, $this->_a_user_component_names);
            if ($user_component_names = Container_Facade::get_parameter('oxid_esales.cacheable_user_components')) {
                self::$_a_collected_component_names = array_merge(self::$_a_collected_component_names, $user_component_names);
            }
            if (Registry::get_request()->get_request_escaped_parameter('_force_no_basket_cmp')) {
                unset(self::$_a_collected_component_names['oxcmp_basket']);
            }
        }
        reset(self::$_a_collected_component_names);
        return self::$_a_collected_component_names;
    }
    /**
     * In non admin mode checks if request was NOT processed by seo handler.
     * If NOT, then tries to load alternative SEO url and if url is available -
     * redirects to it. If no alternative path was found - 404 header is emitted
     * and page is rendered
     */
    protected function process_request()
    {
        $utils = Registry::get_utils();
        $request_url = Registry::get(Request::class)->get_request_url();
        // non admin, request is not empty and was not processed by seo engine
        if (!is_search_engine_url() && $utils->seo_is_active() && $request_url) {
            // fetching standard url and looking for it in seo table
            if ($this->can_redirect() && $redirect_url = Registry::get_seo_encoder()->fetch_seo_url($request_url)) {
                $utils->redirect(Registry::get_config()->get_current_shop_url() . $redirect_url, false, 301);
            } elseif (VIEW_INDEXSTATE_INDEX == $this->no_index()) {
                // forcing to set no index/follow meta
                $this->force_no_index();
                if (Container_Facade::get_parameter('oxid_esales.log_not_seo_urls')) {
                    $shop_id = Registry::get_config()->get_shop_id();
                    $language_id = Registry::get_lang()->get_base_language();
                    $id = md5(strtolower((string) $request_url) . $shop_id . $language_id);
                    // logging "not found" url
                    $database = Database_Provider::get_db();
                    $database->execute('replace oxseologs ( oxstdurl, oxident, oxshopid, oxlang ) values ( ?, ?, ?, ? ) ', [$request_url, $id, $shop_id, $language_id]);
                }
            }
        }
    }
    /**
     * Calls self::_processRequest(), initializes components which needs to
     * be loaded, sets current list type, calls parent::init()
     */
    public function init(): void
    {
        $this->process_request();
        // storing current view
        $should_initialize = $this->should_initialize_components();
        // init all components if there are any
        if ($this->_bl_load_components) {
            foreach ($this->get_component_names() as $component_name => $is_not_cacheable) {
                // do not override initiated components
                if (!isset($this->_oa_components[$component_name])) {
                    // component objects MUST be created to support user called functions
                    $component = ox_new($component_name);
                    $component->set_parent($this);
                    $component->set_this_action($component_name);
                    $this->_oa_components[$component_name] = $component;
                }
                // do we really need to initiate them ?
                if ($should_initialize) {
                    $this->_oa_components[$component_name]->init();
                    // executing only is view does not have action method
                    if (!method_exists($this, (string) $this->get_fnc_name())) {
                        $this->_oa_components[$component_name]->execute_function($this->get_fnc_name());
                    }
                }
            }
        }
        parent::init();
    }
    /**
     * Returns whether init() should initialize created components.
     *
     * @return bool
     */
    protected function should_initialize_components()
    {
        return true;
    }
    /**
     * If current view ID is not set - forms and returns view ID
     * according to language and currency.
     *
     * @return string $this->_sViewId
     */
    public function get_view_id()
    {
        return $this->_s_view_id ?? $this->_s_view_id = $this->generate_view_id();
    }
    /**
     * Generates current view id.
     *
     * @return string
     */
    protected function generate_view_id()
    {
        $config = Registry::get_config();
        $view_id = $this->generate_view_id_base();
        $view_id .= '|' . (int) $this->_bl_force_no_index . '|' . (int) $this->is_root_cat_changed();
        // #0004798: SSL should be included in viewId
        if ($config->is_ssl()) {
            $view_id .= '|ssl';
        }
        // #0002866: external global viewID addition
        if (function_exists('customGetViewId')) {
            $external_view_id = custom_get_view_id();
            if ($external_view_id !== null) {
                $view_id .= '|' . md5(serialize($external_view_id));
            }
        }
        return $view_id;
    }
    /**
     * Generates base for view id.
     *
     * @return string
     */
    protected function generate_view_id_base()
    {
        $language_id = Registry::get_lang()->get_base_language();
        $currency_id = (int) Registry::get_config()->get_shop_currency();
        return "ox|{$language_id}|{$currency_id}";
    }
    /**
     * Template variable getter. Returns true if sorting is on
     *
     * @return bool
     */
    public function show_sorting()
    {
        return $this->_bl_show_sorting && Registry::get_config()->get_config_param('blShowSorting');
    }
    /**
     * Set array of component objects
     *
     * @param array $components array of components objects
     */
    public function set_components($components = null): void
    {
        $this->_oa_components = $components;
    }
    /**
     * Get array of component objects
     *
     * @return array
     */
    public function get_components()
    {
        return $this->_oa_components;
    }
    /**
     * Get component object
     *
     * @param string $name name of component object
     *
     * @return object
     */
    public function get_component($name)
    {
        if (isset($name) && isset($this->_oa_components[$name])) {
            return $this->_oa_components[$name];
        }
    }
    /**
     * Set flag if current view is an order view
     *
     * @param bool $isOrderStep flag if current view is an order view
     */
    public function set_is_order_step($is_order_step = null): void
    {
        $this->_bl_is_order_step = $is_order_step;
    }
    /**
     * Get flag if current view is an order view
     *
     * @return bool
     */
    public function get_is_order_step()
    {
        return $this->_bl_is_order_step;
    }
    /**
     * Active category setter
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $category active category
     */
    public function set_active_category($category): void
    {
        $this->_o_act_category = $category;
    }
    /**
     * Returns active category
     *
     * @return \OxidEsales\Eshop\Application\Model\Category|null
     */
    public function get_active_category()
    {
        return $this->_o_act_category;
    }
    /**
     * Get list type
     *
     * @return string list type
     */
    public function get_list_type()
    {
        if ($this->_s_list_type == null) {
            if ($list_type = Registry::get_request()->get_request_escaped_parameter('listtype')) {
                $this->_s_list_type = $list_type;
            } elseif ($list_type = Registry::get_config()->get_global_parameter('listtype')) {
                $this->_s_list_type = $list_type;
            }
        }
        return $this->_s_list_type;
    }
    /**
     * Returns list type
     *
     * @return string
     */
    public function get_list_display_type()
    {
        if ($this->_s_list_display_type == null) {
            $this->_s_list_display_type = $this->get_custom_list_display_type();
            if (!$this->_s_list_display_type) {
                $this->_s_list_display_type = Registry::get_config()->get_config_param('sDefaultListDisplayType');
            }
            $this->_s_list_display_type = in_array((string) $this->_s_list_display_type, $this->_a_list_display_types) ? $this->_s_list_display_type : 'infogrid';
            // writing to session
            if (Registry::get_request()->get_request_escaped_parameter('ldtype')) {
                Registry::get_session()->set_variable('ldtype', $this->_s_list_display_type);
            }
        }
        return $this->_s_list_display_type;
    }
    /**
     * Returns changed default list type
     *
     * @return string
     */
    public function get_custom_list_display_type()
    {
        if ($this->_s_custom_list_display_type == null) {
            $this->_s_custom_list_display_type = Registry::get_request()->get_request_escaped_parameter('ldtype');
            if (!$this->_s_custom_list_display_type) {
                $this->_s_custom_list_display_type = Registry::get_session()->get_variable('ldtype');
            }
        }
        return $this->_s_custom_list_display_type;
    }
    /**
     * List type setter
     *
     * @param string $type type of list
     */
    public function set_list_type($type): void
    {
        $this->_s_list_type = $type;
        Registry::get_config()->set_global_parameter('listtype', $type);
    }
    /**
     * Returns currency switching option
     *
     * @return bool
     */
    public function load_currency()
    {
        if ($this->_bl_load_currency == null) {
            $this->_bl_load_currency = false;
            if ($load_currency = Registry::get_config()->get_config_param('bl_perfLoadCurrency')) {
                $this->_bl_load_currency = $load_currency;
            }
        }
        return $this->_bl_load_currency;
    }
    /**
     * Returns true if empty categories are not loaded
     *
     * @return bool
     */
    public function dont_show_empty_categories()
    {
        if ($this->_bl_dont_show_empty_cats == null) {
            $this->_bl_dont_show_empty_cats = false;
            if ($dont_show_empty_cats = Registry::get_config()->get_config_param('blDontShowEmptyCategories')) {
                $this->_bl_dont_show_empty_cats = $dont_show_empty_cats;
            }
        }
        return $this->_bl_dont_show_empty_cats;
    }
    /**
     * Returns true if empty categories are not loaded
     *
     * @return bool
     */
    public function show_category_articles_count()
    {
        return Registry::get_config()->get_config_param('bl_perfShowActionCatArticleCnt');
    }
    /**
     * Returns if language should be loaded
     *
     * @return bool
     */
    public function is_language_loaded()
    {
        if ($this->_bl_load_language == null) {
            $this->_bl_load_language = false;
            if ($load_language = Registry::get_config()->get_config_param('bl_perfLoadLanguages')) {
                $this->_bl_load_language = $load_language;
            }
        }
        return $this->_bl_load_language;
    }
    /**
     * Returns item count in top navigation of categories
     *
     * @return integer
     */
    public function get_top_navigation_cat_cnt()
    {
        if ($this->_i_top_cat_nav_itm_cnt == null) {
            $top_category_navigation_items_count = Registry::get_config()->get_config_param('iTopNaviCatCount');
            $this->_i_top_cat_nav_itm_cnt = $top_category_navigation_items_count ?: 5;
        }
        return $this->_i_top_cat_nav_itm_cnt;
    }
    /**
     * Returns sorted column parameter name
     *
     * @return string
     */
    public function get_sort_order_by_parameter_name()
    {
        return 'listorderby';
    }
    /**
     * Returns sorted column direction parameter name
     *
     * @return string
     */
    public function get_sort_order_parameter_name()
    {
        return 'listorder';
    }
    /**
     * Returns page sort ident. It is used as ident in session variable aSorting[ident]
     *
     * @return string
     */
    public function get_sort_ident()
    {
        return 'alist';
    }
    /**
     * Returns default category sorting for selected category
     */
    public function get_default_sorting()
    {
        return null;
    }
    /**
     * Returns default category sorting for selected category
     *
     * @return array
     */
    public function get_user_selected_sorting()
    {
        $request = Registry::get(\Oxid_Esales\Eshop\Core\Request::class);
        $sort_by = $request->get_request_parameter($this->get_sort_order_by_parameter_name());
        $sort_order = $request->get_request_parameter($this->get_sort_order_parameter_name());
        if ((new Sorting_Validator())->is_valid($sort_by, $sort_order)) {
            return ['sortby' => $sort_by, 'sortdir' => $sort_order];
        }
    }
    /**
     * Returns sorting variable from session
     *
     * @param string $sortIdent sorting indent
     *
     * @return array
     */
    public function get_saved_sorting($sort_ident)
    {
        $sorting = Registry::get_session()->get_variable('aSorting');
        if (isset($sorting[$sort_ident])) {
            return $sorting[$sort_ident];
        }
    }
    /**
     * Set sorting column name
     *
     * @param string $column - column name
     */
    public function set_list_order_by($column): void
    {
        $this->_s_list_order_by = $column;
    }
    /**
     * Set sorting directions
     *
     * @param string $direction - direction desc / asc
     */
    public function set_list_order_direction($direction): void
    {
        $this->_s_list_order_dir = $direction;
    }
    /**
     * Template variable getter. Returns string after the list is ordered by
     *
     * @return array
     */
    public function get_list_order_by()
    {
        //if column is with table name split it
        $columns = $this->_s_list_order_by ? explode('.', $this->_s_list_order_by) : [];
        if (count($columns) > 1) {
            return $columns[1];
        }
        return $this->_s_list_order_by;
    }
    /**
     * Template variable getter. Returns list order direction
     *
     * @return array
     */
    public function get_list_order_direction()
    {
        return $this->_s_list_order_dir;
    }
    /**
     * Sets the view parameter "meta_description"
     *
     * @param string $description prepared string for description
     */
    public function set_meta_description($description)
    {
        return $this->_s_meta_description = $description;
    }
    /**
     * Sets the view parameter 'meta_keywords'
     *
     * @param string $keywords prepared string for meta keywords
     */
    public function set_meta_keywords($keywords)
    {
        return $this->_s_meta_keywords = $keywords;
    }
    /**
     * Fetches meta data (description or keywords) from seo table
     *
     * @param string $dataType data type "oxkeywords" or "oxdescription"
     *
     * @return string
     */
    protected function get_meta_from_seo($data_type)
    {
        $seo_object_id = $this->get_seo_object_id();
        $base_language_id = Registry::get_lang()->get_base_language();
        $shop_id = Registry::get_config()->get_shop_id();
        if ($seo_object_id && Registry::get_utils()->seo_is_active() && $keywords = Registry::get_seo_encoder()->get_meta_data($seo_object_id, $data_type, $shop_id, $base_language_id)) {
            return $keywords;
        }
    }
    /**
     * Fetches meta data (description or keywords) from content table
     *
     * @param string $metaIdent meta content ident
     *
     * @return string
     */
    protected function get_meta_from_content($meta_ident)
    {
        if ($meta_ident) {
            $content = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
            if ($content->load_by_ident($meta_ident) && $content->oxcontents__oxactive->value) {
                return Str::get_str()->strip_tags($content->oxcontents__oxcontent->value);
            }
        }
    }
    /**
     * Template variable getter. Returns meta keywords
     *
     * @return string
     */
    public function get_meta_keywords()
    {
        if ($this->_s_meta_keywords === null) {
            $this->_s_meta_keywords = false;
            // set special meta keywords ?
            if ($keywords = $this->get_meta_from_seo('oxkeywords')) {
                $this->_s_meta_keywords = $keywords;
            } elseif ($keywords = $this->get_meta_from_content($this->_s_meta_keywords_ident)) {
                $this->_s_meta_keywords = $this->prepare_meta_keyword($keywords, false);
            } else {
                $this->_s_meta_keywords = $this->prepare_meta_keyword(false, true);
            }
        }
        return $this->_s_meta_keywords;
    }
    /**
     * Template variable getter. Returns meta description
     *
     * @return string
     */
    public function get_meta_description()
    {
        if ($this->_s_meta_description === null) {
            $this->_s_meta_description = false;
            // set special meta description ?
            if ($description = $this->get_meta_from_seo('oxdescription')) {
                $this->_s_meta_description = $description;
            } elseif ($description = $this->get_meta_from_content($this->_s_meta_description_ident)) {
                $this->_s_meta_description = $this->prepare_meta_description($description);
            } else {
                $this->_s_meta_description = $this->prepare_meta_description(false);
            }
        }
        return $this->_s_meta_description;
    }
    /**
     * Get active currency
     *
     * @return object
     */
    public function get_act_currency()
    {
        return $this->_o_act_currency;
    }
    /**
     * Active currency setter
     *
     * @param object $currency Currency object
     */
    public function set_act_currency($currency): void
    {
        $this->_o_act_currency = $currency;
    }
    /**
     * Template variable getter. Returns comparison article list count.
     *
     * @return integer
     */
    public function get_compare_item_count()
    {
        if ($this->_i_comp_items_cnt === null) {
            $items = Registry::get_session()->get_variable('aFiltcompproducts');
            $this->_i_comp_items_cnt = is_array($items) ? count($items) : 0;
        }
        return $this->_i_comp_items_cnt;
    }
    /**
     * Forces output no index meta data for current view
     */
    protected function force_no_index()
    {
        $this->_bl_force_no_index = true;
    }
    /**
     * Marks that current view is marked as no index, no follow and
     * article details links must contain no follow tags
     *
     * @return int
     */
    public function no_index()
    {
        if ($this->_bl_force_no_index) {
            $this->_i_view_index_state = VIEW_INDEXSTATE_NOINDEXFOLLOW;
        } elseif (Registry::get_request()->get_request_escaped_parameter('cur')) {
            $this->_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
        } elseif (0 < Registry::get_request()->get_request_escaped_parameter(self::PAGE_NUMBER)) {
            $this->_i_view_index_state = VIEW_INDEXSTATE_NOINDEXFOLLOW;
        } else {
            switch (Registry::get_request()->get_request_escaped_parameter('fnc')) {
                case 'tocomparelist':
                case 'tobasket':
                    $this->_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
                    break;
            }
        }
        return $this->_i_view_index_state;
    }
    /**
     * Template variable getter. Returns header menu list
     *
     * @return array
     */
    public function get_menue_list()
    {
        return $this->_a_menue_list;
    }
    /**
     * Header menu list setter
     *
     * @param array $menu menu list
     */
    public function set_menue_list($menu): void
    {
        $this->_a_menue_list = $menu;
    }
    /**
     * Sets number of articles per page to config value
     */
    protected function set_nr_of_art_per_page()
    {
        $config = Registry::get_config();
        //setting default values to avoid possible errors showing article list
        $number_of_category_articles = $config->get_config_param('iNrofCatArticles');
        $number_of_category_articles = $number_of_category_articles ?: 10;
        // checking if all needed data is set
        $numbers_of_category_articles = match ($this->get_list_display_type()) {
            'grid' => $config->get_config_param('aNrofCatArticlesInGrid'),
            default => $config->get_config_param('aNrofCatArticles'),
        };
        if (!is_array($numbers_of_category_articles) || !isset($numbers_of_category_articles[0])) {
            $numbers_of_category_articles = [$number_of_category_articles];
            $config->set_config_param('aNrofCatArticles', $numbers_of_category_articles);
        } else {
            $number_of_category_articles = $numbers_of_category_articles[0];
        }
        $view_config = $this->get_view_config();
        //value from user input
        $session = Registry::get_session();
        if ($articles_per_page = (int) Registry::get_request()->get_request_escaped_parameter('_artperpage')) {
            // M45 Possibility to push any "Show articles per page" number parameter
            $number_of_category_articles = in_array($articles_per_page, $numbers_of_category_articles) ? $articles_per_page : $number_of_category_articles;
            $view_config->set_view_config_param('iartPerPage', $number_of_category_articles);
            $session->set_variable('_artperpage', $number_of_category_articles);
        } elseif (($sess_art_per_page = $session->get_variable('_artperpage')) && is_numeric($sess_art_per_page)) {
            // M45 Possibility to push any "Show articles per page" number parameter
            $number_of_category_articles = in_array($sess_art_per_page, $numbers_of_category_articles) ? $sess_art_per_page : $number_of_category_articles;
            $view_config->set_view_config_param('iartPerPage', $number_of_category_articles);
            $session->set_variable('_artperpage', $number_of_category_articles);
        } else {
            $view_config->set_view_config_param('iartPerPage', $number_of_category_articles);
        }
        //setting number of articles per page to config value
        $config->set_config_param('iNrofCatArticles', $number_of_category_articles);
    }
    /**
     * Override this function to return object it which is used to identify its seo meta info
     */
    protected function get_seo_object_id()
    {
    }
    /**
     * Returns current view meta description data
     *
     * @param string $meta                  Category path
     * @param int    $length                Max length of result, -1 for no truncation
     * @param bool   $removeDuplicatedWords If true - performs additional duplicate cleaning
     *
     * @return  string  $string    converted string
     */
    protected function prepare_meta_description($meta, $length = 1024, $remove_duplicated_words = false)
    {
        if ($meta) {
            $string_modifier = Str::get_str();
            if ($length != -1) {
                /* *
                 * performance - we do not need a huge amount of initial text.
                 * assume that effective text may be double longer than $length
                 * and simple truncate it
                 */
                $double_length = $length * 2;
                $meta = $string_modifier->substr($meta, 0, $double_length);
            }
            // decoding html entities
            $meta = $string_modifier->html_entity_decode($meta);
            // stripping HTML tags
            $meta = $string_modifier->strip_tags($meta);
            // removing some special chars
            $meta = $string_modifier->clean_str($meta);
            // removing duplicate words
            if ($remove_duplicated_words) {
                $meta = $this->remove_duplicated_words($meta, Registry::get_config()->get_config_param('aSkipTags'));
            }
            // some special cases
            $meta = str_replace(' ,', ',', $meta);
            $pattern = ["/,[\\s+\\-*]*,/", "/\\s+,/"];
            $meta = $string_modifier->preg_replace($pattern, ',', $meta);
            $meta = Registry::get_utils_string()->minimize_truncate_string($meta, $length);
            $meta = $string_modifier->htmlspecialchars($meta);
            return trim((string) $meta);
        }
    }
    /**
     * Returns current view keywords separated by comma
     *
     * @param string $keywords              Data to use as keywords
     * @param bool   $removeDuplicatedWords If true - performs additional duplicate cleaning
     *
     * @return string of keywords separated by comma
     */
    protected function prepare_meta_keyword($keywords, $remove_duplicated_words = true)
    {
        $string = $this->prepare_meta_description($keywords, -1, false);
        if ($remove_duplicated_words) {
            $string = $this->remove_duplicated_words($string, Registry::get_config()->get_config_param('aSkipTags'));
        }
        return trim($string);
    }
    /**
     * Removes duplicated words (not case sensitive)
     *
     * @param mixed $input    array of string or string
     * @param array $skipTags in admin defined strings
     *
     * @return string of words separated by comma
     */
    protected function remove_duplicated_words($input, $skip_tags = [])
    {
        $string_modifier = Str::get_str();
        if (is_array($input)) {
            $input = implode(' ', $input);
        }
        // removing some usually met characters..
        $input = $string_modifier->preg_replace('/[' . preg_quote($this->_s_remove_meta_chars, '/') . ']/', ' ', $input);
        // splitting by word
        $strings = $string_modifier->preg_split("/[\\s,]+/", $input);
        if ($count = count($skip_tags)) {
            for ($num = 0; $num < $count; $num++) {
                $skip_tags[$num] = $string_modifier->strtolower($skip_tags[$num]);
            }
        }
        $count = count($strings);
        for ($num = 0; $num < $count; $num++) {
            $strings[$num] = $string_modifier->strtolower($strings[$num]);
            // removing in admin defined strings
            if (!$strings[$num] || in_array($strings[$num], $skip_tags)) {
                unset($strings[$num]);
            }
        }
        // duplicates
        return implode(', ', array_unique($strings));
    }
    /**
     * Returns array of params => values which are used in hidden forms and as additional url params.
     * NOTICE: this method SHOULD return raw (non encoded into entities) parameters, because values
     * are processed by htmlentities() to avoid security and broken templates problems
     *
     * @return array
     */
    public function get_navigation_params()
    {
        Registry::get_config();
        $params[self::CATEGORY_ID] = $this->get_category_id();
        $params[self::MANUFACTURER_ID] = Registry::get_request()->get_request_escaped_parameter(self::MANUFACTURER_ID);
        $params['listtype'] = $this->get_list_type();
        $params['ldtype'] = $this->get_custom_list_display_type();
        $params['actcontrol'] = $this->get_class_key();
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        $params[self::RECOMMENDATION_ID] = Registry::get_request()->get_request_escaped_parameter(self::RECOMMENDATION_ID);
        $params[self::SEARCH_RECOMMENDATION] = Registry::get_request()->get_request_escaped_parameter(self::SEARCH_RECOMMENDATION);
        // END deprecated
        $params[self::SEARCH_PARAM] = Registry::get_request()->get_request_escaped_parameter(self::SEARCH_PARAM);
        $params[self::SEARCH_VENDOR] = Registry::get_request()->get_request_escaped_parameter(self::SEARCH_VENDOR);
        $params[self::SEARCH_CATEGORY_ID] = Registry::get_request()->get_request_escaped_parameter(self::SEARCH_CATEGORY_ID);
        $params[self::SEARCH_MANUFACTURER] = Registry::get_request()->get_request_escaped_parameter(self::SEARCH_MANUFACTURER);
        return array_merge($params, $this->get_view_config()->get_additional_navigation_parameters());
    }
    /**
     * Sets sorting item config
     *
     * @param string $sortIdent sortable item id
     * @param string $sortBy    sort field
     * @param string $sortDir   sort direction (optional)
     */
    public function set_item_sorting($sort_ident, $sort_by, $sort_dir = null): void
    {
        $sorting = Registry::get_session()->get_variable('aSorting');
        $sorting[$sort_ident]['sortby'] = $sort_by;
        $sorting[$sort_ident]['sortdir'] = $sort_dir ?: null;
        Registry::get_session()->set_variable('aSorting', $sorting);
    }
    /**
     * Returns sorting config for current item
     *
     * @param string $sortIdent sortable item id
     *
     * @return array
     */
    public function get_sorting($sort_ident)
    {
        $sorting = null;
        if ($sorting = $this->get_user_selected_sorting()) {
            $this->set_item_sorting($sort_ident, $sorting['sortby'], $sorting['sortdir']);
        } elseif (!$sorting = $this->get_saved_sorting($sort_ident)) {
            $sorting = $this->get_default_sorting();
        }
        if ($sorting) {
            $this->set_list_order_by($sorting['sortby']);
            $this->set_list_order_direction($sorting['sortdir']);
        }
        return $sorting;
    }
    /**
     * Returns part of SQL query with sorting params
     *
     * @param string $ident sortable item id
     *
     * @return string
     */
    public function get_sorting_sql($ident)
    {
        $sorting = $this->get_sorting($ident);
        if (is_array($sorting)) {
            $sort_dir = $sorting['sortdir'] ?? '';
            if ($this->is_allowed_sorting_order($sort_dir)) {
                $sort_by = Database_Provider::get_db()->quote_identifier($sorting['sortby']);
                return trim($sort_by . ' ' . $sort_dir);
            }
        }
    }
    /**
     * Returns title suffix used in template
     *
     * @return string
     */
    public function get_title_suffix()
    {
        return Registry::get_config()->get_active_shop()->oxshops__oxtitlesuffix->value;
    }
    /**
     * Returns title page suffix used in template in lists
     */
    public function get_title_page_suffix()
    {
    }
    /**
     * Returns title prefix used in template
     *
     * @return string
     */
    public function get_title_prefix()
    {
        return Registry::get_config()->get_active_shop()->oxshops__oxtitleprefix->value;
    }
    /**
     * Returns full page title
     *
     * @return string
     */
    public function get_page_title()
    {
        $title_parts = [];
        $title_parts[] = $this->get_title_prefix();
        $title_parts[] = $this->get_title();
        $title_parts[] = $this->get_title_suffix();
        $title_parts[] = $this->get_title_page_suffix();
        $title_parts = array_filter($title_parts);
        $title = implode(' | ', $title_parts);
        return $this->replace_double_quotes_with_html_characters($title);
    }
    /**
     * returns object, associated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $languageId language id
     *
     * @return object
     */
    protected function get_subject($language_id)
    {
        return null;
    }
    /**
     * returns additional url params for dynamic url building
     *
     * @return string
     */
    public function get_dyn_url_params()
    {
        $result = '';
        $list_type = $this->get_list_type();
        switch ($list_type) {
            default:
                $result .= $this->get_view_config()->get_dyn_url_parameters($list_type);
                break;
            case 'search':
                $result .= "&amp;listtype={$list_type}";
                $result .= $this->append_unescaped_encoded_value(self::SEARCH_PARAM);
                $result .= $this->append_unescaped_value(self::SEARCH_CATEGORY_ID);
                $result .= $this->append_unescaped_value(self::SEARCH_VENDOR);
                $result .= $this->append_unescaped_value(self::SEARCH_MANUFACTURER);
                break;
        }
        return $result;
    }
    /**
     * Get base link of current view
     *
     * @param int $languageId requested language
     *
     * @return string
     */
    public function get_base_link($language_id = null)
    {
        if (!isset($language_id)) {
            $language_id = Registry::get_lang()->get_base_language();
        }
        $config = Registry::get_config();
        if (Registry::get_utils()->seo_is_active()) {
            if ($display_obj = $this->get_subject($language_id)) {
                $url = $display_obj->get_link($language_id);
            } else {
                $encoder = Registry::get_seo_encoder();
                $constructed_url = $config->get_shop_home_url($language_id) . $this->get_seo_request_params();
                $url = $encoder->get_static_url($constructed_url, $language_id);
            }
        }
        if (!$url) {
            $constructed_url = $config->get_shop_current_url($language_id) . $this->get_request_params();
            $url = Registry::get_utils_url()->process_url($constructed_url, true, null, $language_id);
        }
        return $url;
    }
    /**
     * Get link of current view. In url its include also page number if it is list page
     *
     * @param int $languageId requested language
     *
     * @return string
     */
    public function get_link($language_id = null)
    {
        return $this->add_page_nr_param($this->get_base_link($language_id), $this->get_act_page(), $language_id);
    }
    /**
     * Returns view object canonical url
     */
    public function get_canonical_url()
    {
    }
    /**
     * Return array of id to form recommend list.
     * Should be overridden if need.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array
     */
    public function get_similar_recomm_list_ids()
    {
        return false;
    }
    /**
     * Template variable getter. Returns search parameter for Html
     * So far this method is implemented in search (search.php) view.
     */
    public function get_search_param_for_html()
    {
    }
    /**
     * collects _GET parameters used by eShop and returns uri
     *
     * @param bool $addPageNumber if TRUE - page number will be added
     *
     * @return string
     */
    protected function get_request_params($add_page_number = true)
    {
        $class = $this->get_class_key();
        $function = $this->get_fnc_name();
        $forbidden_functions = ['tobasket', 'login_noredirect', 'addVoucher', 'moveleft', 'moveright', 'deleteReviewAndRating'];
        if (\in_array($function, $forbidden_functions, true)) {
            $function = '';
        }
        // #680
        $url = "cl={$class}";
        if ($function) {
            $url .= "&amp;fnc={$function}";
        }
        $url .= $this->append_value(self::CATEGORY_ID);
        $url .= $this->append_value(self::MANUFACTURER_ID);
        $url .= $this->append_value(self::ACTIVE_PRODUCT_ID);
        $url .= $this->append_basename_value(self::PAGE);
        $url .= $this->append_basename_value(self::TEMPLATE);
        $url .= $this->append_value(self::LOAD_ID);
        // don't include page number for navigation
        // it will be done in \OxidEsales\Eshop\Application\Controller\FrontendController::generatePageNavigation
        if ($add_page_number) {
            $url .= $this->append_value(self::PAGE_NUMBER);
        }
        // #1184M - specialchar search
        $url .= $this->append_unescaped_encoded_value(self::SEARCH_PARAM);
        $url .= $this->append_value(self::SEARCH_CATEGORY_ID);
        $url .= $this->append_value(self::SEARCH_VENDOR);
        $url .= $this->append_value(self::SEARCH_MANUFACTURER);
        $url .= $this->append_value(self::SEARCH_RECOMMENDATION);
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        $url .= $this->append_value(self::RECOMMENDATION_ID);
        // END deprecated
        $url .= $this->get_view_config()->add_request_parameters();
        return $url;
    }
    /**
     * collects _GET parameters used by eShop SEO and returns uri
     *
     * @return string
     */
    protected function get_seo_request_params()
    {
        $class = $this->get_class_key();
        $function = $this->get_fnc_name();
        // #921 S
        $forbidden_functions = ['tobasket', 'login_noredirect', 'addVoucher'];
        if (\in_array($function, $forbidden_functions, true)) {
            $function = '';
        }
        // #680
        $url = "cl={$class}";
        if ($function) {
            $url .= "&amp;fnc={$function}";
        }
        $url .= $this->append_basename_value(self::PAGE);
        $url .= $this->append_basename_value(self::TEMPLATE);
        $url .= $this->append_value(self::LOAD_ID);
        return $url . $this->append_value(self::PAGE_NUMBER);
    }
    /**
     * Returns show category search
     *
     * @return bool
     */
    public function show_search()
    {
        return !(Registry::get_config()->get_config_param('blDisableNavBars') && $this->get_is_order_step());
    }
    /**
     * Template variable getter. Returns sorting columns
     *
     * @return array
     */
    public function get_sort_columns()
    {
        if ($this->_a_sort_columns === null) {
            $this->set_sort_columns(Registry::get_config()->get_config_param('aSortCols'));
        }
        return $this->_a_sort_columns;
    }
    /**
     * Set sorting columns
     *
     * @param array $sortColumns array of column names array('name1', 'name2',...)
     */
    public function set_sort_columns($sort_columns): void
    {
        $this->_a_sort_columns = $sort_columns;
    }
    /**
     * Template variable getter. Returns search string
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     */
    public function get_recomm_search()
    {
    }
    /**
     * Template variable getter. Returns payment id
     */
    public function get_payment_list()
    {
    }
    /**
     * Template variable getter. Returns active recommendation lists
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return \OxidEsales\Eshop\Application\Model\RecommendationList|false
     */
    public function get_active_recomm_list()
    {
        if ($this->_o_active_recomm_list === null) {
            $this->_o_active_recomm_list = false;
            if ($recommendation_list_id = Registry::get_request()->get_request_escaped_parameter(self::RECOMMENDATION_ID)) {
                $this->_o_active_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
                $this->_o_active_recomm_list->load($recommendation_list_id);
            }
        }
        return $this->_o_active_recomm_list;
    }
    /**
     * Template variable getter. Returns accessoires of article
     */
    public function get_accessoires()
    {
    }
    /**
     * Template variable getter. Returns crosssellings
     */
    public function get_cross_selling()
    {
    }
    /**
     * Template variable getter. Returns similar article list
     */
    public function get_similar_products()
    {
    }
    /**
     * Template variable getter. Returns list of customer also bought thies products
     */
    public function get_also_bought_these_products()
    {
    }
    /**
     * Return the active article id
     */
    public function get_article_id()
    {
    }
    /**
     * Returns current view title. Default is search for translation of PAGE_TITLE_{view_class_name}
     *
     * @return string
     */
    public function get_title()
    {
        $language = Registry::get_lang();
        $translation_name = 'PAGE_TITLE_' . strtoupper((string) Registry::get_config()->get_active_view()->get_class_key());
        $translated = $language->translate_string($translation_name, Registry::get_lang()->get_base_language(), false);
        return $translation_name == $translated ? null : $translated;
    }
    /**
     * Returns active lang suffix
     * usally it used in html lang attr to allow the browser to interpret the page in the right language
     * e.g. to support hyphons
     * @return string
     */
    public function get_active_lang_abbr()
    {
        if (!isset($this->_s_active_lang_abbr)) {
            $language_service = Registry::get_lang();
            if (Registry::get_config()->get_config_param('bl_perfLoadLanguages')) {
                $languages = $language_service->get_language_array();
                foreach ($languages as $language) {
                    if ($language->selected) {
                        $this->_s_active_lang_abbr = $language->abbr;
                        break;
                    }
                }
            } else {
                // Performance
                // use oxid shop internal languageAbbr, this might be correct in the most cases but not guaranteed to
                // be that configured in the admin backend for that language
                $this->_s_active_lang_abbr = $language_service->get_language_abbr();
            }
        }
        return $this->_s_active_lang_abbr;
    }
    /**
     * Sets and caches default parameters for shop object and returns it.
     *
     * @param \OxidEsales\Eshop\Application\Model\Shop $shop current shop object
     *
     * @return \OxidEsales\Eshop\Core\ViewConfig Current shop object
     */
    public function add_global_params($shop = null)
    {
        $view_config = parent::add_global_params($shop);
        $this->set_nr_of_art_per_page();
        return $view_config;
    }
    /**
     * Template variable getter. Returns additional params for url
     *
     * @return string
     */
    public function get_additional_params()
    {
        if ($this->_s_additional_params === null) {
            // #1018A
            $this->_s_additional_params = parent::get_additional_params();
            $this->_s_additional_params .= 'cl=' . Registry::get_config()->get_top_active_view()->get_class_key();
            // #1834M - special char search
            $this->_s_additional_params .= $this->append_unescaped_encoded_value(self::SEARCH_PARAM);
            $this->_s_additional_params .= $this->append_value(self::SEARCH_CATEGORY_ID);
            $this->_s_additional_params .= $this->append_value(self::SEARCH_VENDOR);
            $this->_s_additional_params .= $this->append_value(self::SEARCH_MANUFACTURER);
            $this->_s_additional_params .= $this->append_value(self::CATEGORY_ID);
            $this->_s_additional_params .= $this->append_value(self::MANUFACTURER_ID);
            $this->_s_additional_params .= $this->get_view_config()->get_additional_parameters();
        }
        return $this->_s_additional_params;
    }
    /**
     * Generates URL for page navigation
     *
     * @return string $url String with working page url.
     */
    public function generate_page_navigation_url()
    {
        return Registry::get_config()->get_shop_home_url() . $this->get_request_params(false);
    }
    /**
     * Adds page number parameter to url and returns modified url, if page number 0 drops from url
     *
     * @param string $url        Url to add page number
     * @param int    $page       Active page number
     * @param int    $languageId Language id
     *
     * @return string
     */
    protected function add_page_nr_param($url, $page, $language_id = null)
    {
        if ($page) {
            if (strpos($url, 'pgNr=')) {
                $url = preg_replace('/pgNr=[0-9]*/', 'pgNr=' . $page, $url);
            } else {
                $url .= (!str_contains($url, '?') ? '?' : '&amp;') . 'pgNr=' . $page;
            }
        } else {
            $url = preg_replace('/pgNr=[0-9]*/', '', $url);
            $url = preg_replace('/\&amp\;\&amp\;/', '&amp;', (string) $url);
            $url = preg_replace('/\?\&amp\;/', '?', (string) $url);
            $url = preg_replace('/\&amp\;$/', '', (string) $url);
        }
        return $url;
    }
    /**
     * Template variable getter. Returns page navigation
     */
    public function get_page_navigation()
    {
    }
    /**
     * Template variable getter. Returns page navigation with default 7 positions
     *
     * @param int $positionCount Paging positions count ( 0 - unlimited )
     *
     * @return StdClass
     */
    public function get_page_navigation_limited_top($position_count = 7)
    {
        return $this->_o_page_navigation = $this->generate_page_navigation($position_count);
    }
    /**
     * Template variable getter. Returns page navigation with default 11 positions
     *
     * @param int $positionCount Paging positions count ( 0 - unlimited )
     *
     * @return StdClass
     */
    public function get_page_navigation_limited_bottom($position_count = 11)
    {
        return $this->_o_page_navigation = $this->generate_page_navigation($position_count);
    }
    /**
     * Generates variables for page navigation
     *
     * @param int $positionCount Paging positions count ( 0 - unlimited )
     *
     * @return StdClass Object with page navigation data
     */
    public function generate_page_navigation($position_count = 0)
    {
        start_profile('generatePageNavigation');
        $page_navigation = new Std_Class();
        $page_navigation->nr_of_pages = $this->_i_cnt_pages;
        $active_page = $this->get_act_page();
        $page_navigation->act_page = $active_page + 1;
        $url = $this->generate_page_navigation_url();
        if ($position_count == 0 || $position_count >= $page_navigation->nr_of_pages) {
            $start_no = 2;
            $finish_no = $page_navigation->nr_of_pages;
        } else {
            $tmp_val = $position_count - 3;
            $tmp_val2 = floor(($position_count - 4) / 2);
            // actual page is at the start
            if ($page_navigation->act_page <= $tmp_val) {
                $start_no = 2;
                $finish_no = $tmp_val + 1;
                // actual page is at the end
            } elseif ($page_navigation->act_page >= $page_navigation->nr_of_pages - $tmp_val + 1) {
                $start_no = $page_navigation->nr_of_pages - $tmp_val;
                $finish_no = $page_navigation->nr_of_pages - 1;
                // actual page is in the middle
            } else {
                $start_no = $page_navigation->act_page - $tmp_val2;
                $finish_no = $page_navigation->act_page + $tmp_val2;
            }
        }
        $page_navigation->previous_page = null;
        if ($active_page > 0) {
            $page_navigation->previous_page = $this->add_page_nr_param($url, $active_page - 1);
        }
        $page_navigation->next_page = null;
        if ($active_page < $page_navigation->nr_of_pages - 1) {
            $page_navigation->next_page = $this->add_page_nr_param($url, $active_page + 1);
        }
        if ($page_navigation->nr_of_pages > 1) {
            for ($i = 1; $i < $page_navigation->nr_of_pages + 1; $i++) {
                if ($i == 1 || $i == $page_navigation->nr_of_pages || $i >= $start_no && $i <= $finish_no) {
                    $page = new stdClass();
                    $page->url = $this->add_page_nr_param($url, $i - 1);
                    $page->selected = $i == $page_navigation->act_page ? 1 : 0;
                    $page_navigation->change_page[$i] = $page;
                }
            }
            // first/last one
            $page_navigation->firstpage = $this->add_page_nr_param($url, 0);
            $page_navigation->lastpage = $this->add_page_nr_param($url, $page_navigation->nr_of_pages - 1);
        }
        stop_profile('generatePageNavigation');
        return $page_navigation;
    }
    /**
     * While ordering disables navigation controls if \OxidEsales\Eshop\Core\Config::blDisableNavBars
     * is on and executes parent::render()
     *
     * @return string
     */
    public function render()
    {
        foreach (array_keys($this->_oa_components) as $component_name) {
            $this->_a_view_data[$component_name] = $this->_oa_components[$component_name]->render();
        }
        parent::render();
        if ($this->get_is_order_step()) {
            // disabling navigation during order ...
            if (Registry::get_config()->get_config_param('blDisableNavBars')) {
                $this->_i_news_real_status = 1;
                $this->set_show_newsletter(0);
            }
        }
        $this->add_list_id_and_widget_id_to_view_data();
        $config = Registry::get_config();
        $this->_a_view_data['defaultLang'] = $config->get_config_param('sDefaultLang');
        $this->_a_view_data['shopURLParam'] = Container_Facade::get_parameter('oxid_esales.shop_url');
        return $this->_s_this_template;
    }
    private function add_list_id_and_widget_id_to_view_data(): void
    {
        $config = Registry::get_config();
        $class_name = Registry::get_request()->get_request_escaped_parameter('actcl');
        $list_id = null;
        $widget_id = null;
        if ($class_name === 'start' && $config->get_config_param('blEcondaRecommendationsStart')) {
            $list_id = 'recommendationsStart';
            $widget_id = $config->get_config_param('sEcondaWidgetIdStart');
        } elseif ($class_name === 'alist' && $config->get_config_param('blEcondaRecommendationsList')) {
            $list_id = 'recommendationsList';
            $widget_id = $config->get_config_param('sEcondaWidgetIdList');
        } elseif ($class_name === 'details' && $config->get_config_param('blEcondaRecommendationsDetails')) {
            $list_id = 'recommendationsDetails';
            $widget_id = $config->get_config_param('sEcondaWidgetIdDetails');
        } elseif ($class_name === 'basket' && $config->get_config_param('blEcondaRecommendationsBasket')) {
            $list_id = 'recommendationsBasket';
            $widget_id = $config->get_config_param('sEcondaWidgetIdBasket');
        }
        $this->_a_view_data['sListId'] = $list_id;
        $this->_a_view_data['sWidgetId'] = $widget_id;
    }
    /**
     * Returns current view product object (if it is loaded)
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_view_product()
    {
        return $this->get_product();
    }
    /**
     * Sets view product
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $product view product object
     */
    public function set_view_product($product): void
    {
        $this->_o_product = $product;
    }
    /**
     * Returns view product list
     *
     * @return array
     */
    public function get_view_product_list()
    {
        return $this->_a_article_list;
    }
    /**
     * Active page getter
     *
     * @return int
     */
    public function get_act_page()
    {
        if ($this->_i_act_page === null) {
            $this->_i_act_page = (int) Registry::get_request()->get_request_escaped_parameter(self::PAGE_NUMBER);
            $this->_i_act_page = $this->_i_act_page < 0 ? 0 : $this->_i_act_page;
        }
        return $this->_i_act_page;
    }
    /**
     * Returns active vendor set by categories component; if vendor is
     * not set by component - will create vendor object and will try to
     * load by id passed by request
     *
     * @return \OxidEsales\Eshop\Application\Model\Vendor
     */
    public function get_act_vendor()
    {
        // if active vendor is not set yet - trying to load it from request params
        // this may be useful when category component was unable to load active vendor
        // and we still need some object to mount navigation info
        if ($this->_o_act_vendor === null) {
            $this->_o_act_vendor = false;
            $vendor_id = Registry::get_request()->get_request_escaped_parameter(self::CATEGORY_ID);
            $vendor_id = $vendor_id ? str_replace('v_', '', $vendor_id) : $vendor_id;
            $vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
            if ($vendor->load($vendor_id)) {
                $this->_o_act_vendor = $vendor;
            }
        }
        return $this->_o_act_vendor;
    }
    /**
     * Returns active Manufacturer set by categories component; if Manufacturer is
     * not set by component - will create Manufacturer object and will try to
     * load by id passed by request
     *
     * @return \OxidEsales\Eshop\Application\Model\Manufacturer
     */
    public function get_act_manufacturer()
    {
        // if active Manufacturer is not set yet - trying to load it from request params
        // this may be useful when category component was unable to load active Manufacturer
        // and we still need some object to mount navigation info
        if ($this->_o_act_manufacturer === null) {
            $this->_o_act_manufacturer = false;
            $manufacturer_id = Registry::get_request()->get_request_escaped_parameter(self::MANUFACTURER_ID);
            $manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
            if ($manufacturer->load($manufacturer_id)) {
                $this->_o_act_manufacturer = $manufacturer;
            }
        }
        return $this->_o_act_manufacturer;
    }
    /**
     * Active vendor setter
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor active vendor
     */
    public function set_act_vendor($vendor): void
    {
        $this->_o_act_vendor = $vendor;
    }
    /**
     * Active Manufacturer setter
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $manufacturer active Manufacturer
     */
    public function set_act_manufacturer($manufacturer): void
    {
        $this->_o_act_manufacturer = $manufacturer;
    }
    /**
     * Returns fake object which is used to mount navigation info
     *
     * @return stdClass
     */
    public function get_act_search()
    {
        if ($this->_o_act_search === null) {
            $this->_o_act_search = new stdClass();
            $url = Registry::get_config()->get_shop_home_url();
            $this->_o_act_search->link = "{$url}cl=search";
        }
        return $this->_o_act_search;
    }
    /**
     * Returns category tree (if it is loaded)
     *
     * @return \OxidEsales\Eshop\Application\Model\CategoryList
     */
    public function get_category_tree()
    {
        return $this->_o_category_tree;
    }
    /**
     * Category list setter
     *
     * @param \OxidEsales\Eshop\Application\Model\CategoryList $categoryTree category tree
     */
    public function set_category_tree($category_tree): void
    {
        $this->_o_category_tree = $category_tree;
    }
    /**
     * Returns Manufacturer tree (if it is loaded0
     *
     * @return \OxidEsales\Eshop\Application\Model\ManufacturerList
     */
    public function get_manufacturer_tree()
    {
        return $this->_o_manufacturer_tree;
    }
    /**
     * Manufacturer tree setter
     *
     * @param \OxidEsales\Eshop\Application\Model\ManufacturerList $manufacturerTree Manufacturer tree
     */
    public function set_manufacturer_tree($manufacturer_tree): void
    {
        $this->_o_manufacturer_tree = $manufacturer_tree;
    }
    /**
     * Returns additional URL parameters which must be added to list products urls
     */
    public function get_add_url_params()
    {
    }
    /**
     * Template variable getter. Returns Top 5 article list.
     * Parameter \OxidEsales\Eshop\Application\Controller\FrontendController::$_blTop5Action must be set to true.
     *
     * @param integer $count Product count in list
     *
     * @return array
     */
    public function get_top5article_list($count = null)
    {
        if ($this->_bl_top5action) {
            if ($this->_a_top5article_list === null) {
                $this->_a_top5article_list = false;
                $config = Registry::get_config();
                if ($config->get_config_param('bl_perfLoadAktion')) {
                    // top 5 articles
                    $art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
                    $art_list->load_top5articles($count);
                    if ($art_list->count()) {
                        $this->_a_top5article_list = $art_list;
                    }
                }
            }
        }
        return $this->_a_top5article_list;
    }
    /**
     * Template variable getter. Returns bargain article list
     * Parameter \OxidEsales\Eshop\Application\Controller\FrontendController::$_blBargainAction must be set to true.
     *
     * @return array
     */
    public function get_bargain_article_list()
    {
        if ($this->_bl_bargain_action) {
            if ($this->_a_bargain_article_list === null) {
                $this->_a_bargain_article_list = [];
                if (Registry::get_config()->get_config_param('bl_perfLoadAktion')) {
                    $article_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
                    $article_list->load_action_articles('OXBARGAIN');
                    if ($article_list->count()) {
                        $this->_a_bargain_article_list = $article_list;
                    }
                }
            }
        }
        return $this->_a_bargain_article_list;
    }
    /**
     * Template variable getter. Returns if order price is lower than
     * minimum order price setup (config param "iMinOrderPrice")
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; use oxBasket method
     *
     * @return bool
     */
    public function is_low_order_price()
    {
        $session = Registry::get_session();
        if ($this->_bl_low_order_price === null && $basket = $session->get_basket()) {
            $this->_bl_low_order_price = $basket->is_below_min_order_price();
        }
        return $this->_bl_low_order_price;
    }
    /**
     * Template variable getter. Returns formatted min order price value
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; use oxBasket method
     *
     * @return string
     */
    public function get_min_order_price()
    {
        if ($this->_s_min_order_price === null && $this->is_low_order_price()) {
            $min_order_price = Price::get_price_in_act_currency(Registry::get_config()->get_config_param('iMinOrderPrice'));
            $this->_s_min_order_price = Registry::get_lang()->format_currency($min_order_price);
        }
        return $this->_s_min_order_price;
    }
    /**
     * Template variable getter. Returns if newsletter is really active (for "user" template)
     *
     * @return integer
     */
    public function get_news_real_status()
    {
        return $this->_i_news_real_status;
    }
    /**
     * Checks if current request parameters does not block SEO redirection process
     *
     * @return bool
     */
    protected function can_redirect()
    {
        foreach ($this->_a_block_redirect_params as $param) {
            if (Registry::get_request()->get_request_escaped_parameter($param) !== null) {
                return false;
            }
        }
        return true;
    }
    /**
     * Empty active product getter
     */
    public function get_product()
    {
    }
    /**
     * Template variable getter. Returns Manufacturer list for search
     *
     * @return array
     */
    public function get_manufacturer_list()
    {
        return $this->_a_manufacturerlist;
    }
    /**
     * Sets Manufacturer list for search
     *
     * @param array $list manufacturer list
     */
    public function set_manufacturer_list($list): void
    {
        $this->_a_manufacturerlist = $list;
    }
    /**
     * Sets root vendor
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor vendor object
     */
    public function set_root_vendor($vendor): void
    {
        $this->_o_root_vendor = $vendor;
    }
    /**
     * Template variable getter. Returns root vendor
     *
     * @return \OxidEsales\Eshop\Application\Model\Vendor
     */
    public function get_root_vendor()
    {
        return $this->_o_root_vendor;
    }
    /**
     * Sets root Manufacturer
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $manufacturer manufacturer object
     */
    public function set_root_manufacturer($manufacturer): void
    {
        $this->_o_root_manufacturer = $manufacturer;
    }
    /**
     * Template variable getter. Returns root Manufacturer
     *
     * @return \OxidEsales\Eshop\Application\Model\Manufacturer
     */
    public function get_root_manufacturer()
    {
        return $this->_o_root_manufacturer;
    }
    /**
     * Template variable getter. Returns vendor id
     *
     * @return string
     */
    public function get_vendor_id()
    {
        if ($this->_s_vendor_id === null) {
            $this->_s_vendor_id = false;
            if ($vendor = $this->get_act_vendor()) {
                $this->_s_vendor_id = $vendor->get_id();
            }
        }
        return $this->_s_vendor_id;
    }
    /**
     * Template variable getter. Returns Manufacturer id
     *
     * @return string
     */
    public function get_manufacturer_id()
    {
        if ($this->_s_manufacturer_id === null) {
            $this->_s_manufacturer_id = false;
            if ($manufacturer = $this->get_act_manufacturer()) {
                $this->_s_manufacturer_id = $manufacturer->get_id();
            }
        }
        return $this->_s_manufacturer_id;
    }
    /**
     * Template variable getter. Returns more category
     *
     * @deprecated will be removed in v8.0
     *
     * @return object
     */
    public function get_cat_more_url()
    {
        return Registry::get_config()->get_shop_home_url() . 'cnid=oxmore';
    }
    /**
     * Template variable getter. Returns category path
     *
     * @return array
     */
    public function get_cat_tree_path()
    {
        return $this->_s_cat_tree_path;
    }
    /**
     * Loads and returns oxContent object requested by its ident
     *
     * @param string $ident content identifier
     *
     * @return \OxidEsales\Eshop\Application\Model\Content
     */
    public function get_content_by_ident($ident)
    {
        if (!isset($this->_a_contents[$ident])) {
            $this->_a_contents[$ident] = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
            $this->_a_contents[$ident]->load_by_ident($ident);
        }
        return $this->_a_contents[$ident];
    }
    /**
     * Default content category getter, returns FALSE by default
     *
     * @return bool
     */
    public function get_content_category()
    {
        return false;
    }
    /**
     * Returns array of fields which must be filled during registration
     *
     * @return array|bool
     */
    public function get_must_fill_fields()
    {
        if ($this->_a_must_fill_fields === null) {
            $this->_a_must_fill_fields = false;
            // passing must-be-filled-fields info
            $must_fill_fields = Registry::get_config()->get_config_param('aMustFillFields');
            if (is_array($must_fill_fields)) {
                $this->_a_must_fill_fields = array_flip($must_fill_fields);
            }
        }
        return $this->_a_must_fill_fields;
    }
    /**
     * Returns if field is required.
     *
     * @param string $field required field to check
     *
     * @return array|bool
     */
    public function is_field_required($field)
    {
        return isset($this->get_must_fill_fields()[$field]);
    }
    /**
     * Form id getter. This id used to prevent double review entry submit
     *
     * @return string
     */
    public function get_form_id()
    {
        if ($this->_s_form_id === null) {
            $this->_s_form_id = Registry::get_utils_object()->generate_u_id();
            Registry::get_session()->set_variable('sessionuformid', $this->_s_form_id);
        }
        return $this->_s_form_id;
    }
    /**
     * Checks if session form id matches with request form id
     *
     * @return bool
     */
    public function can_accept_form_data()
    {
        if ($this->_bl_can_accept_form_data === null) {
            $this->_bl_can_accept_form_data = false;
            $form_id = Registry::get_request()->get_request_escaped_parameter('uformid');
            $session_form_id = Registry::get_session()->get_variable('sessionuformid');
            // testing if form and session ids matches
            if ($form_id && $form_id === $session_form_id) {
                $this->_bl_can_accept_form_data = true;
            }
            // regenerating form data
            $this->get_form_id();
        }
        return $this->_bl_can_accept_form_data;
    }
    /**
     * return last finished promotion list
     *
     * @return \OxidEsales\Eshop\Application\Model\ActionList
     */
    public function get_promo_finished_list()
    {
        if (isset($this->_o_promo_finished_list)) {
            return $this->_o_promo_finished_list;
        }
        $this->_o_promo_finished_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Action_List::class);
        $this->_o_promo_finished_list->load_finished_by_count(2);
        return $this->_o_promo_finished_list;
    }
    /**
     * return current promotion list
     *
     * @return \OxidEsales\Eshop\Application\Model\ActionList
     */
    public function get_promo_current_list()
    {
        if (isset($this->_o_promo_current_list)) {
            return $this->_o_promo_current_list;
        }
        $this->_o_promo_current_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Action_List::class);
        $this->_o_promo_current_list->load_current();
        return $this->_o_promo_current_list;
    }
    /**
     * return future promotion list
     *
     * @return \OxidEsales\Eshop\Application\Model\ActionList
     */
    public function get_promo_future_list()
    {
        if (isset($this->_o_promo_future_list)) {
            return $this->_o_promo_future_list;
        }
        $this->_o_promo_future_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Action_List::class);
        $this->_o_promo_future_list->load_future_by_count(2);
        return $this->_o_promo_future_list;
    }
    /**
     * should promotions list be shown?
     *
     * @return bool
     */
    public function get_show_promotion_list()
    {
        if (isset($this->_bl_show_promotions)) {
            return $this->_bl_show_promotions;
        }
        $this->_bl_show_promotions = false;
        if (ox_new(\Oxid_Esales\Eshop\Application\Model\Action_List::class)->are_any_active_promotions()) {
            $this->_bl_show_promotions = count($this->get_promo_finished_list()) + count($this->get_promo_current_list()) + count($this->get_promo_future_list()) > 0;
        }
        return $this->_bl_show_promotions;
    }
    /**
     * Checks if private sales is on
     *
     * @return bool
     */
    public function is_enabled_private_sales()
    {
        if ($this->_bl_enabled_private_sales === null) {
            $this->_bl_enabled_private_sales = (bool) Registry::get_config()->get_config_param('blPsLoginEnabled');
            if ($this->_bl_enabled_private_sales && ($can_preview = Registry::get_utils()->can_preview()) !== null) {
                $this->_bl_enabled_private_sales = !$can_preview;
            }
        }
        return $this->_bl_enabled_private_sales;
    }
    /**
     * Returns input field validation error array (if available)
     *
     * @return array
     */
    public function get_field_validation_errors()
    {
        return Registry::get_input_validator()->get_field_validation_errors();
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     */
    public function get_bread_crumb()
    {
        return null;
    }
    /**
     * Sets if active root category was changed
     *
     * @param bool $rootCategoryChanged root category changed
     */
    public function set_root_cat_changed($root_category_changed): void
    {
        $this->_bl_root_cat_changed = $root_category_changed;
    }
    /**
     * Template variable getter. Returns true if active root category was changed
     *
     * @return bool
     */
    public function is_root_cat_changed()
    {
        return $this->_bl_root_cat_changed;
    }
    /**
     * Template variable getter. Returns user address
     *
     * @return array
     */
    public function get_invoice_address()
    {
        if ($this->_a_invoice_address == null) {
            $invoice_address = Registry::get_request()->get_request_escaped_parameter('invadr');
            if ($invoice_address) {
                $this->_a_invoice_address = $invoice_address;
            }
        }
        return $this->_a_invoice_address;
    }
    /**
     * Template variable getter. Returns user delivery address
     *
     * @return array
     */
    public function get_delivery_address()
    {
        if ($this->_a_delivery_address == null) {
            $config = Registry::get_config();
            //do not show deladr if address was reloaded
            if (!Registry::get_request()->get_request_escaped_parameter('reloadaddress')) {
                $this->_a_delivery_address = Registry::get_request()->get_request_escaped_parameter('deladr');
            }
        }
        return $this->_a_delivery_address;
    }
    /**
     * Template variable setter. Sets user delivery address
     *
     * @param array $deliveryAddress delivery address
     */
    public function set_delivery_address($delivery_address): void
    {
        $this->_a_delivery_address = $delivery_address;
    }
    /**
     * Template variable setter. Sets user address
     *
     * @param array $address user address
     */
    public function set_invoice_address($address): void
    {
        $this->_a_invoice_address = $address;
    }
    /**
     * Template variable getter. Returns logged in user name
     *
     * @return string
     */
    public function get_active_username()
    {
        if ($this->_s_active_username == null) {
            $this->_s_active_username = false;
            $username = Registry::get_request()->get_request_escaped_parameter('lgn_usr');
            if ($username) {
                $this->_s_active_username = $username;
            } elseif ($user = $this->get_user()) {
                $this->_s_active_username = $user->oxuser__oxusername->value;
            }
        }
        return $this->_s_active_username;
    }
    /**
     * Template variable getter. Returns user id from wish list
     *
     * @return string
     */
    public function get_wishlist_user_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('wishid');
    }
    /**
     * Template variable getter. Returns searched category id
     */
    public function get_search_cat_id()
    {
    }
    /**
     * Template variable getter. Returns searched vendor id
     */
    public function get_search_vendor()
    {
    }
    /**
     * Template variable getter. Returns searched Manufacturer id
     */
    public function get_search_manufacturer()
    {
    }
    /**
     * Template variable getter. Returns last seen products
     */
    public function get_last_products()
    {
    }
    /**
     * Returns added basket item notification message type
     *
     * @return int
     */
    public function get_new_basket_item_msg_type()
    {
        return (int) Registry::get_config()->get_config_param('iNewBasketItemMessage');
    }
    /**
     * Checks if feature is enabled
     *
     * @param string $name feature name
     *
     * @return bool
     */
    public function is_active($name)
    {
        return Registry::get_config()->get_config_param('bl' . $name . 'Enabled');
    }
    /**
     * Checks if downloadable files are turned on
     *
     * @return bool
     */
    public function is_enabled_downloadable_files()
    {
        return (bool) Registry::get_config()->get_config_param('blEnableDownloads');
    }
    /**
     * Returns true if "Remember me" are ON
     *
     * @return boolean
     */
    public function show_remember_me()
    {
        return (bool) Registry::get_config()->get_config_param('blShowRememberMe');
    }
    /**
     * Returns true if articles shown in shop with VAT.
     * Checks country VAT and options (show vat only in basket and check if b2b mode is activated).
     *
     * @return boolean
     */
    public function is_vat_included()
    {
        if ($this->_bl_is_vat_included !== null) {
            return $this->_bl_is_vat_included;
        }
        $config = Registry::get_config();
        /*
         * Do not show "inclusive VAT" when:
         *
         *   B2B mode is activated
         * OR
         *   the VAT will only be calculated in the basket
         * OR
         *   the country does not bill VAT
         *
         * oxcountry__oxvatstatus: Vat status: 0 - Do not bill VAT, 1 - Do not bill VAT only if provided valid VAT ID
         * if country is not available (no session) oxvatstatus->value will return null
         */
        if ($config->get_config_param('blShowNetPrice') || $config->get_config_param('bl_perfCalcVatOnlyForBasketOrder')) {
            return $this->_bl_is_vat_included = false;
        }
        $user = $this->get_user();
        if ($user !== false) {
            if ($user->get_field_data('oxustid') && $user->get_field_data('oxustidstatus') == 1) {
                return $this->_bl_is_vat_included = false;
            }
        } else {
            $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        }
        $active_country = $user->get_active_country();
        if ($active_country !== '') {
            $country = ox_new(\Oxid_Esales\Eshop\Application\Model\Country::class);
            if ($country->load($active_country) && $country->oxcountry__oxvatstatus->value !== null && $country->oxcountry__oxvatstatus->value == 0) {
                return $this->_bl_is_vat_included = false;
            }
        }
        return $this->_bl_is_vat_included = true;
    }
    /**
     * Returns true if price calculation is activated
     *
     * @return boolean
     */
    public function is_price_calculated()
    {
        return (bool) Registry::get_config()->get_config_param('bl_perfLoadPrice');
    }
    /**
     * Template variable getter. Returns user name of searched wishlist
     *
     * @return string
     */
    public function get_wishlist_name()
    {
        if ($this->get_user()) {
            $wish_id = Registry::get_request()->get_request_escaped_parameter('wishid');
            $user_id = $wish_id ?: Registry::get_session()->get_variable('wishid');
            if ($user_id) {
                $wish_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
                if ($wish_user->load($user_id)) {
                    return $wish_user;
                }
            }
        }
        return false;
    }
    /**
     * Get widget link for Ajax calls
     *
     * @return string
     */
    public function get_widget_link()
    {
        return Registry::get_config()->get_widget_url();
    }
    /**
     * Template variable getter. Returns article list count in comparison.
     *
     * @return integer
     */
    public function get_compare_items_cnt()
    {
        $compare_controller = ox_new(\Oxid_Esales\Eshop\Application\Controller\Compare_Controller::class);
        return $compare_controller->get_compare_items_cnt();
    }
    /**
     * @param string $sortOrder
     *
     * @return array
     */
    private function is_allowed_sorting_order($sort_order): bool
    {
        $allowed_sort_orders = array_merge((new Sorting_Validator())->get_sorting_orders(), ['']);
        return in_array(strtolower($sort_order), $allowed_sort_orders);
    }
    private function replace_double_quotes_with_html_characters(string $title): string
    {
        return str_replace('"', '&quot;', $title);
    }
    private function append_value(string $param): string
    {
        $value = Registry::get_request()->get_request_escaped_parameter($param);
        return $value ? "&amp;{$param}={$value}" : '';
    }
    private function append_basename_value(string $param): string
    {
        $value = Registry::get_request()->get_request_escaped_parameter($param);
        return $value ? "&amp;{$param}=" . basename((string) $value) : '';
    }
    private function append_unescaped_encoded_value(string $param): string
    {
        $value = Registry::get_request()->get_request_parameter($param);
        return $value ? "&amp;{$param}=" . rawurlencode((string) $value) : '';
    }
    private function append_unescaped_value(string $param): string
    {
        $value = Registry::get_request()->get_request_parameter($param);
        return $value ? "&amp;{$param}={$value}" : '';
    }
}