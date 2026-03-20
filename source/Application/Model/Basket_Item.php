<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Ox_Article_Input_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_View;
use Ox_No_Article_Exception;
use Ox_Out_Of_Stock_Exception;
use stdClass;
/**
 * UserBasketItem class, responsible for storing most important fields
 */
#[\Allow_Dynamic_Properties]
class Basket_Item extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Product ID
     *
     * @var string
     */
    protected $_s_product_id;
    /**
     * Basket product title
     *
     * @var string
     */
    protected $_s_title;
    /**
     * Variant var select
     *
     * @var string
     */
    protected $_s_var_select;
    /**
     * Product icon name
     *
     * @var string
     */
    protected $_s_icon;
    /**
     * Product details link
     *
     * @var string
     */
    protected $_s_link;
    /**
     * Item price
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_price;
    /**
     * Item unit price
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_unit_price;
    /**
     * Basket item total amount
     *
     * @var double
     */
    protected $_d_amount = 0.0;
    /**
     * Total basket item weight
     *
     * @var double
     */
    protected $_d_weight = 0;
    /**
     * Basket item select lists
     *
     * @var array
     */
    protected $_a_sel_list = [];
    /**
     * Shop id where product was put into basket
     *
     * @var string
     */
    protected $_s_shop_id;
    /**
     * Native product shop Id
     *
     * @var string
     */
    protected $_s_native_shop_id;
    /**
     * Skip discounts marker
     *
     * @var boolean
     */
    protected $_bl_skip_discounts = false;
    /**
     * Persistent basket item parameters
     *
     * @var array
     */
    protected $_a_persistent_parameters = [];
    /**
     * Buundle marker - marks if item is bundle or not
     *
     * @var boolean
     */
    protected $_bl_bundle = false;
    /**
     * Discount bundle marker - marks if item is discount bundle or not
     *
     * @var boolean
     */
    protected $_bl_is_discount_article = false;
    /**
     * This item article
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_article;
    /**
     * Image NON SSL url
     *
     * @var string
     */
    protected $_s_dimage_dir_no_ssl;
    /**
     * Image SSL url
     *
     * @var string
     */
    protected $_s_dimage_dir_ssl;
    /**
     * User chosen selectlists
     *
     * @var array
     */
    protected $_a_chosen_selectlist = [];
    /**
     * Used wrapping paper Id
     *
     * @var string
     */
    protected $_s_wrapping_id;
    /**
     * Wishlist user Id
     *
     * @var string
     */
    protected $_s_wish_id;
    /**
     * Wish article Id
     *
     * @var string
     */
    protected $_s_wish_article_id;
    /**
     * Article stock check (live db check) status
     *
     * @var bool
     */
    protected $_bl_check_article_stock = true;
    /**
     * Basket Item language Id
     *
     * @var bool
     */
    protected $_i_language_id;
    protected $_o_icon;
    /**
     * Regular Item unit price - price without basket item discounts
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_regular_unit_price;
    /**
     * Basket item's individual key.
     *
     * @var string
     */
    protected $basket_item_key;
    /**
     * Getter for basketItemkey.
     *
     * @return string|null
     */
    public function get_basket_item_key()
    {
        return $this->basket_item_key;
    }
    /**
     * Setter for basketItemkey.
     *
     * @param string $itemKey
     */
    public function set_basket_item_key($item_key): void
    {
        $this->basket_item_key = $item_key;
    }
    /**
     * Return regular unit price
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_regular_unit_price()
    {
        return $this->_o_regular_unit_price;
    }
    /**
     * Set regular unit price
     *
     * @param \OxidEsales\Eshop\Core\Price $oRegularUnitPrice regular price
     */
    public function set_regular_unit_price($o_regular_unit_price): void
    {
        $this->_o_regular_unit_price = $o_regular_unit_price;
    }
    /**
     * Assigns basic params to basket item
     *  - oxbasketitem::_setArticle();
     *  - oxbasketitem::setAmount();
     *  - oxbasketitem::_setSelectList();
     *  - oxbasketitem::setPersParams();
     *  - oxbasketitem::setBundle().
     *
     * @param string $sProductID product id
     * @param double $dAmount    amount
     * @param array  $aSel       selection
     * @param array  $aPersParam persistent params
     * @param bool   $blBundle   bundle
     *
     * @throws oxNoArticleException
     * @throws oxOutOfStockException
     * @throws oxArticleInputException
     */
    public function init($s_product_id, $d_amount, $a_sel = null, $a_pers_param = null, $bl_bundle = null): void
    {
        $this->set_article($s_product_id);
        $this->set_amount($d_amount);
        $this->set_select_list($a_sel);
        $this->set_pers_params($a_pers_param);
        $this->set_bundle($bl_bundle);
        $this->set_language_id(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language());
    }
    /**
     * Initializes basket item from oxorderarticle object
     *  - oxbasketitem::_setFromOrderArticle() - assigns $oOrderArticle parameter
     *  to oxBasketItem::_oArticle. Thus oxOrderArticle is used as oxArticle (calls
     *  standard methods implemented by oxIArticle interface);
     *  - oxbasketitem::setAmount();
     *  - oxbasketitem::_setSelectList();
     *  - oxbasketitem::setPersParams().
     *
     * @param \OxidEsales\Eshop\Application\Model\OrderArticle $oOrderArticle order article to load info from
     */
    public function init_from_order_article($o_order_article): void
    {
        $this->set_from_order_article($o_order_article);
        $this->set_amount($o_order_article->oxorderarticles__oxamount->value);
        $this->set_select_list($o_order_article->get_order_article_select_list());
        $this->set_pers_params($o_order_article->get_pers_params());
        $this->set_bundle($o_order_article->is_bundle());
    }
    /**
     * Marks if item is discount bundle ( oxbasketitem::_blIsDiscountArticle )
     *
     * @param bool $blIsDiscountArticle if item is discount bundle
     */
    public function set_as_discount_article($bl_is_discount_article): void
    {
        $this->_bl_is_discount_article = $bl_is_discount_article;
    }
    /**
     * Sets stock control mode
     *
     * @param bool $blStatus stock control mode
     */
    public function set_stock_check_status($bl_status): void
    {
        $this->_bl_check_article_stock = $bl_status;
    }
    /**
     * Returns stock control mode
     *
     * @return bool
     */
    public function get_stock_check_status()
    {
        return $this->_bl_check_article_stock;
    }
    /**
     * Sets item amount and weight which depends on amount
     * ( oxbasketitem::dAmount, oxbasketitem::dWeight )
     *
     * @param double $dAmount    amount
     * @param bool   $blOverride Whether to override current amount.
     * @param string $sItemKey   item key
     *
     * @throws oxArticleInputException
     * @throws oxOutOfStockException
     */
    public function set_amount($d_amount, $bl_override = true, $s_item_key = null): void
    {
        try {
            //validating amount
            $d_amount = \Oxid_Esales\Eshop\Core\Registry::get_input_validator()->validate_basket_amount($d_amount);
        } catch (\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception $o_ex) {
            $o_ex->set_article_nr($this->get_product_id());
            $o_ex->set_product_id($this->get_product_id());
            // setting additional information for exception and then rethrowing
            throw $o_ex;
        }
        $o_article = $this->get_article(true);
        $d_amount = $this->apply_package_on_amount($o_article, $d_amount);
        // setting default
        $i_on_stock = true;
        if ($bl_override) {
            $this->_d_amount = $d_amount;
        } else {
            $this->_d_amount += $d_amount;
        }
        // checking for stock
        if ($this->get_stock_check_status() == true) {
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            $d_art_stock_amount = $session->get_basket()->get_art_stock_in_basket($o_article->get_id(), $s_item_key);
            $select_for_update = false;
            if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
                $select_for_update = true;
            }
            $i_on_stock = $o_article->check_for_stock($this->_d_amount, $d_art_stock_amount, $select_for_update);
            if ($i_on_stock !== true) {
                if ($i_on_stock === false) {
                    // no stock !
                    $this->_d_amount = 0;
                } else {
                    // limited stock
                    $this->_d_amount = $i_on_stock;
                }
            }
        }
        // calculating general weight
        $this->_d_weight = $o_article->oxarticles__oxweight->value * $this->_d_amount;
        if ($i_on_stock !== true) {
            /** @var \OxidEsales\Eshop\Core\Exception\OutOfStockException $oEx */
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Out_Of_Stock_Exception::class);
            $o_ex->set_message('ERROR_MESSAGE_OUTOFSTOCK_OUTOFSTOCK');
            $o_ex->set_article_nr($o_article->oxarticles__oxartnum->value);
            $o_ex->set_product_id($o_article->get_product_id());
            $o_ex->set_remaining_amount($this->_d_amount);
            $o_ex->set_basket_index($s_item_key);
            throw $o_ex;
        }
    }
    /**
     * Apply checks for package on amount
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     * @param double                                      $amount
     *
     * @return double
     */
    protected function apply_package_on_amount($article, $amount)
    {
        return $amount;
    }
    /**
     * Sets $this->_oPrice
     *
     * @param object $oPrice price
     */
    public function set_price($o_price): void
    {
        $this->_o_unit_price = clone $o_price;
        $this->_o_price = clone $o_price;
        $this->_o_price->multiply($this->get_amount());
    }
    public function get_icon(): Product_Media_View
    {
        if ($this->_o_icon === null) {
            $this->_o_icon = $this->get_article()->get_icon();
        }
        return $this->_o_icon;
    }
    /**
     * Retrieves the article .Throws an exception if article does not exist,
     * is not buyable or visible.
     *
     * @param bool   $blCheckProduct       checks if product is buyable and visible
     * @param string $sProductId           product id
     * @param bool   $blDisableLazyLoading disable lazy loading
     *
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleException exception in case of no current object product id is set
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException exception in case if product not exitst or not visible
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException exception if product is not buyable (stock and so on)
     *
     * @return \OxidEsales\Eshop\Application\Model\Article|\OxidEsales\Eshop\Application\Model\OrderArticle
     */
    public function get_article($bl_check_product = false, $s_product_id = null, $bl_disable_lazy_loading = false)
    {
        if ($this->_o_article === null || !$this->_o_article->is_order_article() && $bl_disable_lazy_loading) {
            $s_product_id = $s_product_id ?: $this->_s_product_id;
            if (!$s_product_id) {
                //this exception may not be caught, anyhow this is a critical exception
                /** @var \OxidEsales\Eshop\Core\Exception\ArticleException $oEx */
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Article_Exception::class);
                $o_ex->set_message('EXCEPTION_ARTICLE_NOPRODUCTID');
                throw $o_ex;
            }
            $this->_o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            // #M773 Do not use article lazy loading on order save
            if ($bl_disable_lazy_loading) {
                $this->_o_article->modify_cache_key('_allviews');
                $this->_o_article->disable_lazy_loading();
            }
            // performance:
            // - skipping variants loading
            // - skipping 'ab' price info
            // - load parent field
            $this->_o_article->set_no_variant_loading(true);
            $this->_o_article->set_load_parent_data(true);
            if (!$this->_o_article->load($s_product_id)) {
                /** @var \OxidEsales\Eshop\Core\Exception\NoArticleException $oEx */
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\No_Article_Exception::class);
                $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
                $o_ex->set_message(sprintf($o_lang->translate_string('ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST', $o_lang->get_base_language()), $s_product_id));
                $o_ex->set_article_nr($s_product_id);
                $o_ex->set_product_id($s_product_id);
                throw $o_ex;
            }
            // cant put not visible product to basket (M:1286)
            if ($bl_check_product && !$this->_o_article->is_visible()) {
                /** @var \OxidEsales\Eshop\Core\Exception\NoArticleException $oEx */
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\No_Article_Exception::class);
                $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
                $o_ex->set_message(sprintf($o_lang->translate_string('ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST', $o_lang->get_base_language()), $this->_o_article->oxarticles__oxartnum->value));
                $o_ex->set_article_nr($s_product_id);
                $o_ex->set_product_id($s_product_id);
                throw $o_ex;
            }
            // cant put not buyable product to basket
            if ($bl_check_product && !$this->_o_article->is_buyable()) {
                /** @var \OxidEsales\Eshop\Core\Exception\ArticleInputException $oEx */
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception::class);
                $o_ex->set_message('ERROR_MESSAGE_ARTICLE_ARTICLE_NOT_BUYABLE');
                $o_ex->set_article_nr($s_product_id);
                $o_ex->set_product_id($s_product_id);
                throw $o_ex;
            }
        }
        return $this->_o_article;
    }
    /**
     * Returns bundle amount
     *
     * @return double
     */
    public function getd_bundled_amount()
    {
        return $this->is_bundle() ? $this->_d_amount : 0;
    }
    /**
     * Returns the price.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_price()
    {
        return $this->_o_price;
    }
    /**
     * Returns the price.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_unit_price()
    {
        return $this->_o_unit_price;
    }
    /**
     * Returns the amount of item.
     *
     * @return double
     */
    public function get_amount()
    {
        return $this->_d_amount;
    }
    /**
     * returns the total weight.
     *
     * @return double
     */
    public function get_weight()
    {
        return $this->_d_weight;
    }
    /**
     * Returns product title
     *
     * @return string
     */
    public function get_title()
    {
        if ($this->_s_title === null || $this->get_language_id() != \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language()) {
            $o_article = $this->get_article();
            $this->_s_title = $o_article->oxarticles__oxtitle->value;
            if ($o_article->oxarticles__oxvarselect->value) {
                $this->_s_title = $this->_s_title . ', ' . $this->get_var_select();
            }
        }
        return $this->_s_title;
    }
    /**
     * Returns product details URL
     *
     * @return string
     */
    public function get_link()
    {
        if ($this->_s_link === null || $this->get_language_id() != \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language()) {
            $this->_s_link = \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->clean_url($this->get_article()->get_link(), ['force_sid']);
        }
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        return $session->process_url($this->_s_link);
    }
    /**
     * Returns ID of shop from which this product was added into basket
     *
     * @return string
     */
    public function get_shop_id()
    {
        return $this->_s_shop_id;
    }
    /**
     * Returns user passed select list information
     *
     * @return array
     */
    public function get_sel_list()
    {
        return $this->_a_sel_list;
    }
    /**
     * Returns user chosen select list information
     *
     * @return array
     */
    public function get_chosen_sel_list()
    {
        return $this->_a_chosen_selectlist;
    }
    /**
     * Returns true if product is bundle
     *
     * @return bool
     */
    public function is_bundle()
    {
        return $this->_bl_bundle;
    }
    /**
     * Returns true if product is given as discount
     *
     * @return bool
     */
    public function is_discount_article()
    {
        return $this->_bl_is_discount_article;
    }
    /**
     * Returns true if discount must be skipped for current product
     *
     * @return bool
     */
    public function is_skip_discount()
    {
        return $this->_bl_skip_discounts;
    }
    /**
     * Special getter function for backwards compatibility.
     * Executes methods by rule "get".$sVariableName and returns
     * result processed by executed function.
     *
     * @param string $sName parameter name
     *
     * @return mixed
     */
    public function __get($s_name)
    {
        if ($s_name == 'oProduct') {
            return $this->get_article();
        }
    }
    /**
     * Does not return _oArticle var on serialisation
     *
     * @return array
     */
    public function __sleep()
    {
        $a_ret = [];
        foreach (get_object_vars($this) as $s_key => $s_var) {
            if ($s_key != '_oArticle') {
                $a_ret[] = $s_key;
            }
        }
        return $a_ret;
    }
    /**
     * Assigns general product parameters to oxbasketitem object :
     *  - sProduct    - oxarticle object ID;
     *  - title       - products title;
     *  - icon        - icon name;
     *  - link        - details URL's;
     *  - sShopId     - current shop ID;
     *  - sNativeShopId  - article shop ID;
     *  - _sDimageDirNoSsl - NON SSL mode image path;
     *  - _sDimageDirSsl   - SSL mode image path;
     *
     * @param string $sProductId product id
     *
     * @throws oxNoArticleException exception
     */
    protected function set_article($s_product_id)
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $o_article = $this->get_article(true, $s_product_id);
        // product ID
        $this->_s_product_id = $s_product_id;
        $this->_s_title = null;
        $this->_s_var_select = null;
        $this->get_title();
        // removing force_sid from the link (in case it'll change)
        $this->_s_link = \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->clean_url($o_article->get_link(), ['force_sid']);
        // shop Ids
        $this->_s_shop_id = $o_config->get_shop_id();
        $this->_s_native_shop_id = $o_article->oxarticles__oxshopid->value;
        // SSL/NON SSL image paths
        $this->_s_dimage_dir_no_ssl = $o_article->nossl_dimagedir;
        $this->_s_dimage_dir_ssl = $o_article->ssl_dimagedir;
    }
    /**
     * Assigns general product parameters to oxbasketitem object:
     *  - sProduct    - oxarticle object ID;
     *  - title       - products title;
     *  - sShopId     - current shop ID;
     *  - sNativeShopId  - article shop ID;
     *
     * @param \OxidEsales\Eshop\Application\Model\OrderArticle $oOrderArticle order article
     */
    protected function set_from_order_article($o_order_article)
    {
        // overriding whole article
        $this->_o_article = $o_order_article;
        // product ID
        $this->_s_product_id = $o_order_article->get_product_id();
        // products title
        $this->_s_title = $o_order_article->oxarticles__oxtitle->value;
        // shop Ids
        $this->_s_shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        $this->_s_native_shop_id = $o_order_article->oxarticles__oxshopid->value;
    }
    /**
     * Stores item select lists ( oxbasketitem::aSelList )
     *
     * @param array $aSelList item select lists
     */
    protected function set_select_list($a_sel_list)
    {
        // checking for default select list
        $a_select_lists = $this->get_article()->get_select_lists();
        if (!$a_sel_list || is_array($a_sel_list) && count($a_sel_list) == 0) {
            if ($i_sel_cnt = count($a_select_lists)) {
                $a_sel_list = array_fill(0, $i_sel_cnt, '0');
            }
        }
        $this->_a_sel_list = $a_sel_list;
        if (is_array($this->_a_sel_list) && count($this->_a_sel_list)) {
            foreach ($this->_a_sel_list as $conkey => $i_sel) {
                $this->_a_chosen_selectlist[$conkey] = new stdClass();
                $this->_a_chosen_selectlist[$conkey]->name = $a_select_lists[$conkey]['name'];
                $this->_a_chosen_selectlist[$conkey]->value = $a_select_lists[$conkey][$i_sel]->name;
            }
        }
    }
    /**
     * Get persistent parameters ( oxbasketitem::_aPersistentParameters )
     *
     * @return array
     */
    public function get_pers_params()
    {
        return $this->_a_persistent_parameters;
    }
    /**
     * Stores items persistent parameters ( oxbasketitem::_aPersistentParameters )
     *
     * @param array $aPersParam items persistent parameters
     */
    public function set_pers_params($a_pers_param): void
    {
        $this->_a_persistent_parameters = $a_pers_param;
    }
    /**
     * Marks if item is bundle ( oxbasketitem::blBundle )
     *
     * @param bool $blBundle if item is bundle
     */
    public function set_bundle($bl_bundle): void
    {
        $this->_bl_bundle = $bl_bundle;
    }
    /**
     * Used to set "skip discounts" status for basket item
     *
     * @param bool $blSkip set true to skip discounts
     */
    public function set_skip_discounts($bl_skip): void
    {
        $this->_bl_skip_discounts = $bl_skip;
    }
    /**
     * Returns product Id
     *
     * @return string product id
     */
    public function get_product_id()
    {
        return $this->_s_product_id;
    }
    /**
     * Product wrapping paper id setter
     *
     * @param string $sWrapId wrapping paper id
     */
    public function set_wrapping($s_wrap_id): void
    {
        $this->_s_wrapping_id = $s_wrap_id;
    }
    /**
     * Returns wrapping paper ID (if such was applied)
     *
     * @return string
     */
    public function get_wrapping_id()
    {
        return $this->_s_wrapping_id;
    }
    /**
     * Returns basket item wrapping object
     *
     * @return \OxidEsales\Eshop\Application\Model\Wrapping
     */
    public function get_wrapping()
    {
        $o_wrap = null;
        if ($s_wrap_id = $this->get_wrapping_id()) {
            $o_wrap = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
            $o_wrap->load($s_wrap_id);
        }
        return $o_wrap;
    }
    /**
     * Returns wishlist user Id
     *
     * @return string
     */
    public function get_wish_id()
    {
        return $this->_s_wish_id;
    }
    /**
     * Wish user id setter
     *
     * @param string $sWishId user id
     */
    public function set_wish_id($s_wish_id): void
    {
        $this->_s_wish_id = $s_wish_id;
    }
    /**
     * Wish article Id setter
     *
     * @param string $sArticleId wish article id
     */
    public function set_wish_article_id($s_article_id): void
    {
        $this->_s_wish_article_id = $s_article_id;
    }
    /**
     * Returns wish article Id
     *
     * @return string
     */
    public function get_wish_article_id()
    {
        return $this->_s_wish_article_id;
    }
    /**
     * Returns formatted regular unit price
     *
     * @deprecated in v4.8/5.1 on 2013-10-08; use oxPrice template engine formatter
     *
     * @return string
     */
    public function get_f_regular_unit_price()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($this->get_regular_unit_price()->get_price());
    }
    /**
     * Returns formatted unit price
     *
     * @deprecated in v4.8/5.1 on 2013-10-08; use oxPrice template engine formatter
     *
     * @return string
     */
    public function get_f_unit_price()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($this->get_unit_price()->get_price());
    }
    /**
     * Returns formatted total price
     *
     * @deprecated in v4.8/5.1 on 2013-10-08; use oxPrice template engine formatter
     *
     * @return string
     */
    public function get_f_total_price()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($this->get_price()->get_price());
    }
    /**
     * Returns formatted total price
     *
     * @return string
     */
    public function get_vat_percent()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_vat($this->get_price()->get_vat());
    }
    /**
     * Returns varselect value
     *
     * @return string
     */
    public function get_var_select()
    {
        if ($this->_s_var_select === null || $this->get_language_id() != \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language()) {
            $o_article = $this->get_article();
            $s_var_select_value = $o_article->oxarticles__oxvarselect->value;
            $this->_s_var_select = !empty($s_var_select_value) || $s_var_select_value === '0' ? $s_var_select_value : '';
        }
        return $this->_s_var_select;
    }
    /**
     * Get language id
     *
     * @return integer
     */
    public function get_language_id()
    {
        return $this->_i_language_id;
    }
    /**
     * Set language Id, reload basket content on language change.
     *
     * @param integer $iLanguageId language id
     */
    public function set_language_id($i_language_id): void
    {
        $i_old_lang = $this->_i_language_id;
        $this->_i_language_id = $i_language_id;
        // #0003777: reload content on language change
        if ($i_old_lang !== null && $i_old_lang != $i_language_id) {
            try {
                $this->set_article($this->get_product_id());
            } catch (\Oxid_Esales\Eshop\Core\Exception\No_Article_Exception|\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception $o_ex) {
                \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex);
            }
        }
    }
}