<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Exception;
use Oxid_Esales\Eshop\Application\Model\Contract\Article_Interface;
use Oxid_Esales\Eshop\Core\Contract\I_Url;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Model\Multi_Language_Model;
use Oxid_Esales\Eshop\Core\Price;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Database_Provider;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Dao\Product_Media_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_View;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service\Product_Media_View_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\After_Model_Update_Event;
// defining supported link types
define('OXARTICLE_LINKTYPE_CATEGORY', 0);
define('OXARTICLE_LINKTYPE_VENDOR', 1);
define('OXARTICLE_LINKTYPE_MANUFACTURER', 2);
define('OXARTICLE_LINKTYPE_PRICECATEGORY', 3);
// @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
define('OXARTICLE_LINKTYPE_RECOMM', 5);
// END deprecated
/**
 * Article manager.
 * Creates fully detailed article object, with such information as VAT,
 * discounts, etc.
 */
class Article extends Multi_Language_Model implements Article_Interface, I_Url
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxarticle';
    /**
     * Set $_blUseLazyLoading to true if you want to load only actually used fields not full object, depending on views.
     *
     * @var bool
     */
    protected $_bl_use_lazy_loading = true;
    /**
     * item key the usage with oxuserbasketitem
     *
     * @var string (md5 hash)
     */
    protected $_s_item_key;
    /**
     * Variable controls price calculation type (set true, to calculate price
     * with taxes and etc, or false to return base article price).
     *
     * @var bool
     */
    protected $_bl_calc_price = true;
    /**
     * Article oxPrice object.
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_price;
    /**
     * cached article variant min price
     *
     * @var double|null
     */
    protected $_d_var_min_price;
    /**
     * cached article variant max price
     *
     * @var double|null
     */
    protected $_d_var_max_price;
    /**
     * caches article vat
     *
     * @var double|null
     */
    protected $_d_article_vat;
    /**
     * Status of article - buyable/not buyable.
     *
     * @var bool
     */
    protected $_bl_not_buyable = false;
    /**
     * Indicates if we should load variants for current article. When $_blLoadVariants is set to false then
     * neither simple nor full variants for this article are loaded.
     *
     * @var bool
     */
    protected $_bl_load_variants = true;
    /**
     * Article variants without empty stock, not orderable flagged variants
     *
     * @var array
     */
    protected $_a_variants;
    /**
     * Article variants with empty stock, not orderable flagged variants
     *
     * @var array
     */
    protected $_a_variants_with_not_orderables;
    /**
     * $_blNotBuyableParent is set to true, when article has variants and is not buyable due to:
     *      a) config option
     *      b) it is not active
     *      c) all variants are not active
     *
     * @var bool
     */
    protected $_bl_not_buyable_parent = false;
    /**
     * $_blHasVariants is set to true if article has any variants.
     */
    protected $_bl_has_variants = false;
    /**
     * $_blHasVariants is set to true if article has multidimensional variants.
     */
    protected $_bl_has_md_variants = false;
    /**
     * If set true, then this object is on comparison list
     *
     * @var bool
     */
    protected $_bl_is_on_comparison_list = false;
    /**
     * user object
     *
     * @var \OxidEsales\Eshop\Application\Model\User
     */
    protected $_o_user;
    /**
     * Performance issue. Sometimes you want to load articles without calculating
     * correct discounts and prices etc.
     *
     * @var bool
     */
    protected $_bl_load_price = true;
    /**
     * $_fPricePerUnit holds price per unit value in active shop currency.
     * $_fPricePerUnit is calculated from
     * \OxidEsales\Eshop\Application\Model\Article::oxarticles__oxunitquantity->value
     * and from \OxidEsales\Eshop\Application\Model\Article::oxarticles__oxuniname->value. If either one of these
     * values is empty then $_fPricePerUnit is not calculated. Example: In case when product price is 10 EUR and
     * product quantity is 0.5 (liters) then $_fPricePerUnit would be 20,00
     */
    protected $_f_price_per_unit;
    /**
     * Variable used to force load parent data in export
     */
    protected $_bl_load_parent_data = false;
    /**
     * Variable used to determine if setting parentId to empty value is allowed
     */
    protected $_bl_allow_empty_parent_id = false;
    /**
     * Variable used to force load parent data in export
     */
    protected $_bl_skip_assign = false;
    /**
     * Set $_blSkipDiscounts to true if you want to skip the discount.
     *
     * @var bool
     */
    protected $_bl_skip_discounts;
    /**
     * Object holding the list of attributes and attribute values associated with this article
     * @var \OxidEsales\Eshop\Application\Model\AttributeList
     */
    protected $_o_attribute_list;
    /**
     * Object holding the list of attributes and attribute values associated with this article and displayable in basket
     * @var \OxidEsales\Eshop\Application\Model\AttributeList
     */
    protected $basket_attribute_list;
    /**
     * Indicates whether the price is "From" price
     *
     * @var bool
     */
    protected $_bl_is_range_price;
    /**
     * The list of article media URLs
     *
     * @var string
     */
    protected $_a_media_urls;
    /**
     * Array containing references to already loaded parent articles, in order for variant to skip parent data loading
     *
     * @var array
     */
    protected static $_a_loaded_parents;
    /**
     * Cached select lists array
     *
     * @var array
     */
    protected static $_a_sel_list;
    /**
     * Select lists for tpl
     *
     * @var array
     */
    protected $_a_disp_sel_list;
    /**
     * Marks that current object is managed by SEO
     *
     * @var bool
     */
    protected $_bl_is_seo_object = true;
    /**
     * loaded amount prices
     *
     * @var \OxidEsales\Eshop\Application\Model\AmountPriceList
     */
    protected $_o_amount_price_list;
    /**
     * Article details link type (default is 0):
     *     0 - category link
     *     1 - vendor link
     *     2 - manufacturer link
     *
     * @var int
     */
    protected $_i_link_type = 0;
    /**
     * Standard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_a_std_urls = [];
    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_a_seo_urls = [];
    /**
     * Additional parameters to seo urls
     *
     * @var array
     */
    protected $_a_seo_add_params = [];
    /**
     * Additional parameters to std urls
     *
     * @var array
     */
    protected $_a_std_add_params = [];
    /**
     * Image url
     *
     * @var string
     */
    protected $_s_dyn_image_dir;
    /**
     * More details link
     *
     * @var string
     */
    protected $_s_more_detail_link;
    /**
     * To basket link
     *
     * @var string
     */
    protected $_s_to_basket_link;
    /**
     * Article stock status when article is initially loaded.
     *
     * @var int
     */
    protected $_i_stock_status_on_load;
    /**
     * Article original parameters when loaded.
     *
     * @var array
     */
    protected $_a_sorting_fields_on_load = [];
    /**
     * Stock status
     *
     * @var integer
     */
    protected $_i_stock_status;
    /**
     * T price
     *
     * @var object
     */
    protected $_o_t_price;
    /**
     * Amount price list info
     *
     * @var object
     */
    protected $_o_amount_price_info;
    /**
     * Amount price
     *
     * @var double
     */
    protected $_d_amount_price;
    /**
     * Articles manufacturer ids cache
     *
     * @var array
     */
    protected static $_a_article_manufacturers = [];
    /**
     * Articles vendor ids cache
     *
     * @var array
     */
    protected static $_a_article_vendors = [];
    /**
     * Articles category ids cache
     *
     * @var array
     */
    protected static $_a_article_cats = [];
    /**
     * Do not copy certain parent fields to variant
     *
     * @var array
     */
    protected $_a_non_copy_parent_fields = ['oxarticles__oxinsert', 'oxarticles__oxtimestamp', 'oxarticles__oxnid', 'oxarticles__oxid', 'oxarticles__oxparentid'];
    /**
     * Override certain parent fields to variant
     *
     * @var array
     */
    protected $_a_copy_parent_field = ['oxarticles__oxnonmaterial', 'oxarticles__oxfreeshipping', 'oxarticles__oxisdownloadable', 'oxarticles__oxshowcustomagreement'];
    /**
     * Multidimensional variant tree structure
     *
     * @var \OxidEsales\Eshop\Application\Model\MdVariant
     */
    protected $_o_md_variants;
    /**
     * Product long description field
     *
     * @var \OxidEsales\Eshop\Core\Field
     */
    protected $_o_long_desc;
    /**
     * Variant selections array
     *
     * @see getVariantSelections()
     *
     * @var array
     */
    protected $_a_variant_selections = [];
    /**
     * Array of product selections
     *
     * @var array
     */
    protected static $_a_selections = [];
    /**
     * Category instance cache
     *
     * @var array
     */
    protected static $_a_category_cache = [];
    /**
     * stores if are stored any amount price
     *
     * @var bool
     */
    protected static $_bl_has_amount_price;
    /**
     * stores downloadable file list
     *
     * @var array|\OxidEsales\Eshop\Core\Model\ListModel
     */
    protected $_a_article_files;
    /**
     * If admin can edit any field.
     *
     * @var bool
     */
    protected $_bl_can_update_any_field;
    /**
     * Triggered action type
     *
     * @var integer
     */
    protected $action_type = ACTION_NA;
    /**
     * Constructor, sets shop ID for article (\OxidEsales\Eshop\Core\Config::getShopId()),
     * initiates parent constructor (parent::oxI18n()).
     *
     * @param array $aParams The array of names and values of oxArticle instance properties to be set on object
     *                       instantiation
     */
    public function __construct($a_params = null)
    {
        if ($a_params && is_array($a_params)) {
            foreach ($a_params as $s_param => $m_value) {
                $this->{$s_param} = $m_value;
            }
        }
        parent::__construct();
        $this->init('oxarticles');
    }
    /**
     * Magic getter, deals with values which are loaded on demand.
     * Additionally it sets default value for unknown picture fields
     *
     * @param string $sName Variable name
     *
     * @return mixed
     */
    public function __get($s_name)
    {
        $this->{$s_name} = parent::__get($s_name);
        if ($this->{$s_name}) {
            // since the field could have been loaded via lazy loading
            $this->assign_parent_field_value($s_name);
        }
        return $this->{$s_name};
    }
    /**
     * @param \OxidEsales\Eshop\Application\Model\AmountPriceList $amountPriceList
     */
    public function set_amount_price_list($amount_price_list): void
    {
        $this->_o_amount_price_list = $amount_price_list;
    }
    /**
     * @return \OxidEsales\Eshop\Application\Model\AmountPriceList
     */
    protected function get_amount_price_list()
    {
        return $this->_o_amount_price_list;
    }
    /**
     * Checks whether object is in list or not
     * It's needed for oxArticle so that it can pass this to widgets
     *
     * @return bool
     */
    public function is_in_list()
    {
        return parent::is_in_list();
    }
    /**
     * Sets object ID, additionally sets $this->oxarticles__oxnid field value
     *
     * @param string $sId New ID
     *
     * @return string|null
     */
    public function set_id($s_id = null)
    {
        $s_id = parent::set_id($s_id);
        // TODO: in BaseModel::setId make it to check if exists and update, not recreate, then delete this overload
        $this->oxarticles__oxnid = $this->oxarticles__oxid;
        return $s_id;
    }
    /**
     * Returns part of sql query used in active snippet. Query checks
     * if product "oxactive = 1". If config option "blUseTimeCheck" is TRUE
     * additionally checks if "oxactivefrom < current data < oxactiveto"
     *
     * @param bool $blForceCoreTable force core table usage
     *
     * @return string
     */
    public function get_active_check_query($bl_force_core_table = null)
    {
        $view_name = $this->get_view_name($bl_force_core_table);
        $query = " {$view_name}.oxactive = 1 ";
        $query .= " and {$view_name}.oxhidden = 0 ";
        if (Registry::get_config()->get_config_param('blUseTimeCheck')) {
            return $this->add_sql_active_range_snippet($query, $view_name);
        }
        return $query;
    }
    /**
     * Returns part of sql query used in active snippet. If config
     * option "blUseStock" is TRUE checks if "oxstockflag != 2 or
     * ( oxstock + oxvarstock ) > 0". If config option "blVariantParentBuyable"
     * is TRUE checks if product has variants, and if has - checks is
     * there at least one variant which is buyable. If config option
     * option "blUseTimeCheck" is TRUE additionally checks if variants
     * "oxactivefrom < current data < oxactiveto"
     *
     * @param bool $blForceCoreTable force core table usage
     *
     * @return string
     */
    public function get_stock_check_query($bl_force_core_table = null)
    {
        $my_config = Registry::get_config();
        $s_table = $this->get_view_name($bl_force_core_table);
        $s_q = '';
        //do not check for variants
        if ($my_config->get_config_param('blUseStock')) {
            $s_q = " and ( {$s_table}.oxstockflag != 2 or ( {$s_table}.oxstock + {$s_table}.oxvarstock ) > 0  ) ";
            //V #M513: When Parent article is not purchasable,
            // it's visibility should be displayed in shop only if any of Variants is available.
            if (!$my_config->get_config_param('blVariantParentBuyable')) {
                $active_check = 'art.oxactive = 1';
                if ($my_config->get_config_param('blUseTimeCheck')) {
                    $active_check = $this->add_sql_active_range_snippet($active_check, 'art');
                }
                $s_q = " {$s_q} and IF( {$s_table}.oxvarcount = 0, 1, ( select 1 from {$s_table} as art" . " where art.oxparentid={$s_table}.oxid and {$active_check} and" . ' ( art.oxstockflag != 2 or art.oxstock > 0 ) limit 1 ) ) ';
            }
        }
        return $s_q;
    }
    /**
     * Returns part of query which checks if product is variant of current
     * object. Additionally if config option "blUseStock" is TRUE checks
     * stock state "( oxstock > 0 or ( oxstock <= 0 and ( oxstockflag = 1
     * or oxstockflag = 4 ) )"
     *
     * @param bool $blRemoveNotOrderables remove or leave non orderable products
     * @param bool $blForceCoreTable      force core table usage
     *
     * @return string
     */
    public function get_variants_query($bl_remove_not_orderables, $bl_force_core_table = null)
    {
        $s_table = $this->get_view_name($bl_force_core_table);
        $s_q = " and {$s_table}.oxparentid = '" . $this->get_id() . "' ";
        //checking if variant is active and stock status
        if (Registry::get_config()->get_config_param('blUseStock')) {
            $s_q .= " and ( {$s_table}.oxstock > 0 or ( {$s_table}.oxstock <= 0 and {$s_table}.oxstockflag != 2 ";
            if ($bl_remove_not_orderables) {
                $s_q .= " and {$s_table}.oxstockflag != 3 ";
            }
            $s_q .= ' ) ) ';
        }
        return $s_q;
    }
    /**
     * Return unit quantity
     *
     * @return string
     */
    public function get_unit_quantity()
    {
        return $this->oxarticles__oxunitquantity->value;
    }
    /**
     * Return Size of product: length*width*height
     *
     * @return double
     */
    public function get_size()
    {
        return $this->oxarticles__oxlength->value * $this->oxarticles__oxwidth->value * $this->oxarticles__oxheight->value;
    }
    /**
     * Return product weight
     *
     * @return double
     */
    public function get_weight()
    {
        return $this->oxarticles__oxweight->value;
    }
    /**
     * Returns SQL select string with checks if items are available
     *
     * @param bool $blForceCoreTable forces core table usage (optional)
     *
     * @return string
     */
    public function get_sql_active_snippet($bl_force_core_table = null)
    {
        return "( {$this->create_sql_active_snippet($bl_force_core_table)} ) ";
    }
    /**
     *
     * Getter for action type.
     *
     * @return int
     */
    public function get_action_type()
    {
        return $this->action_type;
    }
    /**
     * Returns SQL select string with checks if items are available
     *
     * @param bool $forceCoreTable forces core table usage (optional)
     *
     * @return string
     */
    protected function create_sql_active_snippet($force_core_table)
    {
        // check if article is still active
        $s_q = $this->get_active_check_query($force_core_table);
        // stock and variants check
        $s_q .= $this->get_stock_check_query($force_core_table);
        return $s_q;
    }
    /**
     * Assign condition setter. In case article assignment is skipped ($_blSkipAssign = true), it does not perform
     * additional
     *
     * @param bool $blSkipAssign Whether to skip assign process for the article
     */
    public function set_skip_assign($bl_skip_assign): void
    {
        $this->_bl_skip_assign = $bl_skip_assign;
    }
    /**
     * Disables article price loading. Should be called before assign(), or load()
     */
    public function disable_price_load(): void
    {
        $this->_bl_load_price = false;
    }
    /**
     * Enable article price loading, if disabled.
     */
    public function enable_price_load(): void
    {
        $this->_bl_load_price = true;
    }
    /**
     * Returns item key used with oxuserbasket
     *
     * @return string
     */
    public function get_item_key()
    {
        return $this->_s_item_key;
    }
    /**
     * Sets item key used with oxuserbasket
     *
     * @param string $sItemKey Item key
     */
    public function set_item_key($s_item_key): void
    {
        $this->_s_item_key = $s_item_key;
    }
    /**
     * Disables/enables variant loading
     *
     * @param bool $blLoadVariants skip variant loading or not
     */
    public function set_no_variant_loading($bl_load_variants): void
    {
        $this->_bl_load_variants = !$bl_load_variants;
    }
    /**
     * Checks if article is buyable.
     *
     * @return bool
     */
    public function is_buyable()
    {
        return !($this->_bl_not_buyable_parent || $this->_bl_not_buyable);
    }
    /**
     * Checks if price alarm is enabled.
     *
     * @return bool
     */
    public function is_price_alarm()
    {
        // #419 disabling price alarm if article has fixed price
        return !(($this->__isset('oxarticles__oxblfixedprice') || $this->__get('oxarticles__oxblfixedprice')) && $this->__get('oxarticles__oxblfixedprice')->value);
    }
    /**
     * Checks whether article is inluded in comparison list
     *
     * @return bool
     */
    public function is_on_comparison_list()
    {
        return $this->_bl_is_on_comparison_list;
    }
    /**
     * Set if article is inluded in comparison list
     *
     * @param bool $blOnList Whether is article on the list
     */
    public function set_on_comparison_list($bl_on_list): void
    {
        $this->_bl_is_on_comparison_list = $bl_on_list;
    }
    /**
     * A setter for $_blLoadParentData (whether article parent info should be laoded fully) class variable
     *
     * @param bool $blLoadParentData Whether to load parent data
     */
    public function set_load_parent_data($bl_load_parent_data): void
    {
        $this->_bl_load_parent_data = $bl_load_parent_data;
    }
    /**
     * Getter for do we load parent data
     *
     * @return bool
     */
    public function get_load_parent_data()
    {
        return $this->_bl_load_parent_data;
    }
    /**
     * Returns true if the field is multilanguage
     *
     * @param string $sFieldName Field name
     *
     * @return bool
     */
    public function is_multilingual_field($s_field_name)
    {
        if ('oxlongdesc' == $s_field_name) {
            return true;
        }
        return parent::is_multilingual_field($s_field_name);
    }
    /**
     * Returns formatted price per unit
     *
     * @deprecated since v5.1 (2013-09-25); use oxPrice template engine plugin for formatting in templates
     * @return string
     */
    public function get_f_unit_price()
    {
        if ($this->_f_price_per_unit == null) {
            if ($o_price = $this->get_unit_price()) {
                if ($d_price = $this->get_price_for_view($o_price)) {
                    $this->_f_price_per_unit = Registry::get_lang()->format_currency($d_price);
                }
            }
        }
        return $this->_f_price_per_unit;
    }
    /**
     * Returns price per unit
     *
     * @return \OxidEsales\Eshop\Core\Price|null
     */
    public function get_unit_price()
    {
        // Performance
        if (!Registry::get_config()->get_config_param('bl_perfLoadPrice') || !$this->_bl_load_price) {
            return null;
        }
        $o_price = null;
        if ((float) $this->get_unit_quantity() && $this->oxarticles__oxunitname->value) {
            $o_price = clone $this->get_price();
            $o_price->divide((float) $this->get_unit_quantity());
        }
        return $o_price;
    }
    /**
     * Returns formatted article min price
     *
     * @deprecated since v5.1 (2013-10-04); use oxPrice template engine plugin for formatting in templates
     *
     * @return string
     */
    public function get_f_min_price()
    {
        $s_price = '';
        if ($o_price = $this->get_min_price()) {
            $d_price = $this->get_price_for_view($o_price);
            $s_price = Registry::get_lang()->format_currency($d_price);
        }
        return $s_price;
    }
    /**
     * Returns formatted min article variant price
     *
     * @deprecated since v5.1 (2013-10-04); use oxPrice template engine plugin for formatting in templates
     *
     * @return string
     */
    public function get_f_var_min_price()
    {
        $s_price = '';
        if ($o_price = $this->get_var_min_price()) {
            $d_price = $this->get_price_for_view($o_price);
            $s_price = Registry::get_lang()->format_currency($d_price);
        }
        return $s_price;
    }
    /**
     * Returns article min price of variants
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_var_min_price()
    {
        if (!Registry::get_config()->get_config_param('bl_perfLoadPrice') || !$this->_bl_load_price) {
            return null;
        }
        $o_price = null;
        $d_price = $this->calculate_var_min_price();
        $o_price = $this->get_price_object();
        $o_price->set_price($d_price);
        $this->calculate_price($o_price);
        return $o_price;
    }
    /**
     * Calculates lowest price of available article variants.
     *
     * @return double
     */
    protected function calculate_var_min_price()
    {
        $d_price = $this->get_var_min_raw_price();
        return $this->prepare_price($d_price, $this->get_article_vat());
    }
    /**
     * Returns article min price in calculation included variants
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_min_price()
    {
        if (!Registry::get_config()->get_config_param('bl_perfLoadPrice') || !$this->_bl_load_price) {
            return;
        }
        $o_price = null;
        $d_price = $this->get_raw_price();
        if ($this->get_var_min_raw_price() !== null && $d_price > $this->get_var_min_raw_price()) {
            $d_price = $this->get_var_min_raw_price();
        }
        $d_price = $this->prepare_modified_price($d_price);
        $o_price = $this->get_price_object();
        $o_price->set_price($d_price);
        $this->calculate_price($o_price);
        return $o_price;
    }
    /**
     * @param double $dPrice
     *
     * @return double
     */
    protected function prepare_modified_price($d_price)
    {
        return $this->prepare_price($d_price, $this->get_article_vat());
    }
    /**
     * Returns true if article has variant with different price
     *
     * @return bool
     */
    public function is_range_price()
    {
        if ($this->_bl_is_range_price === null) {
            $this->set_range_price(false);
            if ($this->has_any_variant()) {
                $d_price = $this->get_raw_price();
                $d_min_price = $this->get_var_min_raw_price();
                $d_max_price = $this->get_var_max_price();
                if ($d_min_price != $d_max_price) {
                    $this->set_range_price();
                } elseif (!$this->is_parent_not_buyable() && $d_price != $d_min_price) {
                    $this->set_range_price();
                }
            }
        }
        return $this->_bl_is_range_price;
    }
    /**
     * Setter to set if article has range price
     *
     * @param bool $blIsRangePrice - true if range, else false
     */
    public function set_range_price($bl_is_range_price = true)
    {
        return $this->_bl_is_range_price = $bl_is_range_price;
    }
    public function has_active_time_range(): bool
    {
        $active_from = $this->oxarticles__oxactivefrom->value;
        $active_to = $this->oxarticles__oxactiveto->value;
        $now = Registry::get_utils_date()->get_time();
        if (!$this->has_product_valid_time_range()) {
            return false;
        }
        return (Registry::get_utils_date()->is_empty_date($active_to) || strtotime((string) $active_to) >= $now) && (Registry::get_utils_date()->is_empty_date($active_from) || strtotime((string) $active_from) <= $now);
    }
    /**
     * Checks if article has visible status. Returns TRUE if its visible
     *
     * @return bool
     */
    public function is_visible()
    {
        // admin preview mode
        if (($bl_can_preview = Registry::get_utils()->can_preview()) !== null) {
            return $bl_can_preview;
        }
        $bl_use_time_check = Registry::get_config()->get_config_param('blUseTimeCheck');
        if (!$this->oxarticles__oxactive->value && ($bl_use_time_check && !$this->has_active_time_range() || !$bl_use_time_check)) {
            return false;
        }
        // stock flags
        if (Registry::get_config()->get_config_param('blUseStock') && $this->oxarticles__oxstockflag->value == 2) {
            $i_on_stock = $this->oxarticles__oxstock->value + $this->oxarticles__oxvarstock->value;
            if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
                $session = Registry::get_session();
                $i_on_stock += $session->get_basket_reservations()->get_reserved_amount($this->get_id());
            }
            if ($i_on_stock <= 0) {
                return false;
            }
        }
        return true;
    }
    /**
     * Assigns to oxarticle object some base parameters/values (such as
     * detaillink, moredetaillink, etc).
     *
     * @param array $aRecord Array representing current field values
     */
    public function assign($a_record): void
    {
        start_profile('articleAssign');
        // load object from database
        parent::assign($a_record);
        //clear seo urls
        $this->_a_seo_urls = [];
        $this->oxarticles__oxnid = $this->oxarticles__oxid;
        // check for simple article.
        if ($this->_bl_skip_assign) {
            return;
        }
        $this->assign_parent_field_values();
        $this->assign_not_buyable_parent();
        // assign only for a first load time
        if (!$this->is_loaded()) {
            $this->set_shop_values($this);
        }
        $this->assign_stock();
        $this->assign_dyn_image_dir();
        $this->assign_comparison_list_flag();
        stop_profile('articleAssign');
    }
    /**
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     */
    protected function set_shop_values($article)
    {
    }
    /**
     * Loads object data from DB (object data ID must be passed to method).
     * Converts dates (\OxidEsales\Eshop\Application\Model\Article::oxarticles__oxinsert)
     * to international format (oxUtils.php \OxidEsales\Eshop\Core\Registry::getUtilsDate()->formatDBDate(...)).
     * Returns true if article was loaded successfully.
     *
     * @param string $sOXID Article object ID
     *
     * @return bool
     */
    public function load($s_oxid)
    {
        // A. #1325 resetting to avoid problems when reloading (details etc)
        $this->_bl_not_buyable_parent = false;
        $a_data = $this->load_data($s_oxid);
        if ($a_data) {
            $this->assign($a_data);
            $this->save_sorting_field_values_on_load();
            $this->_i_stock_status_on_load = $this->_i_stock_status;
            $this->_is_loaded = true;
            return true;
        }
        return false;
    }
    /**
     * Loads data from database and returns it.
     *
     * @param string $articleId
     *
     * @return array
     */
    protected function load_data($article_id)
    {
        return $this->load_from_db($article_id);
    }
    /**
     * Checks whether sorting fields changed from last article loading.
     *
     * @return bool
     */
    public function has_sorting_fields_changed()
    {
        $a_sorting_fields = Registry::get_config()->get_config_param('aSortCols');
        $a_sorting_fields = !empty($a_sorting_fields) ? (array) $a_sorting_fields : [];
        $bl_changed = false;
        foreach ($a_sorting_fields as $s_field) {
            $s_parameter_name = 'oxarticles__' . $s_field;
            $current_value_of_field = $this->{$s_parameter_name} instanceof Field ? $this->{$s_parameter_name}->value : '';
            $value_of_field_on_load = $this->_a_sorting_fields_on_load[$s_parameter_name] ?? null;
            if ($value_of_field_on_load !== $current_value_of_field) {
                $bl_changed = true;
                break;
            }
        }
        return $bl_changed;
    }
    /**
     * Calculates and saves product rating average
     *
     * @param integer $rating new rating value
     */
    public function add_to_rating_average($rating): void
    {
        $d_old_rating = $this->oxarticles__oxrating->value;
        $d_old_cnt = $this->oxarticles__oxratingcnt->value;
        $this->oxarticles__oxrating->set_value(($d_old_rating * $d_old_cnt + $rating) / ($d_old_cnt + 1));
        $this->oxarticles__oxratingcnt->set_value($d_old_cnt + 1);
        $d_rating = ($d_old_rating * $d_old_cnt + $rating) / ($d_old_cnt + 1);
        $d_rating_cnt = (int) ($d_old_cnt + 1);
        // oxarticles.oxtimestamp = oxarticles.oxtimestamp to keep old timestamp value
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $query = 'update oxarticles
                  set oxarticles.oxrating = :oxrating,
                      oxarticles.oxratingcnt = :oxratingcnt,
                      oxarticles.oxtimestamp = oxarticles.oxtimestamp
                  where oxarticles.oxid = :oxid';
        $o_db->execute($query, ['oxrating' => $d_rating, 'oxratingcnt' => $d_rating_cnt, 'oxid' => $this->get_id()]);
    }
    /**
     * Set product rating average
     *
     * @param integer $iRating new rating value
     */
    public function set_rating_average($i_rating): void
    {
        $this->oxarticles__oxrating = new Field($i_rating);
    }
    /**
     * Set product rating count
     *
     * @param integer $iRatingCnt new rating count
     */
    public function set_rating_count($i_rating_cnt): void
    {
        $this->oxarticles__oxratingcnt = new Field($i_rating_cnt);
    }
    /**
     * Returns product rating average
     *
     * @param bool $blIncludeVariants - include variant ratings
     *
     * @return double
     */
    public function get_article_rating_average($bl_include_variants = false)
    {
        if (!$bl_include_variants) {
            return round($this->oxarticles__oxrating->value, 1);
        }
        $o_rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
        return $o_rating->get_rating_average($this->get_id(), 'oxarticle', $this->get_variant_ids());
    }
    /**
     * Returns product rating count
     *
     * @param bool $blIncludeVariants - include variant ratings
     *
     * @return int
     */
    public function get_article_rating_count($bl_include_variants = false)
    {
        if (!$bl_include_variants) {
            return $this->oxarticles__oxratingcnt->value;
        }
        $o_rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
        return $o_rating->get_rating_count($this->get_id(), 'oxarticle', $this->get_variant_ids());
    }
    /**
     * Collects user written reviews about an article.
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_reviews()
    {
        $a_ids = [$this->get_id()];
        if ($this->oxarticles__oxparentid->value) {
            $a_ids[] = $this->oxarticles__oxparentid->value;
        }
        // showing variant reviews ..
        if (Registry::get_config()->get_config_param('blShowVariantReviews')) {
            $a_add = $this->get_variant_ids();
            if (is_array($a_add)) {
                $a_ids = array_merge($a_ids, $a_add);
            }
        }
        $o_review = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
        $o_revs = $o_review->load_list('oxarticle', $a_ids);
        //if no review found, return null
        if ($o_revs->count() < 1) {
            return null;
        }
        return $o_revs;
    }
    /**
     * Loads and returns array with cross selling information.
     *
     * @return array
     */
    public function get_cross_selling()
    {
        $o_crosslist = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_crosslist->load_article_cross_sell($this->oxarticles__oxid->value);
        if ($o_crosslist->count()) {
            return $o_crosslist;
        }
    }
    /**
     * Loads and returns array with accessories information.
     *
     * @return array
     */
    public function get_accessoires()
    {
        $my_config = Registry::get_config();
        // Performance
        if (!$my_config->get_config_param('bl_perfLoadAccessoires')) {
            return;
        }
        $o_acclist = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_acclist->set_sql_limit(0, $my_config->get_config_param('iNrofCrossellArticles'));
        $o_acclist->load_article_accessoires($this->oxarticles__oxid->value);
        if ($o_acclist->count()) {
            return $o_acclist;
        }
    }
    /**
     * Returns a list of similar products.
     *
     * @return array
     */
    public function get_similar_products()
    {
        // Performance
        $my_config = Registry::get_config();
        if (!$my_config->get_config_param('bl_perfLoadSimilar')) {
            return;
        }
        // Check configured number of similar products (bug #6062)
        if ($my_config->get_config_param('iNrofSimilarArticles') < 1) {
            return;
        }
        $s_article_table = $this->get_view_name();
        $s_attribs = '';
        $i_cnt = 0;
        $this->get_attribs_string($s_attribs, $i_cnt);
        if (!$s_attribs) {
            return null;
        }
        $a_list = $this->get_sim_list($s_attribs, $i_cnt);
        if (count($a_list)) {
            uasort($a_list, fn($a, $b): int => $a <=> $b);
            $s_search = $this->generate_sim_list_search_str($s_article_table, $a_list);
            $o_similarlist = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $o_similarlist->set_sql_limit(0, $my_config->get_config_param('iNrofSimilarArticles'));
            $o_similarlist->select_string($s_search);
            return $o_similarlist;
        }
    }
    /**
     * Loads and returns articles list, bought by same customer.
     *
     * @return \OxidEsales\Eshop\Application\Model\ArticleList|null
     */
    public function get_customer_also_bought_this_products()
    {
        // Performance
        $my_config = Registry::get_config();
        if (!$my_config->get_config_param('bl_perfLoadCustomerWhoBoughtThis')) {
            return;
        }
        // selecting products that fits
        $s_q = $this->generate_search_str_for_customer_bought();
        $o_articles = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_articles->set_sql_limit(0, $my_config->get_config_param('iNrofCustomerWhoArticles'));
        $o_articles->select_string($s_q);
        if ($o_articles->count()) {
            return $o_articles;
        }
    }
    /**
     * Returns list object with info about article price that depends on amount in basket.
     * Takes data from oxprice2article table. Returns false if such info is not set.
     *
     * @return mixed
     */
    public function load_amount_price_info()
    {
        $my_config = Registry::get_config();
        if (!$my_config->get_config_param('bl_perfLoadPrice') || !$this->_bl_load_price || !$this->_bl_calc_price || !$this->has_amount_price()) {
            return [];
        }
        if ($this->_o_amount_price_info === null) {
            $this->_o_amount_price_info = [];
            if (count($a_am_price_list = $this->build_amount_price_list()->get_array())) {
                $this->_o_amount_price_info = $this->fill_amount_price_list($a_am_price_list);
            }
        }
        return $this->_o_amount_price_info;
    }
    /**
     * Returns all selectlists this article has (used in oxbasket)
     *
     * @param string $sKeyPrefix Optional key prefix
     *
     * @return array
     */
    public function get_select_lists($s_key_prefix = null)
    {
        //#1468C - more then one article in basket with different selectlist...
        //optionall function parameter $sKeyPrefix added, used only in basket.php
        $s_key = $this->get_id();
        if (isset($s_key_prefix)) {
            $s_key = $s_key_prefix . '__' . $s_key;
        }
        if (!isset(self::$_a_sel_list[$s_key])) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_sl_view_name = $table_view_name_generator->get_view_name('oxselectlist');
            $s_q = "select {$s_sl_view_name}.* from oxobject2selectlist join {$s_sl_view_name}\n                    on {$s_sl_view_name}.oxid=oxobject2selectlist.oxselnid\n                    where oxobject2selectlist.oxobjectid = :oxobjectid order by oxobject2selectlist.oxsort";
            // all selectlists this article has
            $o_lists = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_lists->init('oxselectlist');
            $o_lists->select_string($s_q, ['oxobjectid' => $this->get_id()]);
            //#1104S if this is variant ant it has no selectlists, trying with parent
            if ($o_lists->count() == 0 && $this->get_field_data('oxparentid')) {
                $o_lists->select_string($s_q, ['oxobjectid' => $this->oxarticles__oxparentid->value]);
            }
            // We do not need to calculate price here as there are method to get current article vat
            /*if ( $this->getPrice() != null ) {
                  $dVat = $this->getPrice()->getVat();
              }*/
            $d_vat = $this->get_article_vat();
            $i_cnt = 0;
            self::$_a_sel_list[$s_key] = [];
            foreach ($o_lists as $o_selectlist) {
                self::$_a_sel_list[$s_key][$i_cnt] = $o_selectlist->get_field_list($d_vat);
                self::$_a_sel_list[$s_key][$i_cnt]['name'] = $o_selectlist->oxselectlist__oxtitle->value;
                $i_cnt++;
            }
        }
        return self::$_a_sel_list[$s_key];
    }
    /**
     * Returns amount of variants article has
     *
     * @return mixed
     */
    public function get_variants_count()
    {
        return $this->get_field_data('oxvarcount');
    }
    /**
     * Checks if article has multidimensional variants
     *
     * @return bool
     */
    public function has_md_variants()
    {
        return $this->_bl_has_md_variants;
    }
    /**
     * Returns if article has intangible agreement with which customer will have to agree.
     *
     * @return bool
     */
    public function has_intangible_agreement()
    {
        return $this->oxarticles__oxshowcustomagreement->value && $this->oxarticles__oxnonmaterial->value && !$this->has_downloadable_agreement();
    }
    /**
     * Returns if article has downloadable agreement with which customer will have to agree.
     *
     * @return bool
     */
    public function has_downloadable_agreement()
    {
        return $this->oxarticles__oxshowcustomagreement->value && $this->oxarticles__oxisdownloadable->value;
    }
    /**
     * Returns variants selections lists array
     *
     * @param array  $aFilterIds    ids of active selections [optional]
     * @param string $sActVariantId active variant id [optional]
     * @param int    $iLimit        limit variant lists count (if non zero, return limited number of multidimensional
     *                              variant selections)
     *
     * @return array
     */
    public function get_variant_selections($a_filter_ids = null, $s_act_variant_id = null, $i_limit = 0)
    {
        $i_limit = (int) $i_limit;
        if (!isset($this->_a_variant_selections[$i_limit])) {
            $a_variant_selections = false;
            if ($this->oxarticles__oxvarcount->value) {
                $o_variants = $this->get_variants(false);
                $a_variant_selections = ox_new(\Oxid_Esales\Eshop\Application\Model\Variant_Handler::class)->build_variant_selections($this->oxarticles__oxvarname->get_raw_value(), $o_variants, $a_filter_ids, $s_act_variant_id, $i_limit);
                if (!empty($o_variants) && empty($a_variant_selections['rawselections'])) {
                    $a_variant_selections = false;
                }
            }
            $this->_a_variant_selections[$i_limit] = $a_variant_selections;
        }
        return $this->_a_variant_selections[$i_limit];
    }
    /**
     * Returns product selections lists array (used in azure theme)
     *
     * @param int   $iLimit  if given - will load limited count of selections [optional]
     * @param array $aFilter selection filter [optional]
     *
     * @return array
     */
    public function get_selections($i_limit = null, $a_filter = null)
    {
        $s_id = $this->get_id() . (int) $i_limit;
        if (!array_key_exists($s_id, self::$_a_selections)) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_sl_view_name = $table_view_name_generator->get_view_name('oxselectlist');
            $s_q = "select {$s_sl_view_name}.* from oxobject2selectlist join {$s_sl_view_name}\n                    on {$s_sl_view_name}.oxid=oxobject2selectlist.oxselnid\n                    where oxobject2selectlist.oxobjectid = :oxobjectid order by oxobject2selectlist.oxsort";
            if ($i_limit = (int) $i_limit) {
                $s_q .= " limit {$i_limit} ";
            }
            // vat value for price
            $d_vat = 0;
            if (($o_price = $this->get_price()) != null) {
                $d_vat = $o_price->get_vat();
            }
            // all selectlists this article has
            $o_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_list->init('oxselectlist');
            $o_list->get_base_object()->set_vat($d_vat);
            $o_list->select_string($s_q, ['oxobjectid' => $this->get_id()]);
            //#1104S if this is variant and it has no selectlists, trying with parent
            if ($o_list->count() == 0 && $this->oxarticles__oxparentid->value) {
                $o_list->select_string($s_q, ['oxobjectid' => $this->oxarticles__oxparentid->value]);
            }
            self::$_a_selections[$s_id] = $o_list->count() ? $o_list : false;
        }
        if (self::$_a_selections[$s_id]) {
            // marking active from filter
            $a_filter ??= Registry::get_request()->get_request_escaped_parameter('sel');
            if ($a_filter) {
                $i_sel_idx = 0;
                foreach (self::$_a_selections[$s_id] as $o_selection) {
                    if (isset($a_filter[$i_sel_idx])) {
                        $o_selection->set_active_selection_by_index($a_filter[$i_sel_idx]);
                    }
                    $i_sel_idx++;
                }
            }
        }
        return self::$_a_selections[$s_id];
    }
    /**
     * Returns variant list (list contains oxArticle objects)
     *
     * @param bool $blRemoveNotOrderables if true, removes from list not orderable articles, which are out of stock
     *                                    [optional]
     * @param bool $blForceCoreTable      if true forces core table use, default is false [optional]
     *
     * @return \OxidEsales\Eshop\Application\Model\ArticleList
     */
    public function get_full_variants($bl_remove_not_orderables = true, $bl_force_core_table = null)
    {
        return $this->load_variant_list(false, $bl_remove_not_orderables, $bl_force_core_table);
    }
    /**
     * Collects and returns article variants.
     * Note: Only active variants are returned by this method. If you need full variant list use
     * \OxidEsales\Eshop\Application\Model\Article::getAdminVariants()
     *
     * @param bool $blRemoveNotOrderables if true, removes from list not orderable articles, which are out of stock
     * @param bool $blForceCoreTable      if true forces core table use, default is false [optional]
     *
     * @return array
     */
    public function get_variants($bl_remove_not_orderables = true, $bl_force_core_table = null)
    {
        return $this->load_variant_list($this->is_in_list(), $bl_remove_not_orderables, $bl_force_core_table);
    }
    /**
     * Simple way to get variants without querying oxArticle table first. This is basically used for lists.
     */
    public function get_simple_variants()
    {
        if ($this->oxarticles__oxvarcount->value) {
            return $this->get_variants();
        }
    }
    /**
     * Loads article variants and returns variants list object. Article language may
     * be set by passing with parameter, or GET/POST/Session variable.
     *
     * @param string $sLanguage shop language.
     *
     * @return object
     */
    public function get_admin_variants($s_language = null)
    {
        $o_variants = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        if ($s_id = $this->get_id()) {
            $o_base_obj = $o_variants->get_base_object();
            if (is_null($s_language)) {
                $o_base_obj->set_language(Registry::get_lang()->get_base_language());
            } else {
                $o_base_obj->set_language($s_language);
            }
            $s_sql = 'select * from ' . $o_base_obj->get_view_name() . '
                where oxparentid = :oxparentid
                order by oxsort ';
            $o_variants->select_string($s_sql, ['oxparentid' => $s_id]);
            //if we have variants then depending on config option the parent may be non buyable
            if (!Registry::get_config()->get_config_param('blVariantParentBuyable') && $o_variants->count() > 0) {
                //$this->blNotBuyable = true;
                $this->_bl_not_buyable_parent = true;
            }
        }
        return $o_variants;
    }
    /**
     * Loads and returns article category object. First tries to load
     * assigned category and is such category does not exist, tries to
     * load category by price
     *
     * @return \OxidEsales\Eshop\Application\Model\Category|null
     */
    public function get_category()
    {
        $shop_id = Registry::get_config()->get_shop_id();
        $id = $this->get_parent_id();
        if (!$id) {
            $id = $this->get_id();
        }
        $this->initialize_shop_article_category_cache($shop_id);
        if (\array_key_exists($id, self::$_a_category_cache[$shop_id])) {
            return self::$_a_category_cache[$shop_id][$id];
        }
        start_profile('getCategory');
        $category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $category->set_language($this->get_language());
        $str = Str::get_str();
        $where = $category->get_sql_active_snippet();
        $select = $this->generate_search_str($id);
        $select .= ($str->strstr($select, 'where') ? ' and ' : ' where ') . $where . ' order by oxobject2category.oxtime limit 1';
        // category not found ?
        $record = Database_Provider::get_db()->select($select);
        if ($record && $record->count() > 0) {
            $category->assign($record->fields);
        } else {
            $select = $this->generate_search_str($id, true);
            $select .= ($str->strstr($select, 'where') ? ' and ' : ' where ') . $where . ' limit 1';
            // looking for price category
            $record = Database_Provider::get_db()->select($select);
            if ($record && $record->count() > 0) {
                $category->assign($record->fields);
            } else {
                $category = null;
            }
        }
        // add the category instance to cache
        self::$_a_category_cache[$shop_id][$id] = $category;
        stop_profile('getCategory');
        return $category;
    }
    private function initialize_shop_article_category_cache($shop_id): void
    {
        if (!\array_key_exists($shop_id, self::$_a_category_cache)) {
            self::$_a_category_cache[$shop_id] = [];
        }
    }
    /**
     * Returns ID's of categories where this article is assigned
     *
     * @param bool $blActCats select categories if all parents are active
     * @param bool $blSkipCache Whether to skip cache
     *
     * @return array
     */
    public function get_category_ids($bl_act_cats = false, $bl_skip_cache = false)
    {
        $s_article_id = $this->get_id();
        if (!isset(self::$_a_article_cats[$s_article_id]) || $bl_skip_cache) {
            $s_sql = $this->get_category_ids_select($bl_act_cats);
            $a_category_ids = $this->select_category_ids($s_sql, 'oxcatnid');
            $s_sql = $this->get_sql_for_price_categories();
            $a_price_category_ids = $this->select_category_ids($s_sql, 'oxid');
            self::$_a_article_cats[$s_article_id] = array_unique(array_merge($a_category_ids, $a_price_category_ids));
        }
        return self::$_a_article_cats[$s_article_id];
    }
    /**
     * Returns current article vendor object. If $blShopCheck = false, then
     * vendor loading will fallback to oxI18n object and blReadOnly parameter
     * will be set to true if vendor is not assigned to current shop
     *
     * @param bool $blShopCheck Set false if shop check is not required (default is true)
     *
     * @return object
     */
    public function get_vendor($bl_shop_check = true)
    {
        $s_vendor_id = $this->get_vendor_id();
        if ($s_vendor_id) {
            $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        } elseif (!$bl_shop_check && $this->oxarticles__oxvendorid->value) {
            $o_vendor = $this->create_multilanguage_vendor_object();
            $s_vendor_id = $this->oxarticles__oxvendorid->value;
        }
        if ($s_vendor_id && $o_vendor && $o_vendor->load($s_vendor_id) && $o_vendor->oxvendor__oxactive->value) {
            return $o_vendor;
        }
        return null;
    }
    /**
     * @return \OxidEsales\Eshop\Core\Model\MultiLanguageModel
     */
    protected function create_multilanguage_vendor_object()
    {
        $o_vendor = ox_new(Multi_Language_Model::class);
        $o_vendor->init('oxvendor');
        $o_vendor->set_read_only(true);
        return $o_vendor;
    }
    /**
     * Returns article object vendor ID. Result is cached into self::$_aArticleVendors
     *
     * @return string
     */
    public function get_vendor_id()
    {
        if ($this->oxarticles__oxvendorid->value) {
            return $this->oxarticles__oxvendorid->value;
        }
        return false;
    }
    /**
     * Returns article object Manufacturer ID. Result is cached into self::$_aArticleManufacturers
     *
     * @return string
     */
    public function get_manufacturer_id()
    {
        return $this->oxarticles__oxmanufacturerid->value ?: false;
    }
    /**
     * Returns current article Manufacturer object. If $blShopCheck = false, then
     * Manufacturer blReadOnly parameter will be set to true. If Manufacturer is
     * not assigned to current shop
     *
     * @param bool $blShopCheck Set false if shop check is not required (default is true)
     *
     * @return \OxidEsales\Eshop\Application\Model\Manufacturer|null
     */
    public function get_manufacturer($bl_shop_check = true)
    {
        $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
        if (!($s_manufacturer_id = $this->get_manufacturer_id()) && !$bl_shop_check && $this->oxarticles__oxmanufacturerid->value) {
            $this->update_manufacturer_before_loading($o_manufacturer);
            $s_manufacturer_id = $this->oxarticles__oxmanufacturerid->value;
        }
        if ($s_manufacturer_id && $o_manufacturer->load($s_manufacturer_id)) {
            if (!Registry::get_config()->get_config_param('bl_perfLoadManufacturerTree')) {
                $o_manufacturer->set_read_only(true);
            }
            return $o_manufacturer->oxmanufacturers__oxactive->value ? $o_manufacturer : null;
        }
        return null;
    }
    /**
     * Checks if article is assigned to category $sCatNID.
     *
     * @param string $sCatNid category ID
     *
     * @return bool
     */
    public function in_category($s_cat_nid)
    {
        return in_array($s_cat_nid, $this->get_category_ids());
    }
    /**
     * Checks if article is assigned to passed category (even checks
     * if this category is "price category"). Returns true on success.
     *
     * @param string $sCatId category ID
     *
     * @return bool
     */
    public function is_assigned_to_category($s_cat_id)
    {
        // variant handling
        $s_oxid = $this->get_id();
        if (isset($this->oxarticles__oxparentid->value) && $this->oxarticles__oxparentid->value) {
            $s_oxid = $this->oxarticles__oxparentid->value;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_select = $this->generate_select_cat_str($s_oxid, $s_cat_id);
        $s_oxid = $o_db->get_one($s_select);
        // article is assigned to passed category!
        if (isset($s_oxid) && $s_oxid) {
            return true;
        }
        // maybe this category is price category ?
        if (Registry::get_config()->get_config_param('bl_perfLoadPrice') && $this->_bl_load_price) {
            $d_price_from_to = $this->get_price()->get_brutto_price();
            if ($d_price_from_to > 0) {
                $s_select = $this->generate_select_cat_str($s_oxid, $s_cat_id, $d_price_from_to);
                $s_oxid = $o_db->get_one($s_select);
                // article is assigned to passed category!
                if (isset($s_oxid) && $s_oxid) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Returns T price
     *
     * @return \OxidEsales\Eshop\Core\Price|null
     */
    public function get_t_price()
    {
        if (!Registry::get_config()->get_config_param('bl_perfLoadPrice') || !$this->_bl_load_price) {
            return;
        }
        // return cached result, since oPrice is created ONLY in this function [or function of EQUAL level]
        if ($this->_o_t_price !== null) {
            return $this->_o_t_price;
        }
        $o_price = $this->get_price_object();
        $d_base_price = $this->oxarticles__oxtprice->value;
        $d_base_price = $this->prepare_price($d_base_price, $this->get_article_vat());
        $o_price->set_price($d_base_price);
        $this->apply_vat($o_price, $this->get_article_vat());
        $this->apply_currency($o_price);
        if ($this->is_parent_not_buyable()) {
            // if parent article is not buyable then compare agains min article variant price
            $o_price2 = $this->get_var_min_price();
        } else {
            // else compare against article price
            $o_price2 = $this->get_price();
        }
        if ($o_price->get_price() <= $o_price2->get_price()) {
            // if RRP price is less or equal to comparable price then return
            return;
        }
        $this->_o_t_price = $o_price;
        return $this->_o_t_price;
    }
    /**
     * Checks if discount should be skipped for this article in basket. Returns true if yes.
     *
     * @return bool
     */
    public function skip_discounts()
    {
        // already loaded skip discounts config
        if ($this->_bl_skip_discounts !== null) {
            return $this->_bl_skip_discounts;
        }
        if ($this->oxarticles__oxskipdiscounts->value) {
            return true;
        }
        $this->_bl_skip_discounts = false;
        if (Registry::get(\Oxid_Esales\Eshop\Application\Model\Discount_List::class)->has_skip_discount_categories()) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category', $this->get_language());
            $s_view_name = $table_view_name_generator->get_view_name('oxcategories', $this->get_language());
            $s_select = "select 1 from {$s_o2c_view} as {$s_o2c_view}\n                left join {$s_view_name} on {$s_view_name}.oxid = {$s_o2c_view}.oxcatnid\n                where {$s_o2c_view}.oxobjectid = :oxobjectid\n                    and {$s_view_name}.oxactive = :oxactive\n                    and {$s_view_name}.oxskipdiscounts = :oxskipdiscounts ";
            $params = ['oxobjectid' => $this->get_id(), 'oxactive' => 1, 'oxskipdiscounts' => 1];
            $this->_bl_skip_discounts = $o_db->get_one($s_select, $params) == 1;
        }
        return $this->_bl_skip_discounts;
    }
    /**
     * Sets the current oxPrice object
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice the new price object
     */
    public function set_price(Price $o_price): void
    {
        $this->_o_price = $o_price;
    }
    /**
     * Returns base article price from database. Price may differ according to users group
     * Override this function if you want e.g. different prices for diff. usergroups.
     *
     * @param double $dAmount article amount. Default is 1
     *
     * @return double
     */
    public function get_base_price($d_amount = 1)
    {
        // override this function if you want e.g. different prices
        // for diff. user groups.
        // Performance
        $my_config = Registry::get_config();
        if (!$my_config->get_config_param('bl_perfLoadPrice') || !$this->_bl_load_price) {
            return;
        }
        // GroupPrice or DB price ajusted by AmountPrice
        $d_price = $this->get_modified_amount_price($d_amount);
        return $d_price;
    }
    /**
     * Modifies given amount price.
     *
     * @param int $amount
     *
     * @return double
     */
    protected function get_modified_amount_price($amount)
    {
        return $this->get_amount_price($amount);
    }
    /**
     * Calculates and returns price of article (adds taxes and discounts).
     *
     * @param float|int $dAmount article amount.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_price($d_amount = 1)
    {
        $my_config = Registry::get_config();
        // Performance
        if (!$my_config->get_config_param('bl_perfLoadPrice') || !$this->_bl_load_price) {
            return;
        }
        // return cached result, since oPrice is created ONLY in this function [or function of EQUAL level]
        if ($d_amount != 1 || $this->_o_price === null) {
            // module
            $d_base_price = $this->get_base_price($d_amount);
            $d_base_price = $this->prepare_price($d_base_price, $this->get_article_vat());
            $o_price = $this->get_price_object();
            $o_price->set_price($d_base_price);
            // price handling
            if (!$this->_bl_calc_price && $d_amount == 1) {
                return $this->_o_price = $o_price;
            }
            $this->calculate_price($o_price);
            if ($d_amount != 1) {
                return $o_price;
            }
            $this->_o_price = $o_price;
        }
        return $this->_o_price;
    }
    /**
     * sets article user
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user to set
     */
    public function set_article_user($o_user): void
    {
        $this->_o_user = $o_user;
    }
    /**
     * @return \OxidEsales\Eshop\Application\Model\User article user.
     */
    public function get_article_user()
    {
        if ($this->_o_user) {
            return $this->_o_user;
        }
        return $this->get_user();
    }
    /**
     * Creates, calculates and returns oxPrice object for basket product.
     *
     * @param float  $dAmount  Amount
     * @param array  $aSelList Selection list
     * @param object $oBasket  User shopping basket object
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_basket_price($d_amount, $a_sel_list, $o_basket)
    {
        $o_user = $o_basket->get_basket_user();
        $this->set_article_user($o_user);
        $o_basket_price = $this->get_price_object($o_basket->is_calculation_mode_netto());
        // get base price
        $d_base_price = $this->get_base_price($d_amount);
        $d_base_price = $this->modify_select_list_price($d_base_price, $a_sel_list);
        $d_base_price = $this->prepare_price($d_base_price, $this->get_article_vat(), $o_basket->is_calculation_mode_netto());
        // applying select list price
        // setting price
        $o_basket_price->set_price($d_base_price);
        $d_vat = Registry::get(\Oxid_Esales\Eshop\Application\Model\Vat_Selector::class)->get_basket_item_vat($this, $o_basket);
        $this->calculate_price($o_basket_price, $d_vat);
        // returning final price object
        return $o_basket_price;
    }
    /**
     * Deletes record and other information related to this article such as images from DB,
     * also removes variants. Returns true if entry was deleted.
     *
     * @param string $sOXID Article id
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        if (!$s_oxid) {
            return false;
        }
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $database->start_transaction();
        try {
            // #2339 delete first variants before deleting parent product
            $this->delete_variant_records($s_oxid);
            $this->load($s_oxid);
            $this->delete_pics();
            $this->on_change_reset_counts($s_oxid, $this->oxarticles__oxvendorid->value, $this->oxarticles__oxmanufacturerid->value);
            // delete self
            $deleted = parent::delete($s_oxid);
            $this->delete_records($s_oxid);
            Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Article::class)->on_delete_article($this);
            $this->on_change(ACTION_DELETE, $s_oxid, $this->get_field_data('oxparentid'));
            $database->commit_transaction();
        } catch (Exception $exception) {
            $database->rollback_transaction();
            throw $exception;
        }
        return $deleted;
    }
    /**
     * Reduce article stock. return the affected amount
     *
     * @param float $dAmount              amount to reduce
     * @param bool  $blAllowNegativeStock are negative stocks allowed?
     *
     * @return float
     */
    public function reduce_stock($d_amount, $bl_allow_negative_stock = false)
    {
        $this->action_type = ACTION_UPDATE_STOCK;
        $this->before_update();
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $query = 'select oxstock
            from oxarticles
            where oxid = :oxid FOR UPDATE ';
        $actual_stock = $database->get_one($query, ['oxid' => $this->get_id()]);
        $i_stock_count = $actual_stock - $d_amount;
        if (!$bl_allow_negative_stock && $i_stock_count < 0) {
            $d_amount += $i_stock_count;
            $i_stock_count = 0;
        }
        $this->oxarticles__oxstock = new Field($i_stock_count);
        $query = 'update oxarticles set oxarticles.oxstock = :oxstock where oxarticles.oxid = :oxid';
        $database->execute($query, ['oxstock' => $i_stock_count, 'oxid' => $this->get_id()]);
        $this->on_change(ACTION_UPDATE_STOCK);
        return $d_amount;
    }
    /**
     * Recursive function. Updates quantity of sold articles.
     * Return true if amount was changed in database.
     *
     * @param float $dAmount Number of articles sold
     *
     * @return mixed
     */
    public function update_sold_amount($d_amount = 0)
    {
        if (!$d_amount) {
            return;
        }
        $rs = false;
        // article is not variant - should be updated current amount
        if (!$this->oxarticles__oxparentid->value) {
            //updating by SQL query, due to wrong behaviour if saving article using not admin mode
            $d_amount = (float) $d_amount;
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $query = 'update oxarticles
                      set oxarticles.oxsoldamount = (oxarticles.oxsoldamount + :amount)
                      where oxarticles.oxid = :oxid';
            $rs = $o_db->execute($query, ['oxid' => $this->oxarticles__oxid->value, 'amount' => $d_amount]);
            return (bool) $rs;
        }
        // article is not variant - should be updated current amount
        if ($this->oxarticles__oxparentid->value) {
            // article is variant - should be updated this article parent amount
            $o_update_article = $this->get_parent_article();
            if ($o_update_article) {
                $o_update_article->update_sold_amount($d_amount);
            }
        }
        return $rs;
    }
    /**
     * Disables reminder functionality for article
     *
     * @return bool
     */
    public function disable_reminder()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $query = 'update oxarticles set oxarticles.oxremindactive = 2 where oxarticles.oxid = :oxid';
        return (bool) $o_db->execute($query, ['oxid' => $this->oxarticles__oxid->value]);
    }
    /**
     * (\OxidEsales\Eshop\Application\Model\Article::_saveArtLongDesc()) save the object using parent::save() method.
     *
     * @return bool
     */
    public function save()
    {
        $this->assign_parent_depend_fields();
        $bl_ret = parent::save();
        // saving long description
        $this->save_art_long_desc();
        return $bl_ret;
    }
    /**
     * Changes article variant to parent article
     */
    public function reset_parent(): void
    {
        $s_parent_id = $this->oxarticles__oxparentid->value;
        $this->oxarticles__oxparentid = new Field('', Field::T_RAW);
        $this->_bl_allow_empty_parent_id = true;
        $this->save();
        $this->_bl_allow_empty_parent_id = false;
        if ($s_parent_id !== '') {
            $this->on_change(ACTION_UPDATE, null, $s_parent_id);
        }
    }
    /**
     * collect article pics, icons, zoompic and puts it all in an array
     * structure of array (ActPicID, ActPic, MorePics, Pics, Icons, ZoomPic)
     *
     * @return array
     */
    public function get_picture_gallery()
    {
        $media_items = Container_Facade::get(Product_Media_View_Service_Interface::class)->get_all_by_role(Id::from_string($this->get_id()), Product_Media_Role::from(Product_Media_Role::DETAIL));
        $active_media = $this->determine_active_media($media_items);
        return ['activeMedia' => $active_media, 'mediaItems' => $media_items, 'hasMultipleImages' => count($media_items) > 1];
    }
    private function determine_active_media(array $media_items): ?Product_Media_View
    {
        if (empty($media_items)) {
            return null;
        }
        $requested_media_id = Registry::get_request()->get_request_escaped_parameter('actmediaid');
        if ($requested_media_id && isset($media_items[$requested_media_id])) {
            return $media_items[$requested_media_id];
        }
        return reset($media_items);
    }
    /**
     * This function is triggered whenever article is saved or deleted or after the stock is changed.
     * Originally we need to update the oxstock for possible article parent in case parent is not buyable
     * Plus you may want to extend this function to update some extended information.
     * Call \OxidEsales\Eshop\Application\Model\Article::onChange($sAction, $sOXID) with ID parameter when changes are
     * executed over SQL.
     * (or use module class instead of oxArticle if such exists)
     *
     * @param string $action          Action constant
     * @param string $articleId       Article ID
     * @param string $parentArticleId Parent ID
     */
    public function on_change($action = null, $article_id = null, $parent_article_id = null): void
    {
        $this->action_type = !is_null($action) ? $action : $this->action_type;
        $my_config = Registry::get_config();
        if (!isset($article_id)) {
            if ($this->get_id()) {
                $article_id = $this->get_id();
            }
            if (!isset($article_id)) {
                $article_id = $this->oxarticles__oxid->value;
            }
            if ($this->oxarticles__oxparentid && $this->oxarticles__oxparentid->value) {
                $parent_article_id = $this->oxarticles__oxparentid->value;
            }
        }
        if (!isset($article_id)) {
            return;
        }
        //if (isset($sOXID) && !$myConfig->blVariantParentBuyable && $myConfig->blUseStock)
        if ($my_config->get_config_param('blUseStock')) {
            //if article has variants then updating oxvarstock field
            //getting parent id
            if (!isset($parent_article_id)) {
                $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
                $s_q = 'select oxparentid from oxarticles where oxid = :oxid';
                $parent_article_id = $o_db->get_one($s_q, ['oxid' => $article_id]);
            }
            //if we have parent id then update stock
            if ($parent_article_id) {
                $this->on_change_update_stock($parent_article_id);
            }
        }
        //if we have parent id then update count
        //update count even if blUseStock is not active
        if ($parent_article_id) {
            $this->on_change_update_var_count($parent_article_id);
        }
        $s_id = $parent_article_id ?: $article_id;
        $this->set_var_min_max_price($s_id);
        $this->update_parent_depend_fields();
        // resetting articles count cache if stock has changed and some
        // articles goes offline (M:1448)
        if ($action === ACTION_UPDATE_STOCK) {
            $this->assign_stock();
            $this->on_change_stock_reset_count($article_id);
        }
        Container_Facade::dispatch(new After_Model_Update_Event($this));
    }
    /**
     * Returns custom article VAT value if possible
     * By default value is taken from oxarticle__oxvat field
     *
     * @return double
     */
    public function get_custom_vat()
    {
        if ($this->__isset('oxarticles__oxvat') || $this->__get('oxarticles__oxvat')) {
            return $this->oxarticles__oxvat->value;
        }
    }
    /**
     * Checks if stock configuration allows to buy user chosen amount $dAmount
     *
     * @param double     $dAmount         buyable amount
     * @param double|int $dArtStockAmount stock amount
     * @param bool       $selectForUpdate Set true to select for update
     *
     * @return mixed
     */
    public function check_for_stock($d_amount, $d_art_stock_amount = 0, $select_for_update = false)
    {
        $my_config = Registry::get_config();
        if (!$my_config->get_config_param('blUseStock')) {
            return true;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // fetching DB info as its up-to-date
        $s_q = 'select oxstock, oxstockflag from oxarticles
            where oxid = :oxid';
        $s_q .= $select_for_update ? ' FOR UPDATE ' : '';
        $rs = $o_db->select($s_q, ['oxid' => $this->get_id()]);
        $i_on_stock = 0;
        if ($rs !== false && $rs->count() > 0) {
            $i_on_stock = $rs->fields['oxstock'] - $d_art_stock_amount;
            $i_stock_flag = $rs->fields['oxstockflag'];
            //When using stockflag 1 and 4 with basket reservations enabled but disallowing
            //negative stock values we would allow to reserve more items than are initially available
            //by keeping the stock level not lower than zero. When discarding reservations
            //stock level might differ from original value.
            if (!$my_config->get_config_param('blPsBasketReservationEnabled') || $my_config->get_config_param('blPsBasketReservationEnabled') && $my_config->get_config_param('blAllowNegativeStock')) {
                // foreign stock is also always considered as on stock
                if ($i_stock_flag == 1 || $i_stock_flag == 4) {
                    return true;
                }
            }
            if (!$my_config->get_config_param('blAllowUnevenAmounts')) {
                $i_on_stock = floor($i_on_stock);
            }
        }
        if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
            $session = Registry::get_session();
            $i_on_stock += $session->get_basket_reservations()->get_reserved_amount($this->get_id());
        }
        if ($i_on_stock >= $d_amount) {
            return true;
        }
        if ($i_on_stock > 0) {
            return $i_on_stock;
        }
        $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception::class);
        $o_ex->set_message('ERROR_MESSAGE_ARTICLE_ARTICLE_NOT_BUYABLE');
        Registry::get_utils_view()->add_error_to_display($o_ex);
        return false;
    }
    /**
     * Get article long description
     *
     * @return object $oField field object
     */
    public function get_long_description()
    {
        if ($this->_o_long_desc === null) {
            // initializing
            $this->_o_long_desc = new Field();
            // choosing which to get..
            $s_oxid = $this->get_id();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_view_name = $table_view_name_generator->get_view_name('oxartextends', $this->get_language());
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_db_value = $o_db->get_one("select oxlongdesc from {$s_view_name} where oxid = :oxid", ['oxid' => $s_oxid]);
            if ($s_db_value != false) {
                $this->_o_long_desc->set_value($s_db_value, Field::T_RAW);
            } elseif ($this->oxarticles__oxparentid && $this->oxarticles__oxparentid->value) {
                if (!$this->is_admin() || $this->_bl_load_parent_data) {
                    $o_parent = $this->get_parent_article();
                    if ($o_parent) {
                        $this->_o_long_desc->set_value($o_parent->get_long_description()->get_raw_value(), Field::T_RAW);
                    }
                }
            }
        }
        return $this->_o_long_desc;
    }
    /**
     * Save article long description to oxartext table
     *
     * @param string $longDescription description to set
     */
    public function set_article_long_desc($long_description): void
    {
        // setting current value
        $this->_o_long_desc = new Field($long_description, Field::T_RAW);
        $this->oxarticles__oxlongdesc = new Field($long_description, Field::T_RAW);
    }
    /**
     * the uninitilized list of attributes
     * use getAttributes
     * @return \OxidEsales\Eshop\Application\Model\AttributeList
     */
    protected function new_attribute_list()
    {
        return ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute_List::class);
    }
    /**
     * Loads and returns attribute list associated with this article
     *
     * @return \OxidEsales\Eshop\Application\Model\AttributeList
     */
    public function get_attributes()
    {
        if ($this->_o_attribute_list === null) {
            $this->_o_attribute_list = $this->new_attributelist();
            $this->_o_attribute_list->load_attributes($this->get_id(), $this->get_parent_id());
        }
        return $this->_o_attribute_list;
    }
    /**
     * Loads and returns attribute list for display in basket
     *
     * @return \OxidEsales\Eshop\Application\Model\AttributeList
     */
    public function get_attributes_displayable_in_basket()
    {
        if ($this->basket_attribute_list === null) {
            $this->basket_attribute_list = $this->new_attributelist();
            $this->basket_attribute_list->load_attributes_displayable_in_basket($this->get_id(), $this->get_parent_id());
        }
        return $this->basket_attribute_list;
    }
    /**
     * Appends article seo url with additional request parameters
     *
     * @param string $sAddParams additional parameters which needs to be added to product url
     * @param int    $iLang      language id
     */
    public function append_link($s_add_params, $i_lang = null): void
    {
        if ($s_add_params) {
            if ($i_lang === null) {
                $i_lang = $this->get_language();
            }
            $this->_a_seo_add_params[$i_lang] = isset($this->_a_seo_add_params[$i_lang]) ? $this->_a_seo_add_params[$i_lang] . '&amp;' : '';
            $this->_a_seo_add_params[$i_lang] .= $s_add_params;
        }
    }
    /**
     * Returns raw article seo url
     *
     * @param int  $iLang  language id
     * @param bool $blMain force to return main url [optional]
     *
     * @return string
     */
    public function get_base_seo_link($i_lang, $bl_main = false)
    {
        /** @var \OxidEsales\Eshop\Application\Model\SeoEncoderArticle $oEncoder */
        $o_encoder = Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Article::class);
        if (!$bl_main) {
            return $o_encoder->get_article_url($this, $i_lang, $this->get_link_type());
        }
        return $o_encoder->get_article_main_url($this, $i_lang);
    }
    /**
     * Gets article link
     *
     * @param int  $iLang  language id [optional]
     * @param bool $blMain force to return main url [optional]
     *
     * @return string
     */
    public function get_link($i_lang = null, $bl_main = false)
    {
        if (!Registry::get_utils()->seo_is_active()) {
            return $this->get_std_link($i_lang);
        }
        if ($i_lang === null) {
            $i_lang = $this->get_language();
        }
        $i_link_type = $this->get_link_type();
        if (!isset($this->_a_seo_urls[$i_lang][$i_link_type])) {
            $this->_a_seo_urls[$i_lang][$i_link_type] = $this->get_base_seo_link($i_lang, $bl_main);
        }
        $s_url = $this->_a_seo_urls[$i_lang][$i_link_type];
        if (isset($this->_a_seo_add_params[$i_lang])) {
            $s_url .= (!str_contains($s_url . $this->_a_seo_add_params[$i_lang], '?') ? '?' : '&amp;') . $this->_a_seo_add_params[$i_lang];
        }
        return $s_url;
    }
    /**
     * Returns main object URL. If SEO is ON returned link will be in SEO form,
     * else URL will have dynamic form
     *
     * @param int $iLang language id [optional]
     *
     * @return string
     */
    public function get_main_link($i_lang = null)
    {
        return $this->get_link($i_lang, true);
    }
    /**
     * Resets details link
     *
     * @param int $iType type of link to load
     */
    public function set_link_type($i_type): void
    {
        // resetting details link, to force new
        $this->_s_detail_link = null;
        // setting link type
        $this->_i_link_type = (int) $i_type;
    }
    /**
     * Get link type
     *
     * @return int
     */
    public function get_link_type()
    {
        return $this->_i_link_type;
    }
    /**
     * Appends article dynamic url with additional request parameters
     *
     * @param string $sAddParams additional parameters which needs to be added to product url
     * @param int    $iLang      language id
     */
    public function append_std_link($s_add_params, $i_lang = null): void
    {
        if ($s_add_params) {
            if ($i_lang === null) {
                $i_lang = $this->get_language();
            }
            $this->_a_std_add_params[$i_lang] = isset($this->_a_std_add_params[$i_lang]) ? $this->_a_std_add_params[$i_lang] . '&amp;' : '';
            $this->_a_std_add_params[$i_lang] .= $s_add_params;
        }
    }
    /**
     * Returns base dynamic url: shopurl/index.php?cl=details
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not [optional]
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function get_base_std_link($i_lang, $bl_add_id = true, $bl_full = true)
    {
        $s_url = '';
        if ($bl_full) {
            //always returns shop url, not admin
            $s_url = Registry::get_config()->get_shop_url($i_lang, false);
        }
        $s_url .= 'index.php?cl=details' . ($bl_add_id ? '&amp;anid=' . $this->get_id() : '');
        return $s_url . (isset($this->_a_std_add_params[$i_lang]) ? '&amp;' . $this->_a_std_add_params[$i_lang] : '');
    }
    /**
     * Returns standard URL to product
     *
     * @param int   $iLang   required language. optional
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function get_std_link($i_lang = null, $a_params = [])
    {
        if ($i_lang === null) {
            $i_lang = $this->get_language();
        }
        if (!isset($this->_a_std_urls[$i_lang])) {
            $this->_a_std_urls[$i_lang] = $this->get_base_std_link($i_lang);
        }
        return Registry::get_utils_url()->process_url($this->_a_std_urls[$i_lang], true, $a_params, $i_lang);
    }
    /**
     * Return article media URL
     *
     * @return array
     */
    public function get_media_urls()
    {
        if ($this->_a_media_urls === null) {
            $this->_a_media_urls = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $this->_a_media_urls->init('oxmediaurl');
            $this->_a_media_urls->get_base_object()->set_language($this->get_language());
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_view_name = $table_view_name_generator->get_view_name('oxmediaurls', $this->get_language());
            $s_q = "select * from {$s_view_name} where oxobjectid = :oxobjectid";
            $this->_a_media_urls->select_string($s_q, ['oxobjectid' => $this->get_id()]);
        }
        return $this->_a_media_urls;
    }
    /**
     * Get image url
     *
     * @return array
     */
    public function get_dyn_image_dir()
    {
        return $this->_s_dyn_image_dir;
    }
    /**
     * Returns select lists to display
     *
     * @return array
     */
    public function get_disp_sel_list()
    {
        if ($this->_a_disp_sel_list === null) {
            if (Registry::get_config()->get_config_param('bl_perfLoadSelectLists') && Registry::get_config()->get_config_param('bl_perfLoadSelectListsInAList')) {
                $this->_a_disp_sel_list = $this->get_select_lists();
            }
        }
        return $this->_a_disp_sel_list;
    }
    /**
     * Get more details link
     *
     * @return string
     */
    public function get_more_detail_link()
    {
        if ($this->_s_more_detail_link == null) {
            // and assign special article values
            $this->_s_more_detail_link = Registry::get_config()->get_shop_home_url() . 'cl=moredetails';
            // not always it is okey, as not all the time active category is the same as primary article cat.
            if ($s_act_cat = Registry::get_request()->get_request_escaped_parameter('cnid')) {
                $this->_s_more_detail_link .= '&amp;cnid=' . $s_act_cat;
            }
            $this->_s_more_detail_link .= '&amp;anid=' . $this->get_id();
        }
        return $this->_s_more_detail_link;
    }
    /**
     * Get to basket link
     *
     * @return string
     */
    public function get_to_basket_link()
    {
        if ($this->_s_to_basket_link == null) {
            $my_config = Registry::get_config();
            if (Registry::get_utils()->is_search_engine()) {
                $this->_s_to_basket_link = $this->get_link();
            } else {
                // and assign special article values
                $this->_s_to_basket_link = $my_config->get_shop_home_url();
                // override some classes as these should never showup
                $act_controller_id = Registry::get_config()->get_request_controller_id();
                if ($act_controller_id == 'thankyou') {
                    $act_controller_id = 'basket';
                }
                $this->_s_to_basket_link .= 'cl=' . $act_controller_id;
                // this is not very correct
                if ($s_act_cat = Registry::get_request()->get_request_escaped_parameter('cnid')) {
                    $this->_s_to_basket_link .= '&amp;cnid=' . $s_act_cat;
                }
                $this->_s_to_basket_link .= '&amp;fnc=tobasket&amp;aid=' . $this->get_id() . '&amp;anid=' . $this->get_id();
                if ($s_tpl = basename((string) Registry::get_request()->get_request_escaped_parameter('tpl'))) {
                    $this->_s_to_basket_link .= '&amp;tpl=' . $s_tpl;
                }
            }
        }
        return $this->_s_to_basket_link;
    }
    /**
     * Get stock status
     *
     * @return integer
     */
    public function get_stock_status()
    {
        return $this->_i_stock_status;
    }
    /**
     * Get stock status as it was on loading this object.
     *
     * @return integer
     */
    public function get_stock_status_on_load()
    {
        return $this->_i_stock_status_on_load;
    }
    /**
     * Get stock
     *
     * @return float
     */
    public function get_stock()
    {
        return $this->oxarticles__oxstock->value;
    }
    /**
     * Returns formatted delivery date. If the date is past or not set ('0000-00-00') returns false.
     *
     * @deprecated since v6.2 (2020-02-26); use getRestockDate();
     * @return string|bool
     */
    public function get_delivery_date()
    {
        return $this->get_restock_date();
    }
    /**
     * Returns formatted delivery date. If the date is past or not set ('0000-00-00') returns false.
     *
     * @return string|bool
     */
    public function get_restock_date()
    {
        $restock_date = $this->get_field_data('oxdelivery');
        if ($restock_date >= date('Y-m-d')) {
            return Registry::get_utils_date()->format_db_date($restock_date);
        }
        return false;
    }
    /**
     * Returns rounded T price.
     *
     * @deprecated since v5.1 (2013-10-03); use getTPrice() and oxPrice modifier;
     *
     * @return double|bool
     */
    public function get_ft_price()
    {
        // module
        if ($o_price = $this->get_t_price()) {
            if ($d_price = $this->get_price_for_view($o_price)) {
                return Registry::get_lang()->format_currency($d_price);
            }
        }
    }
    /**
     * Returns formatted product's price.
     *
     * @deprecated since v5.1 (2013-10-04); use oxPrice template engine plugin for formatting in templates
     *
     * @return double
     */
    public function get_f_price()
    {
        if ($o_price = $this->get_price()) {
            $d_price = $this->get_price_for_view($o_price);
            return Registry::get_lang()->format_currency($d_price);
        }
    }
    /**
     * Resets oxremindactive status.
     * If remindActive status is 2, reminder is already sent.
     */
    public function reset_remind_status(): void
    {
        if ($this->oxarticles__oxremindactive->value == 2 && $this->oxarticles__oxremindamount->value <= $this->oxarticles__oxstock->value) {
            $this->oxarticles__oxremindactive->value = 1;
        }
    }
    /**
     * Returns formatted product's NETTO price.
     *
     * @deprecated since v5.1 (2013-10-03); use getPrice() and oxPrice modifier;
     *
     * @return double
     */
    public function get_f_net_price()
    {
        if ($o_price = $this->get_price()) {
            return Registry::get_lang()->format_currency($o_price->get_netto_price());
        }
    }
    /**
     * Returns true if parent is not buyable
     *
     * @return bool
     */
    public function is_parent_not_buyable()
    {
        return $this->_bl_not_buyable_parent;
    }
    /**
     * Returns true if article is not buyable
     *
     * @return bool
     */
    public function is_not_buyable()
    {
        return $this->_bl_not_buyable;
    }
    /**
     * Sets product state - buyable or not
     *
     * @param bool $blBuyable state - buyable or not (default false)
     */
    public function set_buyable_state($bl_buyable = false): void
    {
        $this->_bl_not_buyable = !$bl_buyable;
    }
    /**
     * Sets selectlists of current product
     *
     * @param array $aSelList selectlist
     */
    public function set_selectlist($a_sel_list): void
    {
        $this->_a_disp_sel_list = $a_sel_list;
    }
    public function get_media(int $position): Product_Media_View
    {
        return Container_Facade::get(Product_Media_View_Service_Interface::class)->get_by_position(Id::from_string($this->get_id()), $position);
    }
    public function get_icon(): Product_Media_View
    {
        return Container_Facade::get(Product_Media_View_Service_Interface::class)->get_by_role(Id::from_string($this->get_id()), Product_Media_Role::from(Product_Media_Role::ICON));
    }
    public function get_thumbnail(): Product_Media_View
    {
        return Container_Facade::get(Product_Media_View_Service_Interface::class)->get_by_role(Id::from_string($this->get_id()), Product_Media_Role::from(Product_Media_Role::THUMBNAIL));
    }
    /**
     * apply article and article use
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice target price
     */
    public function apply_vats(Price $o_price): void
    {
        $this->apply_vat($o_price, $this->get_article_vat());
    }
    /**
     * Applies discounts which should be applied in general case (for 0 amount)
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
     */
    public function apply_discounts_for_variant($o_price): void
    {
        // apply discounts
        if (!$this->skip_discounts()) {
            $o_discount_list = Registry::get(\Oxid_Esales\Eshop\Application\Model\Discount_List::class);
            $a_discounts = $o_discount_list->get_article_discounts($this, $this->get_article_user());
            reset($a_discounts);
            foreach ($a_discounts as $o_discount) {
                $o_price->set_discount($o_discount->get_add_sum(), $o_discount->get_add_sum_type());
            }
            $o_price->calculate_discount();
        }
    }
    /**
     * Get parent article
     *
     * @return Article
     */
    public function get_parent_article()
    {
        if ($this->oxarticles__oxparentid && $s_parent_id = $this->oxarticles__oxparentid->value) {
            $s_index = $s_parent_id . '_' . $this->get_language();
            if (!isset(self::$_a_loaded_parents[$s_index])) {
                self::$_a_loaded_parents[$s_index] = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                self::$_a_loaded_parents[$s_index]->_bl_load_price = false;
                self::$_a_loaded_parents[$s_index]->_bl_load_variants = false;
                if (!self::$_a_loaded_parents[$s_index]->load_in_lang($this->get_language(), $s_parent_id)) {
                    //return false in case parent product failed to load
                    self::$_a_loaded_parents[$s_index] = false;
                }
            }
            return self::$_a_loaded_parents[$s_index];
        }
    }
    /**
     * Updates article variants oxremindactive field, as variants inherit this setting from parent
     */
    public function update_variants_remind(): void
    {
        // check if it is parent article
        if (!$this->is_variant() && $this->has_any_variant()) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_update = 'update oxarticles
                        set oxremindactive = :oxremindactive
                        where oxparentid = :oxparentid and
                              oxshopid = :oxshopid';
            $o_db->execute($s_update, ['oxremindactive' => $this->oxarticles__oxremindactive->value, 'oxparentid' => $this->get_id(), 'oxshopid' => $this->get_shop_id()]);
        }
    }
    /**
     * Returns product id (oxid)
     * (required for interface oxIArticle)
     *
     * @return string
     */
    public function get_product_id()
    {
        return $this->get_id();
    }
    /**
     * Returns product parent id (oxparentid)
     *
     * @return string
     */
    public function get_parent_id()
    {
        return $this->oxarticles__oxparentid instanceof Field ? $this->oxarticles__oxparentid->value : '';
    }
    /**
     * Returns false if object is not derived from oxorderarticle class
     *
     * @return bool
     */
    public function is_order_article()
    {
        return false;
    }
    /**
     * Returns TRUE if product is variant, and false if not
     */
    public function is_variant(): bool
    {
        if (isset($this->oxarticles__oxparentid) && false !== $this->oxarticles__oxparentid) {
            return (bool) $this->oxarticles__oxparentid->value;
        }
        return false;
    }
    /**
     * Returns TRUE if product is multidimensional variant, and false if not
     *
     * @return bool
     */
    public function is_md_variant()
    {
        $o_md_variant = ox_new(\Oxid_Esales\Eshop\Application\Model\Variant_Handler::class);
        return $o_md_variant->is_md_variant($this);
    }
    /**
     * get Sql for loading price categories which include this article
     *
     * @param string $sFields fields to load from oxCategories
     *
     * @return string
     */
    public function get_sql_for_price_categories($s_fields = '')
    {
        if (!$s_fields) {
            $s_fields = 'oxid';
        }
        $s_select_where = "select {$s_fields} from " . $this->get_object_view_name('oxcategories') . ' where';
        $s_quoted_price = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($this->get_field_data('oxprice') ?? '');
        return "{$s_select_where} oxpricefrom != 0 and oxpriceto != 0" . " and oxpricefrom <= {$s_quoted_price} and oxpriceto >= {$s_quoted_price}" . " union {$s_select_where} oxpricefrom != 0 and oxpriceto = 0 and oxpricefrom <= {$s_quoted_price}" . " union {$s_select_where} oxpricefrom = 0 and oxpriceto != 0 and oxpriceto >= {$s_quoted_price}";
    }
    /**
     * Checks if article is assigned to price category $sCatNID.
     *
     * @param string $categoryPriceId Price category ID
     *
     * @return bool
     */
    public function in_price_category($category_price_id)
    {
        return (bool) $this->fetch_first_in_price_category($category_price_id);
    }
    /**
     * Fetch the article corresponding to this object in the price category with the given id.
     *
     * @param string $categoryPriceId The id of the category we want to check, if this article is in.
     *
     * @return string One, if the given article is in the given price category, else empty string.
     */
    protected function fetch_first_in_price_category($category_price_id)
    {
        $database = $this->get_database();
        $query = $this->create_fetch_first_in_price_category_sql($category_price_id);
        return $database->get_one($query);
    }
    /**
     * Create the sql for the fetchFirstInPriceCategory method.
     *
     * @param string $categoryPriceId The price category id.
     *
     * @return string The wished sql.
     */
    protected function create_fetch_first_in_price_category_sql($category_price_id)
    {
        $database = $this->get_database();
        $quoted_price = $database->quote($this->oxarticles__oxprice->value);
        $quoted_category_id = $database->quote($category_price_id);
        return 'select 1 from ' . $this->get_object_view_name('oxcategories') . " where oxid={$quoted_category_id} and" . "(   (oxpricefrom != 0 and oxpriceto != 0 and oxpricefrom <= {$quoted_price} and oxpriceto >= {$quoted_price})" . " or (oxpricefrom != 0 and oxpriceto = 0 and oxpricefrom <= {$quoted_price})" . " or (oxpricefrom = 0 and oxpriceto != 0 and oxpriceto >= {$quoted_price})" . ')';
    }
    /**
     * Get the database object.
     *
     * @return \OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface
     */
    protected function get_database()
    {
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
    }
    /**
     * Returns multidimensional variant structure
     *
     * @return \OxidEsales\Eshop\Application\Model\MdVariant
     */
    public function get_md_variants()
    {
        if ($this->_o_md_variants) {
            return $this->_o_md_variants;
        }
        $o_parent_article = $this->get_parent_article();
        if ($o_parent_article) {
            $o_variants = $o_parent_article->get_variants();
        } else {
            $o_variants = $this->get_variants();
        }
        /** @var \OxidEsales\Eshop\Application\Model\VariantHandler $oVariantHandler */
        $o_variant_handler = ox_new(\Oxid_Esales\Eshop\Application\Model\Variant_Handler::class);
        $this->_o_md_variants = $o_variant_handler->build_md_variants($o_variants, $this->get_id());
        return $this->_o_md_variants;
    }
    /**
     * Returns first level variants from multidimensional variants list
     *
     * @return \OxidEsales\Eshop\Application\Model\MdVariant
     */
    public function get_md_subvariants()
    {
        return $this->get_md_variants()->get_md_subvariants();
    }
    /**
     * Return article picture file name
     *
     * @param string $sFieldName article picture field name
     * @param int    $iIndex     article picture index
     *
     * @return string
     */
    public function get_picture_field_value($s_field_name, $i_index = null)
    {
        if ($s_field_name) {
            $s_field_name = 'oxarticles__' . $s_field_name . $i_index;
            if ($this->{$s_field_name} && $this->{$s_field_name}->value) {
                return $this->{$s_field_name}->value;
            }
        }
        return '';
    }
    public function get_master_picture_path(string $file): string
    {
        return Registry::get_config()->get_master_picture_path($file);
    }
    /**
     * Returns oxarticles__oxunitname value processed by \OxidEsales\Eshop\Core\Language::translateString()
     *
     * @return string
     */
    public function get_unit_name()
    {
        if ($this->oxarticles__oxunitname->value) {
            return Registry::get_lang()->translate_string($this->oxarticles__oxunitname->value);
        }
    }
    public function get_article_files($add_from_parent = false)
    {
        if ($this->_a_article_files === null) {
            $this->_a_article_files = false;
            $files_query = 'SELECT * FROM `oxfiles` WHERE `oxartid` = :oxartid';
            $files_query_parameters = ['oxartid' => $this->get_id()];
            if (!Registry::get_config()->get_config_param('blVariantParentBuyable') && $add_from_parent) {
                $files_query .= ' OR `oxartId` = :oxparentid';
                $files_query_parameters['oxparentid'] = $this->oxarticles__oxparentid->value;
            }
            $article_files = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $article_files->init('oxfile');
            $article_files->select_string($files_query, $files_query_parameters);
            $this->_a_article_files = $article_files;
        }
        return $this->_a_article_files;
    }
    /**
     * Returns oxarticles__oxisdownloadable value
     *
     * @return bool
     */
    public function is_downloadable()
    {
        return $this->oxarticles__oxisdownloadable->value;
    }
    /**
     * Checks if articles has amount price
     *
     * @return bool
     */
    public function has_amount_price()
    {
        if (self::$_bl_has_amount_price === null) {
            self::$_bl_has_amount_price = false;
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'SELECT 1 FROM `oxprice2article` LIMIT 1';
            if ($o_db->get_one($s_q)) {
                self::$_bl_has_amount_price = true;
            }
        }
        return self::$_bl_has_amount_price;
    }
    /**
     * Loads and returns variants list.
     *
     * @param bool      $loadSimpleVariants    if parameter $blSimple - list will be filled with oxSimpleVariant
     *                                         objects, else - oxArticle
     * @param bool      $blRemoveNotOrderables if true, removes from list not orderable articles, which are out of
     *                                         stock [optional]
     * @param bool|null $forceCoreTableUsage   if true forces core table use, default is false [optional]
     *
     * @return array|\OxidEsales\Eshop\Application\Model\SimpleVariantList|\OxidEsales\Eshop\Application\Model\ArticleList
     */
    protected function load_variant_list($load_simple_variants, $bl_remove_not_orderables = true, $force_core_table_usage = null)
    {
        $variants = [];
        if ($article_id = $this->get_id()) {
            //do not load me as a parent later
            self::$_a_loaded_parents[$article_id . '_' . $this->get_language()] = $this;
            $config = Registry::get_config();
            if (!$this->_bl_load_variants || !$this->is_admin() && !$config->get_config_param('blLoadVariants') || !$this->is_admin() && !$this->oxarticles__oxvarcount->value) {
                return $variants;
            }
            // cache
            $cache_key = $load_simple_variants ? 'simple' : 'full';
            if ($bl_remove_not_orderables) {
                if (isset($this->_a_variants[$cache_key])) {
                    return $this->_a_variants[$cache_key];
                }
                $this->_a_variants[$cache_key] =& $variants;
            } elseif (!$bl_remove_not_orderables) {
                if (isset($this->_a_variants_with_not_orderables[$cache_key])) {
                    return $this->_a_variants_with_not_orderables[$cache_key];
                }
                $this->_a_variants_with_not_orderables[$cache_key] =& $variants;
            }
            if ($this->_bl_has_variants = $this->has_any_variant($force_core_table_usage)) {
                //load simple variants for lists
                if ($load_simple_variants) {
                    $variants = ox_new(\Oxid_Esales\Eshop\Application\Model\Simple_Variant_List::class);
                    $variants->set_parent($this);
                } else {
                    //loading variants
                    $variants = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
                    $variants->get_base_object()->modify_cache_key('_variants');
                }
                start_profile('selectVariants');
                $force_core_table_usage = (bool) $force_core_table_usage;
                $base_object = $variants->get_base_object();
                $this->update_variants_base_object($base_object, $force_core_table_usage);
                $s_article_table = $this->get_view_name($force_core_table_usage);
                $query = $this->get_load_variants_query($bl_remove_not_orderables, $force_core_table_usage, $base_object, $s_article_table);
                $variants->select_string($query);
                //if this is multidimensional variants, make additional processing
                if ($config->get_config_param('blUseMultidimensionVariants')) {
                    $o_md_variants = ox_new(\Oxid_Esales\Eshop\Application\Model\Variant_Handler::class);
                    $this->_bl_has_md_variants = $o_md_variants->is_md_variant($variants->current());
                }
                stop_profile('selectVariants');
            }
            //if we have variants then depending on config option the parent may be non buyable
            if (!$config->get_config_param('blVariantParentBuyable') && $this->_bl_has_variants) {
                $this->_bl_not_buyable_parent = true;
            }
            // If all variants are inactive, the article may be non-buyable (config-dependent)
            if (!$config->get_config_param('blVariantParentBuyable') && count($variants) == 0 && $this->_bl_has_variants) {
                $this->_bl_not_buyable = true;
            }
        }
        return $variants;
    }
    /**
     * Selects category IDs from given SQL statement and ID field name
     *
     * @param string $query sql statement
     * @param string $field category ID field name
     *
     * @return array
     */
    protected function select_category_ids($query, $field)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $a_result = $o_db->get_all($query);
        $a_return = [];
        foreach ($a_result as $a_value) {
            $a_value = array_change_key_case($a_value, CASE_LOWER);
            $a_return[] = $a_value[$field];
        }
        return $a_return;
    }
    /**
     * Returns query for article categories select
     *
     * @param bool $blActCats select categories if all parents are active
     *
     * @return string
     */
    protected function get_category_ids_select($bl_act_cats = false)
    {
        $s_o2c_view = $this->get_object_view_name('oxobject2category');
        $s_cat_view = $this->get_object_view_name('oxcategories');
        $s_article_id_sql = 'oxobject2category.oxobjectid=' . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($this->get_id());
        if ($this->get_parent_id()) {
            $s_article_id_sql = '(' . $s_article_id_sql . ' or oxobject2category.oxobjectid=' . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($this->get_parent_id()) . ')';
        }
        $s_active_category_sql = $bl_act_cats ? $this->get_active_category_select_snippet() : '';
        return "select\n                        oxobject2category.oxcatnid as oxcatnid\n                     from {$s_o2c_view} as oxobject2category\n                        left join {$s_cat_view} as oxcategories on oxcategories.oxid = oxobject2category.oxcatnid\n                    where {$s_article_id_sql} and oxcategories.oxid is not null\n                    and oxcategories.oxactive = 1 {$s_active_category_sql}\n                    order by oxobject2category.oxtime";
    }
    /**
     * Returns active category select snippet
     *
     * @return string
     */
    protected function get_active_category_select_snippet()
    {
        $s_cat_view = $this->get_object_view_name('oxcategories');
        return "and oxcategories.oxhidden = 0 and (select count(cats.oxid) from {$s_cat_view} as cats" . ' where cats.oxrootid = oxcategories.oxrootid and cats.oxleft < oxcategories.oxleft ' . 'and cats.oxright > oxcategories.oxright and ( cats.oxhidden = 1 or cats.oxactive = 0 ) ) = 0 ';
    }
    /**
     * Calculates price of article (adds taxes, currency and discounts).
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice price object
     * @param double                       $dVat   vat value, optional, if passed, bypasses
     *                                             "bl_perfCalcVatOnlyForBasketOrder" config value
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    protected function calculate_price($o_price, $d_vat = null)
    {
        // apply VAT only if configuration requires it
        if (isset($d_vat) || !Registry::get_config()->get_config_param('bl_perfCalcVatOnlyForBasketOrder')) {
            $this->apply_vat($o_price, $d_vat ?? $this->get_article_vat());
        }
        // apply currency
        $this->apply_currency($o_price);
        // apply discounts
        if (!$this->skip_discounts()) {
            $o_discount_list = Registry::get(\Oxid_Esales\Eshop\Application\Model\Discount_List::class);
            $a_discounts = $o_discount_list->get_article_discounts($this, $this->get_article_user());
            reset($a_discounts);
            foreach ($a_discounts as $o_discount) {
                $o_price->set_discount($o_discount->get_add_sum(), $o_discount->get_add_sum_type());
            }
            $o_price->calculate_discount();
        }
        return $o_price;
    }
    /**
     * Checks if parent has ANY variant assigned
     *
     * @param bool $blForceCoreTable force core table usage
     *
     * @return bool
     */
    protected function has_any_variant($bl_force_core_table = null)
    {
        if ($s_id = $this->get_id()) {
            if ($this->oxarticles__oxshopid->value == Registry::get_config()->get_shop_id()) {
                return (bool) $this->oxarticles__oxvarcount->value;
            }
            $s_article_table = $this->get_view_name($bl_force_core_table);
            $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            return (bool) $db->get_one("select 1 from {$s_article_table} where oxparentid = :oxparentid", ['oxparentid' => $s_id]);
        }
        return false;
    }
    /**
     * Check if stock status has changed since loading the article
     *
     * @return bool
     */
    protected function is_stock_status_changed()
    {
        return $this->_i_stock_status != $this->_i_stock_status_on_load;
    }
    /**
     * Check if visibility has changed since loading the article
     *
     * @return bool
     */
    protected function is_visibility_changed()
    {
        return $this->is_stock_status_changed() && ($this->_i_stock_status == -1 || $this->_i_stock_status_on_load == -1);
    }
    /**
     * inserts article long description to artextends table
     */
    protected function save_art_long_desc()
    {
        if (in_array('oxlongdesc', $this->_a_skip_save_fields)) {
            return;
        }
        if ($this->_bl_employ_multilanguage) {
            $s_value = $this->get_long_description()->get_raw_value();
            if ($s_value !== null) {
                $o_art_ext = ox_new(Multi_Language_Model::class);
                $o_art_ext->init('oxartextends');
                $o_art_ext->set_language((int) $this->get_language());
                if (!$o_art_ext->load($this->get_id())) {
                    $o_art_ext->set_id($this->get_id());
                }
                $o_art_ext->oxartextends__oxlongdesc = new Field($s_value, Field::T_RAW);
                $o_art_ext->save();
            }
        } else {
            $o_art_ext = ox_new(Multi_Language_Model::class);
            $o_art_ext->set_enable_multilang(false);
            $o_art_ext->init('oxartextends');
            $a_obj_fields = $o_art_ext->get_all_fields(true);
            if (!$o_art_ext->load($this->get_id())) {
                $o_art_ext->set_id($this->get_id());
            }
            foreach ($a_obj_fields as $s_key => $s_value) {
                if (preg_match('/^oxlongdesc(_(\d{1,2}))?$/', (string) $s_key)) {
                    $s_field = $this->get_field_long_name($s_key);
                    if (isset($this->{$s_field})) {
                        $s_long_desc = null;
                        if ($this->{$s_field} instanceof Field) {
                            $s_long_desc = $this->{$s_field}->get_raw_value();
                        } elseif (is_object($this->{$s_field})) {
                            $s_long_desc = $this->{$s_field}->value;
                        }
                        if (isset($s_long_desc)) {
                            $s_ae_field = $o_art_ext->get_field_long_name($s_key);
                            $o_art_ext->{$s_ae_field} = new Field($s_long_desc, Field::T_RAW);
                        }
                    }
                }
            }
            $o_art_ext->save();
        }
    }
    /**
     * Removes object data fields (oxarticles__oxtimestamp, oxarticles__oxparentid, oxarticles__oxinsert).
     */
    protected function skip_save_fields()
    {
        $this->_a_skip_save_fields = [];
        $this->_a_skip_save_fields[] = 'oxtimestamp';
        // $this->_aSkipSaveFields[] = 'oxlongdesc';
        $this->_a_skip_save_fields[] = 'oxinsert';
        $this->add_skipped_save_fields_for_mapping();
        if (!$this->_bl_allow_empty_parent_id && (!isset($this->oxarticles__oxparentid->value) || $this->oxarticles__oxparentid->value == '')) {
            $this->_a_skip_save_fields[] = 'oxparentid';
        }
    }
    /**
     * Merges two discount arrays. If there are two the same
     * discounts, discount values will be added.
     *
     * @param array $aDiscounts     Discount array
     * @param array $aItemDiscounts Discount array
     *
     * @return array $aDiscounts
     */
    protected function merge_discounts($a_discounts, $a_item_discounts)
    {
        foreach ($a_item_discounts as $s_key => $o_discount) {
            // add prices of the same discounts
            if (array_key_exists($s_key, $a_discounts)) {
                $a_discounts[$s_key]->d_discount += $o_discount->d_discount;
            } else {
                $a_discounts[$s_key] = $o_discount;
            }
        }
        return $a_discounts;
    }
    /**
     * get user Group A, B or C price, returns db price if user is not in groups
     *
     * @return double
     */
    protected function get_group_price()
    {
        $s_price_sufix = $this->get_user_price_sufix();
        $s_var_name = "oxarticles__oxprice{$s_price_sufix}";
        $d_price = $this->{$s_var_name}->value;
        // #1437/1436C - added config option, and check for zero A,B,C price values
        if (Registry::get_config()->get_config_param('blOverrideZeroABCPrices') && (float) $d_price == 0) {
            return $this->oxarticles__oxprice->value;
        }
        return $d_price;
    }
    /**
     * Modifies article price depending on given amount.
     * Takes data from oxprice2article table.
     *
     * @param int $amount Basket amount
     *
     * @return double
     */
    protected function get_amount_price($amount = 1)
    {
        start_profile('_getAmountPrice');
        $d_price = $this->get_group_price();
        $o_amt_prices = $this->build_amount_price_list();
        foreach ($o_amt_prices as $o_am_price) {
            if ($o_am_price->oxprice2article__oxamount->value <= $amount && $amount <= $o_am_price->oxprice2article__oxamountto->value && $d_price > $o_am_price->oxprice2article__oxaddabs->value) {
                $d_price = $o_am_price->oxprice2article__oxaddabs->value;
            }
        }
        stop_profile('_getAmountPrice');
        return $d_price;
    }
    /**
     * Modifies article price according to selected select list value
     *
     * @param double $dPrice      Modifiable price
     * @param array  $aChosenList Selection list array
     *
     * @return double
     */
    protected function modify_select_list_price($d_price, $a_chosen_list = null)
    {
        $my_config = Registry::get_config();
        // #690
        if ($my_config->get_config_param('bl_perfLoadSelectLists') && $my_config->get_config_param('bl_perfUseSelectlistPrice')) {
            $a_sel_lists = $this->get_select_lists();
            foreach ($a_sel_lists as $key => $a_sel) {
                if (isset($a_chosen_list[$key]) && isset($a_sel[$a_chosen_list[$key]])) {
                    $o_sel = $a_sel[$a_chosen_list[$key]];
                    if ($o_sel->price_unit == 'abs') {
                        $d_price += $o_sel->price;
                    } elseif ($o_sel->price_unit == '%') {
                        $d_price += Price::percent($d_price, $o_sel->price);
                    }
                }
            }
        }
        return $d_price;
    }
    /**
     * Fills amount price list object and sets amount price for article object
     *
     * @param array $aAmPriceList Amount price list
     *
     * @return array
     */
    protected function fill_amount_price_list($a_am_price_list)
    {
        $o_lang = Registry::get_lang();
        // trying to find lowest price value
        foreach ($a_am_price_list as $s_id => $o_item) {
            /** @var \OxidEsales\Eshop\Core\Price $oItemPrice */
            $o_item_price = $this->get_price_object();
            if ($o_item->oxprice2article__oxaddabs->value) {
                $d_base_price = $o_item->oxprice2article__oxaddabs->value;
                $d_base_price = $this->prepare_modified_price($d_base_price);
                $o_item_price->set_price($d_base_price);
                $this->calculate_price($o_item_price);
            } else {
                $d_base_price = $this->get_group_price();
                $d_base_price = $this->prepare_modified_price($d_base_price);
                $o_item_price->set_price($d_base_price);
                $o_item_price->subtract_percent($o_item->oxprice2article__oxaddperc->value);
            }
            $a_am_price_list[$s_id]->fbrutprice = $o_lang->format_currency($o_item_price->get_brutto_price());
            $a_am_price_list[$s_id]->fnetprice = $o_lang->format_currency($o_item_price->get_netto_price());
            if ($quantity = $this->get_unit_quantity()) {
                $a_am_price_list[$s_id]->fbrutamountprice = $o_lang->format_currency($o_item_price->get_brutto_price() / $quantity);
                $a_am_price_list[$s_id]->fnetamountprice = $o_lang->format_currency($o_item_price->get_netto_price() / $quantity);
            }
        }
        return $a_am_price_list;
    }
    /**
     * Collects and returns active/all variant ids of article.
     *
     * @param bool $blActiveVariants Parameter to load only active variants.
     *
     * @return array
     */
    public function get_variant_ids($active_variants = true)
    {
        $s_id = $this->get_id();
        if (!$s_id) {
            return [];
        }
        $active_sql_snippet = '';
        if ($active_variants) {
            $active_sql_snippet = ' and ' . $this->get_sql_active_snippet(true);
        }
        $variants_query = sprintf('select oxid from %s where oxparentid = :oxparentid %s order by oxsort', $this->get_view_name(true), $active_sql_snippet);
        return Database_Provider::get_db()->get_col($variants_query, ['oxparentid' => $s_id]);
    }
    /**
     * retrieve article VAT (cached)
     *
     * @return double
     */
    public function get_article_vat()
    {
        if (!isset($this->_d_article_vat)) {
            $this->_d_article_vat = Registry::get(\Oxid_Esales\Eshop\Application\Model\Vat_Selector::class)->get_article_vat($this);
        }
        return $this->_d_article_vat;
    }
    public function has_product_valid_time_range(): bool
    {
        if (!Registry::get_utils_date()->is_empty_date($this->oxarticles__oxactivefrom->value)) {
            return true;
        }
        return !Registry::get_utils_date()->is_empty_date($this->oxarticles__oxactiveto->value);
    }
    public function is_product_always_active(): bool
    {
        return !empty($this->oxarticles__oxactive->value);
    }
    /**
     * Applies VAT to article
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
     * @param double                       $dVat   VAT percent
     */
    protected function apply_vat(Price $o_price, $d_vat)
    {
        start_profile(__FUNCTION__);
        $o_price->set_vat($d_vat);
        /** @var \OxidEsales\Eshop\Application\Model\VatSelector $oVatSelector */
        $o_vat_selector = Registry::get(\Oxid_Esales\Eshop\Application\Model\Vat_Selector::class);
        if (($d_vat = $o_vat_selector->get_article_user_vat($this)) !== false) {
            $o_price->set_user_vat($d_vat);
        }
        stop_profile(__FUNCTION__);
    }
    /**
     * Applies currency factor
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
     * @param object                       $oCur   Currency object
     */
    protected function apply_currency(Price $o_price, $o_cur = null)
    {
        if (!$o_cur) {
            $o_cur = Registry::get_config()->get_act_shop_currency_object();
        }
        $o_price->multiply($o_cur->rate);
    }
    /**
     * gets attribs string
     *
     * @param string $sAttributeSql Attribute selection snippet
     * @param int    $iCnt          The number of selected attributes
     */
    protected function get_attribs_string(&$s_attribute_sql, &$i_cnt)
    {
        // we do not use lists here as we don't need this overhead right now
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_select = 'select oxattrid from oxobject2attribute
            where oxobject2attribute.oxobjectid = :oxobjectid';
        if ($this->get_parent_id()) {
            $s_select .= ' OR oxobject2attribute.oxobjectid = :oxparentid';
        }
        $s_attribute_sql = '';
        $a_attribute_ids = $o_db->get_col($s_select, ['oxobjectid' => $this->get_id(), 'oxparentid' => $this->get_parent_id()]);
        if (is_array($a_attribute_ids) && count($a_attribute_ids)) {
            $a_attribute_ids = array_unique($a_attribute_ids);
            $i_cnt = count($a_attribute_ids);
            $s_attribute_sql .= 't1.oxattrid IN ( ' . implode(',', $o_db->quote_array($a_attribute_ids)) . ') ';
        }
    }
    /**
     * Gets similar list.
     *
     * @param string $sAttributeSql Attribute selection snippet
     * @param int    $iCnt          Similar list article count
     *
     * @return array
     */
    protected function get_sim_list($s_attribute_sql, $i_cnt)
    {
        // #523A
        $i_attr_percent = Registry::get_config()->get_config_param('iAttributesPercent') / 100;
        // 70% same attributes
        if (!$i_attr_percent || $i_attr_percent < 0 || $i_attr_percent > 1) {
            $i_attr_percent = 0.7;
        }
        // #1137V iAttributesPercent = 100 doesn't work
        $i_hit_min = ceil($i_cnt * $i_attr_percent);
        $a_exclude_ids = [];
        $a_exclude_ids[] = $this->get_id();
        if ($this->get_parent_id()) {
            $a_exclude_ids[] = $this->get_parent_id();
        }
        // we do not use lists here as we don't need this overhead right now
        $s_select = "select oxobjectid from oxobject2attribute as t1 where ( {$s_attribute_sql} ) and t1.oxobjectid NOT IN (" . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_exclude_ids)) . ') group by t1.oxobjectid having count(*) >= :minhit LIMIT 0, 20';
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_col($s_select, ['minhit' => $i_hit_min]);
    }
    /**
     * Generates search string for similar list.
     *
     * @param string $sArticleTable Article table name
     * @param array  $aList         A list of original articles
     *
     * @return string
     */
    protected function generate_sim_list_search_str($s_article_table, $a_list)
    {
        $s_field_list = $this->get_select_fields();
        $a_list = array_slice($a_list, 0, Registry::get_config()->get_config_param('iNrofSimilarArticles'));
        $s_search = "select {$s_field_list} from {$s_article_table} where " . $this->get_sql_active_snippet() . "  and {$s_article_table}.oxissearch = 1 and {$s_article_table}.oxid in ( ";
        $s_search .= implode(',', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_list)) . ')';
        // #524A -- randomizing articles in attribute list
        $s_search .= ' order by rand() ';
        return $s_search;
    }
    /**
     * Generates SearchString for getCategory()
     *
     * @param string $sOXID            Article ID
     * @param bool   $blSearchPriceCat Whether to perform the search within price categories
     *
     * @return string
     */
    protected function generate_search_str($s_oxid, $bl_search_price_cat = false)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_cat_view = $table_view_name_generator->get_view_name('oxcategories', $this->get_language());
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        // we do not use lists here as we don't need this overhead right now
        if (!$bl_search_price_cat) {
            return "select {$s_cat_view}.* from {$s_o2c_view} as oxobject2category left join {$s_cat_view} on\n                  {$s_cat_view}.oxid = oxobject2category.oxcatnid where oxobject2category.oxobjectid=" . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($s_oxid) . " and {$s_cat_view}.oxid is not null ";
        }
        return "select {$s_cat_view}.* from {$s_cat_view} where\n                      '{$this->oxarticles__oxprice->value}' >= {$s_cat_view}.oxpricefrom and\n                      '{$this->oxarticles__oxprice->value}' <= {$s_cat_view}.oxpriceto ";
    }
    /**
     * Generates SQL select string for getCustomerAlsoBoughtThisProduct
     *
     * @return string
     */
    protected function generate_search_str_for_customer_bought()
    {
        $s_art_table = $this->get_view_name();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_order_art_table = $table_view_name_generator->get_view_name('oxorderarticles');
        // fetching filter params
        $articles_in = " '{$this->oxarticles__oxid->value}' ";
        if ($this->oxarticles__oxparentid->value) {
            // adding article parent
            $articles_in .= ", '{$this->oxarticles__oxparentid->value}' ";
            $s_parent_id_for_variants = $this->oxarticles__oxparentid->value;
        } else {
            $s_parent_id_for_variants = $this->get_id();
        }
        $database = Database_Provider::get_db();
        $articles_ids = $database->get_col("select oxid from {$s_art_table} where oxparentid = :oxparentid and oxid != :oxid ", ['oxparentid' => $s_parent_id_for_variants, 'oxid' => $this->oxarticles__oxid->value]);
        foreach ($articles_ids as $articles_id) {
            $articles_in .= ', ' . $database->quote($articles_id) . ' ';
        }
        $i_limit = (int) Registry::get_config()->get_config_param('iNrofCustomerWhoArticles');
        $i_limit = $i_limit ? $i_limit * 10 : 50;
        return "select distinct {$s_art_table}.* from (" . " select d.oxorderid as suborderid from {$s_order_art_table} as d use index" . " ( oxartid ) where d.oxartid in ( {$articles_in} ) limit {$i_limit}" . ' ) as suborder' . " left join {$s_order_art_table} force index ( oxorderid )" . " on suborder.suborderid = {$s_order_art_table}.oxorderid" . " left join {$s_art_table} on {$s_art_table}.oxid = {$s_order_art_table}.oxartid" . " where {$s_art_table}.oxid not in ( {$articles_in} )" . " and ( {$s_art_table}.oxissearch = 1 or {$s_art_table}.oxparentid <> '' )" . ' and ' . $this->get_sql_active_snippet();
    }
    /**
     * Generates select string for isAssignedToCategory()
     *
     * @param string $sOXID        Article ID
     * @param string $sCatId       Category ID
     * @param bool   $dPriceFromTo Article price for price categories
     *
     * @return string
     */
    protected function generate_select_cat_str($s_oxid, $s_cat_id, $d_price_from_to = false)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_category_view = $table_view_name_generator->get_view_name('oxcategories');
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_oxid = $o_db->quote($s_oxid);
        $s_cat_id = $o_db->quote($s_cat_id);
        if (!$d_price_from_to) {
            $s_select = "select oxobject2category.oxcatnid from {$s_o2c_view} as oxobject2category ";
            $s_select .= "left join {$s_category_view} as oxcategories on oxcategories.oxid = oxobject2category.oxcatnid ";
            $s_select .= "where oxobject2category.oxcatnid={$s_cat_id} and oxobject2category.oxobjectid={$s_oxid} ";
            $s_select .= 'and oxcategories.oxactive = 1 order by oxobject2category.oxtime ';
        } else {
            $d_price_from_to = $o_db->quote($d_price_from_to);
            $s_select = "select oxcategories.oxid from {$s_category_view} as oxcategories where ";
            $s_select .= "oxcategories.oxid={$s_cat_id} and {$d_price_from_to} >= oxcategories.oxpricefrom and ";
            $s_select .= "{$d_price_from_to} <= oxcategories.oxpriceto ";
        }
        return $s_select;
    }
    /**
     * Collecting assigned to article amount-price list.
     *
     * @return \OxidEsales\Eshop\Application\Model\AmountPriceList
     */
    protected function build_amount_price_list()
    {
        if ($this->get_amount_price_list() === null) {
            /** @var \OxidEsales\Eshop\Application\Model\AmountPriceList $oAmPriceList */
            $o_am_price_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Amount_Price_List::class);
            $this->set_amount_price_list($o_am_price_list);
            if (!$this->skip_discounts()) {
                //collecting assigned to article amount-price list
                $o_am_price_list->load($this);
                // prepare abs prices if currently having percentages
                $o_base_price = $this->get_group_price();
                foreach ($o_am_price_list as $o_am_price) {
                    if ($o_am_price->oxprice2article__oxaddperc->value) {
                        $o_am_price->oxprice2article__oxaddabs = new Field(Price::percent($o_base_price, 100 - $o_am_price->oxprice2article__oxaddperc->value), Field::T_RAW);
                    }
                }
            }
            $this->set_amount_price_list($o_am_price_list);
        }
        return $this->_o_amount_price_list;
    }
    /**
     * Detects if field is empty.
     *
     * @param string $sFieldName Field name
     *
     * @return bool
     */
    protected function is_field_empty($s_field_name)
    {
        $m_value = $this->{$s_field_name}->value;
        if (is_null($m_value)) {
            return true;
        }
        if ($m_value === '') {
            return true;
        }
        // certain fields with zero value treat as empty
        $a_zero_value_fields = ['oxarticles__oxprice', 'oxarticles__oxvat', 'oxarticles__oxunitquantity'];
        if (!$m_value && in_array($s_field_name, $a_zero_value_fields)) {
            return true;
        }
        if (!strcmp((string) $m_value, '0000-00-00 00:00:00') || !strcmp((string) $m_value, '0000-00-00')) {
            return true;
        }
        $s_field_name = strtolower($s_field_name);
        if ($s_field_name == 'oxarticles__oxicon' && (str_contains((string) $m_value, 'nopic_ico.jpg') || str_contains((string) $m_value, 'nopic.jpg'))) {
            return true;
        }
        if (str_contains((string) $m_value, 'nopic.jpg') && ($s_field_name == 'oxarticles__oxthumb' || str_starts_with($s_field_name, 'oxarticles__oxpic') || str_starts_with($s_field_name, 'oxarticles__oxzoom'))) {
            return true;
        }
        return false;
    }
    /**
     * Assigns parent field values to article
     *
     * @param string $sFieldName field name
     */
    protected function assign_parent_field_value($s_field_name)
    {
        if (!$o_parent_article = $this->get_parent_article()) {
            return;
        }
        $s_copy_field_name = $this->get_field_long_name($s_field_name);
        // assigning only these which parent article has
        if ($o_parent_article->{$s_copy_field_name} != null) {
            // only overwrite database values
            if (!str_starts_with($s_copy_field_name, 'oxarticles__')) {
                return;
            }
            //do not copy certain fields
            if (in_array($s_copy_field_name, $this->_a_non_copy_parent_fields)) {
                return;
            }
            //skip picture parent value assignment in case master image is set for variant
            if ($this->is_field_empty($s_copy_field_name) && $this->is_image_field($s_copy_field_name) && $this->has_master_image(1)) {
                return;
            }
            //COPY THE VALUE
            if ($this->is_field_empty($s_copy_field_name)) {
                $this->{$s_copy_field_name} = clone $o_parent_article->{$s_copy_field_name};
            }
        }
    }
    /**
     * Detects if field is an image field by field name
     *
     * @param string $sFieldName Field name
     *
     * @return bool
     */
    protected function is_image_field($s_field_name)
    {
        return stristr($s_field_name, '_oxthumb') || stristr($s_field_name, '_oxicon') || stristr($s_field_name, '_oxzoom') || stristr($s_field_name, '_oxpic');
    }
    /**
     * Assigns parent field values to article
     */
    protected function assign_parent_field_values()
    {
        start_profile('articleAssignParentInternal');
        if ($this->get_field_data('oxparentid')) {
            // yes, we are in fact a variant
            if (!$this->is_admin() || $this->_bl_load_parent_data && $this->is_admin()) {
                foreach ($this->_a_field_names as $s_field_name => $s_val) {
                    $this->assign_parent_field_value($s_field_name);
                }
            }
        }
        stop_profile('articleAssignParentInternal');
    }
    /**
     * if we have variants then depending on config option the parent may be non buyable
     */
    protected function assign_not_buyable_parent()
    {
        if (!Registry::get_config()->get_config_param('blVariantParentBuyable') && ($this->_bl_has_variants || $this->get_field_data('oxvarstock') || $this->get_field_data('oxvarcount'))) {
            $this->_bl_not_buyable_parent = true;
        }
    }
    /**
     * Assigns stock status to article
     */
    protected function assign_stock()
    {
        $my_config = Registry::get_config();
        // -----------------------------------
        // stock
        // -----------------------------------
        // #1125 A. must round (using floor()) value taken from database and cast to int
        if (!$my_config->get_config_param('blAllowUnevenAmounts') && !$this->is_admin()) {
            $stock = $this->get_field_data('oxstock') ?? 0;
            $this->oxarticles__oxstock = new Field((int) floor($stock));
        }
        //GREEN light
        $this->_i_stock_status = 0;
        // if we have flag /*1 or*/ 4 - we show always green light
        $stock_flag = $this->get_field_data('oxstockflag');
        if ($my_config->get_config_param('blUseStock') && $stock_flag != 4) {
            //ORANGE light
            $stock = $this->get_available_stock();
            if ($stock > 0 && $this->is_low_stock()) {
                $this->_i_stock_status = 1;
            }
            //RED light
            if ($stock <= 0) {
                $this->_i_stock_status = -1;
            }
        }
        // stock
        $stock_flag = $this->get_field_data('oxstockflag');
        if ($my_config->get_config_param('blUseStock') && ($stock_flag == 3 || $stock_flag == 2)) {
            $i_on_stock = $this->oxarticles__oxstock->value;
            if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
                $session = Registry::get_session();
                if ($reservations = $session->get_basket_reservations()) {
                    $i_on_stock += $reservations->get_reserved_amount($this->get_id());
                }
            }
            if ($i_on_stock <= 0) {
                $this->set_buyable_state(false);
            }
        }
        //exceptional handling for variant parent stock:
        if ($this->_bl_not_buyable && $this->oxarticles__oxvarstock->value) {
            $this->set_buyable_state(true);
            //but then at least setting notBuaybleParent to true
            $this->_bl_not_buyable_parent = true;
        }
        //special treatment for lists when blVariantParentBuyable config option is set to false
        //then we just hide "to basket" button.
        //If variants are not loaded in the list and this article has variants and parent is not buyable
        //then this article is not buyable
        if (!$my_config->get_config_param('blVariantParentBuyable') && !$my_config->get_config_param('blLoadVariants') && $this->oxarticles__oxvarstock->value) {
            $this->set_buyable_state(false);
        }
        // Set non-buyable if no variants (e.g. not loaded/inactive) and $this is a non-buyable parent
        if (!$this->_bl_not_buyable && $this->_bl_not_buyable_parent && $this->oxarticles__oxvarcount->value == 0) {
            $this->set_buyable_state(false);
        }
    }
    /**
     * assigns dynimagedir to article
     */
    protected function assign_dyn_image_dir()
    {
        $my_config = Registry::get_config();
        $s_this_shop = $this->oxarticles__oxshopid->value;
        $this->_s_dyn_image_dir = $my_config->get_picture_url(null, false);
        $this->dabsimagedir = $my_config->get_picture_dir(false);
        //$sThisShop
        $this->nossl_dimagedir = $my_config->get_picture_url(null, false, false, null, $s_this_shop);
        //$sThisShop
        $this->ssl_dimagedir = $my_config->get_picture_url(null, false, true, null, $s_this_shop);
        //$sThisShop
    }
    /**
     * Adds a flag if article is on comparisonlist.
     */
    protected function assign_comparison_list_flag()
    {
        // #657 add a flag if article is on comparisonlist
        $a_items = Registry::get_session()->get_variable('aFiltcompproducts');
        if (isset($a_items[$this->get_id()])) {
            $this->_bl_is_on_comparison_list = true;
        }
    }
    /**
     * Sets article creation date
     * (\OxidEsales\Eshop\Application\Model\Article::oxarticles__oxinsert). Then executes parent method
     * parent::_insert() and returns insertion status.
     *
     * @return bool
     */
    protected function insert()
    {
        // set oxinsert
        $s_now = date('Y-m-d H:i:s', Registry::get_utils_date()->get_time());
        $this->oxarticles__oxinsert = new Field($s_now);
        if (!is_object($this->oxarticles__oxsubclass) || $this->oxarticles__oxsubclass->value == '') {
            $this->oxarticles__oxsubclass = new Field('oxarticle');
        }
        return parent::insert();
    }
    /**
     * Executes \OxidEsales\Eshop\Application\Model\Article::_skipSaveFields() and updates article information
     *
     * @return bool
     */
    protected function update()
    {
        $this->set_update_seo(true);
        $this->set_update_seo_on_field_change('oxtitle');
        $this->skip_save_fields();
        return parent::update();
    }
    /**
     * Deletes records in database
     *
     * @param string $articleId Article ID
     *
     * @return int
     */
    protected function delete_records($article_id)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        //remove other records
        $s_delete = 'delete from oxobject2article where oxarticlenid = :articleId or oxobjectid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxobject2attribute where oxobjectid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxobject2category where oxobjectid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxobject2selectlist where oxobjectid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxprice2article where oxartid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxreviews where oxtype="oxarticle" and oxobjectid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxratings where oxobjectid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxaccessoire2article where oxobjectid = :articleId or oxarticlenid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        //#1508C - deleting oxobject2delivery entries added
        $s_delete = 'delete from oxobject2delivery where oxobjectid = :articleId and oxtype=\'oxarticles\' ';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxartextends where oxid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        //delete the record
        foreach ($this->get_language_set_tables('oxartextends') as $s_set_tbl) {
            $o_db->execute("delete from {$s_set_tbl} where oxid = :articleId", ['articleId' => $article_id]);
        }
        $s_delete = 'delete from oxactions2article where oxartid = :articleId';
        $o_db->execute($s_delete, ['articleId' => $article_id]);
        $s_delete = 'delete from oxobject2list where oxobjectid = :articleId';
        return $o_db->execute($s_delete, ['articleId' => $article_id]);
    }
    /**
     * Deletes variant records
     *
     * @param string $sOXID Article ID
     */
    protected function delete_variant_records($s_oxid)
    {
        if ($s_oxid) {
            //collect variants to remove recursively
            $query = 'select oxid from ' . $this->get_view_name() . ' where oxparentid = :oxparentid';
            $products = Database_Provider::get_db()->get_col($query, ['oxparentid' => $s_oxid]);
            $product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            foreach ($products as $product_id) {
                $product->set_id($product_id);
                $product->delete();
            }
        }
    }
    /**
     * Delete pics
     */
    protected function delete_pics()
    {
        $product_media_dao = Container_Facade::get(Product_Media_Dao_Interface::class);
        $product_id = Id::from_string($this->get_id());
        $media_collection = $product_media_dao->get_all($product_id);
        foreach ($media_collection as $product_media) {
            $product_media_dao->delete($product_media->get_id());
        }
    }
    /**
     * Resets category and vendor counts. This method is supposed to be called on article change trigger.
     *
     * @param string $sOxid           object to reset id ID
     * @param string $sVendorId       Vendor ID
     * @param string $sManufacturerId Manufacturer ID
     */
    protected function on_change_reset_counts($s_oxid, $s_vendor_id = null, $s_manufacturer_id = null)
    {
        $my_utils_count = Registry::get_utils_count();
        if ($s_vendor_id) {
            $my_utils_count->reset_vendor_article_count($s_vendor_id);
        }
        if ($s_manufacturer_id) {
            $my_utils_count->reset_manufacturer_article_count($s_manufacturer_id);
        }
        $a_category_ids = $this->get_category_ids();
        //also reseting category counts
        foreach ($a_category_ids as $s_cat_id) {
            $my_utils_count->reset_cat_article_count($s_cat_id);
        }
    }
    /**
     * Updates article stock. This method is supposed to be called on article change trigger.
     *
     * @param string $parentId product parent id
     */
    protected function on_change_update_stock($parent_id)
    {
        if ($parent_id) {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $query = 'SELECT oxstock, oxvendorid, oxmanufacturerid FROM oxarticles WHERE oxid = :oxid';
            $rs = $database->select($query, ['oxid' => $parent_id]);
            $query = 'SELECT SUM(oxstock) FROM ' . $this->get_view_name(true) . '
                WHERE oxparentid = :oxparentid
                AND ' . $this->get_sql_active_snippet(true) . '
                AND oxstock > 0 ';
            $stock = (float) $database->get_one($query, ['oxparentid' => $parent_id]);
            $query = 'UPDATE oxarticles SET oxvarstock = :oxvarstock WHERE oxid = :oxid';
            $database->execute($query, ['oxvarstock' => $stock, 'oxid' => $parent_id]);
            //now lets update category counts
            //first detect stock status change for this article (to or from 0)
            if ($stock < 0) {
                $stock = 0;
            }
            $old_stock = $rs->fields['oxstock'] ?? null;
            if ($old_stock < 0) {
                $old_stock = 0;
            }
            if ($this->get_field_data('oxstockflag') == 2 && $old_stock xor $stock) {
                //means the stock status could be changed (oxstock turns from 0 to 1 or from 1 to 0)
                // so far we leave it like this but later we could move all count resets to one or two functions
                $this->on_change_reset_counts($parent_id, $rs->fields['oxvendorid'] ?? null, $rs->fields['oxmanufacturerid'] ?? null);
            }
        }
    }
    /**
     * Resets article count cache when stock value is zero and article goes offline.
     *
     * @param string $sOxid product id
     */
    protected function on_change_stock_reset_count($s_oxid)
    {
        $my_config = Registry::get_config();
        if ($my_config->get_config_param('blUseStock') && $this->oxarticles__oxstockflag->value == 2 && $this->oxarticles__oxstock->value + $this->oxarticles__oxvarstock->value <= 0) {
            $this->on_change_reset_counts($s_oxid, $this->oxarticles__oxvendorid->value, $this->oxarticles__oxmanufacturerid->value);
        }
    }
    /**
     * Updates variant count. This method is supposed to be called on article change trigger.
     *
     * @param string $parentId Parent ID
     */
    protected function on_change_update_var_count($parent_id)
    {
        if ($parent_id) {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $query = 'SELECT COUNT(*) AS varcount FROM oxarticles WHERE oxparentid = :oxparentid';
            $var_count = (int) $database->get_one($query, ['oxparentid' => $parent_id]);
            $query = 'UPDATE oxarticles SET oxvarcount = :oxvarcount WHERE oxid = :oxid';
            $database->execute($query, ['oxvarcount' => $var_count, 'oxid' => $parent_id]);
        }
    }
    /**
     * Updates variant min price. This method is supposed to be called on article change trigger.
     *
     * @param string $sParentId Parent ID
     */
    protected function set_var_min_max_price($s_parent_id)
    {
        if ($s_parent_id) {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = '
                SELECT
                    MIN( IF( `oxarticles`.`oxprice` > 0, `oxarticles`.`oxprice`, `p`.`oxprice` ) ) AS `varminprice`,
                    MAX( IF( `oxarticles`.`oxprice` > 0, `oxarticles`.`oxprice`, `p`.`oxprice` ) ) AS `varmaxprice`
                FROM ' . $this->get_view_name(true) . ' AS `oxarticles`
                    LEFT JOIN ' . $this->get_view_name(true) . ' AS `p`
                     ON ( `p`.`oxid` = `oxarticles`.`oxparentid` AND `p`.`oxprice` > 0 )
                WHERE ' . $this->get_sql_active_snippet(true) . '
                    AND ( `oxarticles`.`oxparentid` = :oxparentid )';
            $a_prices = $database->get_row($s_q, ['oxparentid' => $s_parent_id]);
            if (isset($a_prices['varminprice'], $a_prices['varmaxprice'])) {
                $s_q = '
                    UPDATE `oxarticles`
                    SET
                        `oxvarminprice` = :oxvarminprice,
                        `oxvarmaxprice` = :oxvarmaxprice
                    WHERE
                        `oxid` = :oxid';
                $params = ['oxvarminprice' => $a_prices['varminprice'], 'oxvarmaxprice' => $a_prices['varmaxprice'], 'oxid' => $s_parent_id];
            } else {
                $s_q = '
                    UPDATE `oxarticles`
                    SET
                        `oxvarminprice` = `oxprice`,
                        `oxvarmaxprice` = `oxprice`
                    WHERE
                        `oxid` = :oxid';
                $params = ['oxid' => $s_parent_id];
            }
            $database->execute($s_q, $params);
        }
    }
    /**
     * Checks if article has uploaded master image for selected picture
     *
     * @param int $iIndex master picture index
     *
     * @return bool
     */
    protected function has_master_image($i_index)
    {
        $s_pic_name = basename((string) $this->{'oxarticles__oxpic' . $i_index}->value);
        if ($s_pic_name == 'nopic.jpg' || $s_pic_name == '') {
            return false;
        }
        if ($this->is_variant() && $this->get_parent_article() && $this->get_parent_article()->{'oxarticles__oxpic' . $i_index}->value == $this->{'oxarticles__oxpic' . $i_index}->value) {
            return false;
        }
        $s_master_pic = 'product/' . $i_index . '/' . $s_pic_name;
        if (Registry::get_config()->get_master_picture_path($s_master_pic)) {
            return true;
        }
        return false;
    }
    /**
     * Checks and return true if price view mode is netto
     *
     * @return bool
     */
    protected function is_price_view_mode_netto()
    {
        $bl_result = (bool) Registry::get_config()->get_config_param('blShowNetPrice');
        $o_user = $this->get_article_user();
        if ($o_user) {
            return $o_user->is_price_view_mode_netto();
        }
        return $bl_result;
    }
    /**
     * Depending on view mode prepare oxPrice object
     *
     * @param bool $blCalculationModeNetto - if calculation mode netto - true
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    protected function get_price_object($bl_calculation_mode_netto = null)
    {
        /** @var \OxidEsales\Eshop\Core\Price $oPrice */
        $o_price = ox_new(Price::class);
        if ($bl_calculation_mode_netto === null) {
            $bl_calculation_mode_netto = $this->is_price_view_mode_netto();
        }
        if ($bl_calculation_mode_netto) {
            $o_price->set_netto_price_mode();
        } else {
            $o_price->set_brutto_price_mode();
        }
        return $o_price;
    }
    /**
     * Depending on view mode prepare price for viewing
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice price object
     *
     * @return double
     */
    protected function get_price_for_view($o_price)
    {
        if ($this->is_price_view_mode_netto()) {
            return $o_price->get_netto_price();
        }
        return $o_price->get_brutto_price();
    }
    /**
     * Depending on view mode prepare price before calculation
     *
     * @param double $dPrice                 - price
     * @param double $dVat                   - VAT
     * @param bool   $blCalculationModeNetto - if calculation mode netto - true
     *
     * @return double
     */
    protected function prepare_price($d_price, $d_vat, $bl_calculation_mode_netto = null)
    {
        if ($bl_calculation_mode_netto === null) {
            $bl_calculation_mode_netto = $this->is_price_view_mode_netto();
        }
        $o_currency = Registry::get_config()->get_act_shop_currency_object();
        $bl_enter_net_price = Registry::get_config()->get_config_param('blEnterNetPrice');
        if ($bl_calculation_mode_netto && !$bl_enter_net_price) {
            $d_price = round(Price::brutto2Netto($d_price, $d_vat), $o_currency->decimal);
        } elseif (!$bl_calculation_mode_netto && $bl_enter_net_price) {
            $d_price = round(Price::netto2Brutto($d_price, $d_vat), $o_currency->decimal);
        }
        return $d_price;
    }
    /**
     * Return price suffix
     */
    protected function get_user_price_sufix()
    {
        $s_price_suffix = '';
        $o_user = $this->get_article_user();
        if ($o_user) {
            if ($o_user->in_group('oxidpricea')) {
                $s_price_suffix = 'a';
            } elseif ($o_user->in_group('oxidpriceb')) {
                $s_price_suffix = 'b';
            } elseif ($o_user->in_group('oxidpricec')) {
                $s_price_suffix = 'c';
            }
        }
        return $s_price_suffix;
    }
    /**
     * Return prepared price
     */
    protected function get_raw_price()
    {
        $s_price_suffix = $this->get_user_price_sufix();
        if ($s_price_suffix === '') {
            $d_price = $this->oxarticles__oxprice->value;
        } else if (Registry::get_config()->get_config_param('blOverrideZeroABCPrices')) {
            $d_price = $this->{'oxarticles__oxprice' . $s_price_suffix}->value != 0 ? $this->{'oxarticles__oxprice' . $s_price_suffix}->value : $this->oxarticles__oxprice->value;
        } else {
            $d_price = $this->{'oxarticles__oxprice' . $s_price_suffix}->value;
        }
        return $d_price;
    }
    /**
     * Return variant min price
     */
    protected function get_var_min_raw_price()
    {
        if ($this->_d_var_min_price === null) {
            $d_price = $this->get_shop_var_min_price();
            if (is_null($d_price)) {
                $s_price_suffix = $this->get_user_price_sufix();
                if ($s_price_suffix === '') {
                    $d_price = $this->oxarticles__oxvarminprice->value;
                } else {
                    $s_sql = 'SELECT ';
                    if (Registry::get_config()->get_config_param('blOverrideZeroABCPrices')) {
                        $s_sql .= 'MIN( IF(`oxprice' . $s_price_suffix . '` = 0, `oxprice`, `oxprice' . $s_price_suffix . '`) ) AS `varminprice` ';
                    } else {
                        $s_sql .= 'MIN(`oxprice' . $s_price_suffix . '`) AS `varminprice` ';
                    }
                    $s_sql .= ' FROM ' . $this->get_view_name(true) . '
                    WHERE ' . $this->get_sql_active_snippet(true) . '
                        AND ( `oxparentid` = :oxparentid )';
                    $d_price = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_sql, ['oxparentid' => $this->get_id()]);
                }
            }
            $this->_d_var_min_price = $d_price;
        }
        return $this->_d_var_min_price;
    }
    /**
     * Return variant max price
     */
    protected function get_var_max_price()
    {
        if ($this->_d_var_max_price === null) {
            $d_price = $this->get_shop_var_max_price();
            if (is_null($d_price)) {
                $s_price_suffix = $this->get_user_price_sufix();
                if ($s_price_suffix === '') {
                    $d_price = $this->oxarticles__oxvarmaxprice->value;
                } else {
                    $s_sql = 'SELECT ';
                    if (Registry::get_config()->get_config_param('blOverrideZeroABCPrices')) {
                        $s_sql .= 'MAX( IF(`oxprice' . $s_price_suffix . '` = 0, `oxprice`, `oxprice' . $s_price_suffix . '`) ) AS `varmaxprice` ';
                    } else {
                        $s_sql .= 'MAX(`oxprice' . $s_price_suffix . '`) AS `varmaxprice` ';
                    }
                    $s_sql .= ' FROM ' . $this->get_view_name(true) . '
                        WHERE ' . $this->get_sql_active_snippet(true) . '
                            AND ( `oxparentid` = :oxparentid )';
                    $d_price = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_sql, ['oxparentid' => $this->get_id()]);
                }
            }
            $this->_d_var_max_price = $d_price;
        }
        return $this->_d_var_max_price;
    }
    /**
     * Place to hook to return variant min price if it might be different,
     * for example for subshops.
     *
     * @return double|null
     */
    protected function get_shop_var_min_price()
    {
        return null;
    }
    /**
     * Place to hook to return variant max price if it might be different,
     * for example for subshops.
     *
     * @return double|null
     */
    protected function get_shop_var_max_price()
    {
        return null;
    }
    /**
     * Get data from db
     *
     * @param string $articleId id
     *
     * @return array
     */
    protected function load_from_db($article_id)
    {
        $s_select = $this->build_select_string([$this->get_view_name() . '.oxid' => $article_id]);
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_row($s_select);
    }
    /**
     * Place to hook and change amount if it should be calculated by different logic,
     * for example VPE.
     *
     * @param double $amount Amount
     */
    public function check_for_vpe($amount)
    {
    }
    /**
     * Set parent field value to child - variants in DB
     *
     * @return bool
     */
    protected function update_parent_depend_fields()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        foreach ($this->get_copy_parent_fields() as $s_field) {
            $s_value = $this->{$s_field}->value ?? 0;
            $s_sql_sets[] = '`' . str_replace('oxarticles__', '', $s_field) . '` = ' . $o_db->quote($s_value);
        }
        $s_sql = 'UPDATE `oxarticles` SET ';
        $s_sql .= implode(', ', $s_sql_sets) . '';
        $s_sql .= ' WHERE `oxparentid` = :oxparentid';
        return $o_db->execute($s_sql, ['oxparentid' => $this->get_id()]);
    }
    /**
     * Returns array of fields which should not changed in variants
     *
     * @return array
     */
    protected function get_copy_parent_fields()
    {
        return $this->_a_copy_parent_field;
    }
    /**
     * Set parent field value to child - variants
     */
    protected function assign_parent_depend_fields()
    {
        $s_parent = $this->get_parent_article();
        if ($s_parent) {
            foreach ($this->get_copy_parent_fields() as $s_field) {
                $this->{$s_field} = new Field($s_parent->{$s_field}->value);
            }
        }
    }
    /**
     * Saves values of sorting fields on article load.
     */
    protected function save_sorting_field_values_on_load()
    {
        $a_sorting_fields = Registry::get_config()->get_config_param('aSortCols');
        $a_sorting_fields = !empty($a_sorting_fields) ? (array) $a_sorting_fields : [];
        foreach ($a_sorting_fields as $s_field) {
            $s_full_field = $this->get_field_long_name($s_field);
            $this->_a_sorting_fields_on_load[$s_full_field] = $this->{$s_full_field}->value;
        }
    }
    /**
     * Forms query to load variants.
     *
     * @param bool                      $blRemoveNotOrderables
     * @param bool                      $forceCoreTableUsage
     * @param \OxidEsales\Eshop\Application\Model\Article|\OxidEsales\Eshop\Application\Model\SimpleVariant $baseObject
     * @param string                    $sArticleTable
     *
     * @return string
     */
    protected function get_load_variants_query($bl_remove_not_orderables, $force_core_table_usage, $base_object, $s_article_table)
    {
        return 'select ' . $base_object->get_select_fields($force_core_table_usage) . " from {$s_article_table} where " . $this->get_active_check_query($force_core_table_usage) . $this->get_variants_query($bl_remove_not_orderables, $force_core_table_usage) . " order by {$s_article_table}.oxsort";
    }
    /**
     * Set needed parameters to article list object like language.
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $baseObject          article list template object.
     * @param bool|null                              $forceCoreTableUsage if true forces core table use, default is
     *                                                                    false [optional]
     */
    protected function update_variants_base_object($base_object, $force_core_table_usage = null)
    {
        $base_object->set_language($this->get_language());
    }
    /**
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer
     */
    protected function update_manufacturer_before_loading($o_manufacturer)
    {
        $o_manufacturer->set_read_only(true);
    }
    protected function add_sql_active_range_snippet($query, $table_name): string
    {
        $date_utils = Registry::get_utils_date();
        $seconds_to_round_for_query_cache = $this->get_seconds_to_round_for_query_cache();
        $date_now = $date_utils->get_rounded_request_date_db_formatted($seconds_to_round_for_query_cache);
        $default_db_date = $date_utils->format_db_date('-');
        $active_to_condition = "{$table_name}.oxactivefrom <= '{$date_now}' AND " . "{$table_name}.oxactivefrom != '{$default_db_date}' AND " . "{$table_name}.oxactiveto = '{$default_db_date}'";
        $active_from_to_condition = "{$table_name}.oxactivefrom <= '{$date_now}' AND {$table_name}.oxactiveto >= '{$date_now}'";
        $query = $query ? " {$query} or " : '';
        return " ( {$query} (({$active_to_condition}) OR ({$active_from_to_condition})) )";
    }
    private function get_available_stock(): int
    {
        return (int) ($this->_bl_not_buyable_parent ? $this->oxarticles__oxvarstock->value : $this->oxarticles__oxstock->value);
    }
    private function is_low_stock(): bool
    {
        return $this->get_available_stock() <= $this->get_low_stock_threshold();
    }
    private function get_low_stock_threshold(): int
    {
        return (int) ($this->oxarticles__oxlowstockactive->value ? $this->oxarticles__oxremindamount->value : Registry::get_config()->get_config_param('sStockWarningLimit'));
    }
}