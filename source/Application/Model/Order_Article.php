<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Model\Base_Model;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Application\Model\Contract\Article_Interface;
/**
 * Order article manager.
 * Performs copying of article.
 */
class Order_Article extends Base_Model implements Article_Interface
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxorderarticle';
    /**
     * Persisten info
     *
     * @var array
     */
    protected $_a_pers_param;
    /**
     * ERP status info
     *
     * @var array
     */
    protected $_a_statuses;
    /**
     * Order article selection list
     *
     * @var array
     */
    protected $_a_order_article_sel_list;
    /**
     * Order article instance
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_order_article;
    /**
     * Article instance
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_article;
    /**
     * New order article marker
     *
     * @var bool
     */
    protected $_bl_is_new_order_item = false;
    /**
     * Array of fields to skip when saving
     * Overrids oxBase variable
     *
     * @var array
     */
    protected $_a_skip_save_fields = ['oxtimestamp'];
    /** @var \OxidEsales\Eshop\Application\Model\Order */
    private $order;
    /**
     * Class constructor, initiates class constructor (parent::oxbase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxorderarticles');
    }
    /**
     * Copies passed to method product into $this.
     *
     * @param object $oProduct product to copy
     */
    public function copy_this($o_product): void
    {
        $a_object_vars = get_object_vars($o_product);
        foreach ($a_object_vars as $s_name => $s_value) {
            if (isset($o_product->{$s_name}->value)) {
                $s_field_name = preg_replace('/oxarticles__/', 'oxorderarticles__', (string) $s_name);
                if ($s_field_name != 'oxorderarticles__oxtimestamp') {
                    $this->{$s_field_name} = $o_product->{$s_name};
                }
                // formatting view
                if (!Registry::get_config()->get_config_param('blSkipFormatConversion')) {
                    if ($s_field_name == 'oxorderarticles__oxinsert') {
                        Registry::get_utils_date()->convert_db_date($this->{$s_field_name}, true);
                    }
                }
            }
        }
    }
    /**
     * Assigns DB field values to object fields.
     */
    public function assign($db_record): void
    {
        parent::assign($db_record);
        $this->set_article_params();
    }
    /**
     * Performs stock modification for current order article. Additionally
     * executes changeable article onChange/updateSoldAmount methods to
     * update chained data
     *
     * @param double $dAddAmount           amount which will be substracled from value in db
     * @param bool   $blAllowNegativeStock amount allow or not negative stock value
     */
    public function update_article_stock($d_add_amount, $bl_allow_negative_stock = false): void
    {
        // TODO: use oxarticle reduceStock
        // decrement stock if there is any
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->load($this->oxorderarticles__oxartid->value);
        $o_article->before_update();
        if (Registry::get_config()->get_config_param('blUseStock')) {
            // get real article stock count
            $i_stock_count = $this->get_art_stock($d_add_amount, $bl_allow_negative_stock);
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $o_article->oxarticles__oxstock = new \Oxid_Esales\Eshop\Core\Field($i_stock_count);
            $o_db->execute('update oxarticles set oxarticles.oxstock = :oxstock where oxarticles.oxid = :oxid', ['oxstock' => $i_stock_count, 'oxid' => $this->oxorderarticles__oxartid->value]);
            $o_article->on_change(ACTION_UPDATE_STOCK);
        }
        //update article sold amount
        $o_article->update_sold_amount($d_add_amount * -1);
    }
    /**
     * Adds or substracts defined amount passed by param from arcticle stock
     *
     * @param double $dAddAmount           amount which will be added/substracled from value in db
     * @param bool   $blAllowNegativeStock allow/disallow negative stock value
     *
     * @return double
     */
    protected function get_art_stock($d_add_amount = 0, $bl_allow_negative_stock = false)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        // #1592A. must take real value
        $s_q = 'select oxstock from oxarticles 
            where oxid = :oxid';
        $i_stock_count = (float) $master_db->get_one($s_q, ['oxid' => $this->oxorderarticles__oxartid->value]);
        $i_stock_count += $d_add_amount;
        // #1592A. calculating according new stock option
        if (!$bl_allow_negative_stock && $i_stock_count < 0) {
            return 0;
        }
        return $i_stock_count;
    }
    /**
     * Order persistent data getter
     *
     * @return array
     */
    public function get_pers_params()
    {
        if ($this->_a_pers_param != null) {
            return $this->_a_pers_param;
        }
        if ($this->get_field_data('oxpersparam')) {
            $this->_a_pers_param = unserialize($this->get_field_data('oxpersparam'));
        }
        return $this->_a_pers_param;
    }
    /**
     * Order persistent params setter
     *
     * @param array $aParams array of params
     */
    public function set_pers_params($a_params): void
    {
        $this->_a_pers_param = $a_params;
        // serializing persisten info stored while ordering
        $this->oxorderarticles__oxpersparam = new \Oxid_Esales\Eshop\Core\Field(serialize($a_params), \Oxid_Esales\Eshop\Core\Field::T_RAW);
    }
    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     */
    protected function set_field_data($s_field_name, $s_value, $i_data_type = \Oxid_Esales\Eshop\Core\Field::T_TEXT)
    {
        $s_field_name = strtolower($s_field_name);
        switch ($s_field_name) {
            case 'oxpersparam':
            case 'oxorderarticles__oxpersparam':
            case 'oxerpstatus':
            case 'oxorderarticles__oxerpstatus':
            case 'oxtitle':
            case 'oxorderarticles__oxtitle':
                $i_data_type = \Oxid_Esales\Eshop\Core\Field::T_RAW;
                break;
        }
        return parent::set_field_data($s_field_name, $s_value, $i_data_type);
    }
    /**
     * Executes \OxidEsales\Eshop\Application\Model\OrderArticle::load() and returns its result
     *
     * @param int    $iLanguage language id
     * @param string $sOxid     order article id
     *
     * @return bool
     */
    public function load_in_lang($i_language, $s_oxid)
    {
        return $this->load($s_oxid);
    }
    /**
     * Returns ordered article id, implements iBaseArticle interface getter method
     *
     * @return string
     */
    public function get_product_id()
    {
        return $this->oxorderarticles__oxartid->value;
    }
    /**
     * Returns product parent id
     *
     * @return string
     */
    public function get_parent_id()
    {
        // when this field will be introduced there will be no need to load from real article
        if (isset($this->oxorderarticles__oxartparentid) && $this->oxorderarticles__oxartparentid->value !== false) {
            return $this->oxorderarticles__oxartparentid->value;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $s_q = 'select oxparentid from ' . $o_article->get_view_name() . ' 
            where oxid = :oxid';
        $this->oxarticles__oxparentid = new \Oxid_Esales\Eshop\Core\Field($o_db->get_one($s_q, ['oxid' => $this->get_product_id()]));
        return $this->oxarticles__oxparentid->value;
    }
    /**
     * Sets article parameters to current object, so this object can be used for basket calculation
     */
    protected function set_article_params()
    {
        // creating needed fields
        $this->oxarticles__oxstock = $this->oxorderarticles__oxamount;
        $this->oxarticles__oxtitle = $this->oxorderarticles__oxtitle;
        $this->oxarticles__oxwidth = $this->oxorderarticles__oxwidth;
        $this->oxarticles__oxlength = $this->oxorderarticles__oxlength;
        $this->oxarticles__oxheight = $this->oxorderarticles__oxheight;
        $this->oxarticles__oxweight = $this->oxorderarticles__oxweight;
        $this->oxarticles__oxsubclass = $this->oxorderarticles__oxsubclass;
        $this->oxarticles__oxartnum = $this->oxorderarticles__oxartnum;
        $this->oxarticles__oxshortdesc = $this->oxorderarticles__oxshortdesc;
        $this->oxarticles__oxvat = $this->oxorderarticles__oxvat;
        $this->oxarticles__oxprice = $this->oxorderarticles__oxprice;
        $this->oxarticles__oxbprice = $this->oxorderarticles__oxbprice;
        $this->oxarticles__oxthumb = $this->oxorderarticles__oxthumb;
        $this->oxarticles__oxpic1 = $this->oxorderarticles__oxpic1;
        $this->oxarticles__oxpic2 = $this->oxorderarticles__oxpic2;
        $this->oxarticles__oxpic3 = $this->oxorderarticles__oxpic3;
        $this->oxarticles__oxpic4 = $this->oxorderarticles__oxpic4;
        $this->oxarticles__oxpic5 = $this->oxorderarticles__oxpic5;
        $this->oxarticles__oxfile = $this->oxorderarticles__oxfile;
        $this->oxarticles__oxdelivery = $this->oxorderarticles__oxdelivery;
        $this->oxarticles__oxissearch = $this->oxorderarticles__oxissearch;
        $this->oxarticles__oxfolder = $this->oxorderarticles__oxfolder;
        $this->oxarticles__oxtemplate = $this->oxorderarticles__oxtemplate;
        $this->oxarticles__oxexturl = $this->oxorderarticles__oxexturl;
        $this->oxarticles__oxurlimg = $this->oxorderarticles__oxurlimg;
        $this->oxarticles__oxurldesc = $this->oxorderarticles__oxurldesc;
        $this->oxarticles__oxshopid = $this->oxorderarticles__oxordershopid;
        $this->oxarticles__oxquestionemail = $this->oxorderarticles__oxquestionemail;
        $this->oxarticles__oxsearchkeys = $this->oxorderarticles__oxsearchkeys;
    }
    /**
     * Returns true, implements iBaseArticle interface method
     *
     * @param double $dAmount         stock to check
     * @param double $dArtStockAmount stock amount
     *
     * @return bool
     */
    public function check_for_stock($d_amount, $d_art_stock_amount = 0)
    {
        return true;
    }
    /**
     * Loads, caches and returns real order article instance. If article is not
     * available (deleted from db or so) false is returned
     *
     * @param string $sArticleId article id (optional, is not passed oxorderarticles__oxartid will be used)
     *
     * @return \OxidEsales\Eshop\Application\Model\Article|false
     */
    protected function get_order_article($s_article_id = null)
    {
        if ($this->_o_order_article === null) {
            $this->_o_order_article = false;
            $s_article_id = $s_article_id ?: $this->get_product_id();
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->set_load_parent_data(true);
            if ($o_article->load($s_article_id)) {
                $this->_o_order_article = $o_article;
            }
        }
        return $this->_o_order_article;
    }
    /**
     * Returns article select lists, implements iBaseArticle interface method
     *
     * @param string $sKeyPrefix prefix (not used)
     *
     * @return array
     */
    public function get_select_lists($s_key_prefix = null)
    {
        if ($o_article = $this->get_order_article()) {
            return $o_article->get_select_lists();
        }
        return [];
    }
    /**
     * Returns order article selection list array
     *
     * @param string $sArtId           ordered article id [optional]
     * @param string $sOrderArtSelList order article selection list [optional]
     *
     * @return array
     */
    public function get_order_article_select_list($s_art_id = null, $s_order_art_sel_list = null)
    {
        if ($this->_a_order_article_sel_list === null) {
            $s_order_art_sel_list = $s_order_art_sel_list ?: $this->oxorderarticles__oxselvariant->value;
            $s_order_art_sel_list = explode(' || ', (string) $s_order_art_sel_list)[0];
            $a_ret = [];
            if ($o_article = $this->get_order_article($s_art_id)) {
                $a_list = explode(', ', $s_order_art_sel_list);
                $o_str = Str::get_str();
                $a_article_sel_list = $o_article->get_select_lists();
                //formatting temporary list array from string
                if (count($a_article_sel_list) > 0) {
                    foreach ($a_list as $s_list) {
                        if ($s_list) {
                            $a_val = explode(':', $s_list);
                            if (isset($a_val[0]) && isset($a_val[1])) {
                                $s_order_art_list_title = $o_str->strtolower(trim($a_val[0]));
                                $s_order_art_sel_value = $o_str->strtolower(trim($a_val[1]));
                                //checking article list for matches with article list stored in oxOrderItem
                                $i_sel_list_num = 0;
                                foreach ($a_article_sel_list as $a_select) {
                                    //check if selects titles are equal
                                    if ($o_str->strtolower($a_select['name']) == $s_order_art_list_title) {
                                        //try to find matching select items value
                                        $i_sel_value_num = 0;
                                        foreach ($a_select as $o_sel) {
                                            if ($o_str->strtolower($o_sel->name) == $s_order_art_sel_value) {
                                                // found, adding to return array
                                                $a_ret[$i_sel_list_num] = $i_sel_value_num;
                                                break;
                                            }
                                            //next article list item
                                            $i_sel_value_num++;
                                        }
                                    }
                                    //next article list
                                    $i_sel_list_num++;
                                }
                            }
                        }
                    }
                }
            }
            $this->_a_order_article_sel_list = $a_ret;
        }
        return $this->_a_order_article_sel_list;
    }
    /**
     * Returns basket order article price
     *
     * @param double                                     $dAmount  basket item amount
     * @param array                                      $aSelList chosen selection list
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket  basket
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_basket_price($d_amount, $a_sel_list, $o_basket)
    {
        $o_article = $this->get_order_article();
        if ($o_article) {
            return $o_article->get_basket_price($d_amount, $a_sel_list, $o_basket);
        }
        return $this->get_price();
    }
    /**
     * Returns false, implements iBaseArticle interface method
     *
     * @return bool
     */
    public function skip_discounts()
    {
        return false;
    }
    /**
     * Returns empty array, implements iBaseArticle interface getter method
     *
     * @param bool $blActCats   select categories if all parents are active
     * @param bool $blSkipCache force reload or not (default false - no reload)
     *
     * @return array
     */
    public function get_category_ids($bl_act_cats = false, $bl_skip_cache = false)
    {
        if ($o_order_article = $this->get_order_article()) {
            return $o_order_article->get_category_ids($bl_act_cats, $bl_skip_cache);
        }
        return [];
    }
    /**
     * Returns current session language id
     *
     * @return int
     */
    public function get_language()
    {
        return Registry::get_lang()->get_base_language();
    }
    /**
     * Returns base article price from database
     *
     * @param double $dAmount article amount. Default is 1
     *
     * @return object
     */
    public function get_base_price($d_amount = 1)
    {
        return $this->get_price();
    }
    /**
     * Returns order article unit price
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_price()
    {
        $o_base_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        // prices in db are ONLY brutto
        $o_base_price->set_brutto_price_mode();
        $o_base_price->set_vat($this->oxorderarticles__oxvat->value);
        $o_base_price->set_price($this->oxorderarticles__oxbprice->value);
        return $o_base_price;
    }
    /**
     * Marks object as new order item (this marker useful when recalculating stocks after order recalculation)
     *
     * @param bool $blIsNew marker value - TRUE if this item is newy added to order
     */
    public function set_is_new_order_item($bl_is_new): void
    {
        $this->_bl_is_new_order_item = $bl_is_new;
    }
    /**
     * Returns TRUE if current order article is newly added to order
     *
     * @return bool
     */
    public function is_new_order_item()
    {
        return $this->_bl_is_new_order_item;
    }
    /**
     * Ordered article stock setter. Before setting new stock value additionally checks for
     * original article stock value. Is stock values <= preferred, adjusts order stock according
     * to it
     *
     * @param int $iNewAmount new ordered items amount
     */
    public function set_new_amount($i_new_amount): void
    {
        if ($i_new_amount >= 0) {
            // to update stock we must first check if it is possible - article exists?
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($o_article->load($this->oxorderarticles__oxartid->value)) {
                // updating stock info
                $i_stock_change = $i_new_amount - $this->oxorderarticles__oxamount->value;
                if ($i_stock_change > 0 && ($i_on_stock = $o_article->check_for_stock($i_stock_change)) !== false) {
                    if ($i_on_stock !== true) {
                        $i_stock_change = $i_on_stock;
                        $i_new_amount = $this->oxorderarticles__oxamount->value + $i_stock_change;
                    }
                }
                $this->update_article_stock($i_stock_change * -1, Registry::get_config()->get_config_param('blAllowNegativeStock'));
                // updating self
                $this->oxorderarticles__oxamount = new \Oxid_Esales\Eshop\Core\Field($i_new_amount, \Oxid_Esales\Eshop\Core\Field::T_RAW);
                $this->save();
            }
        }
    }
    /**
     * Returns true if object is derived from oxorderarticle class
     *
     * @return bool
     */
    public function is_order_article()
    {
        return true;
    }
    /**
     * Sets order article storno value to 1 and if stock control is on -
     * restores previous oxarticle stock state
     */
    public function cancel_order_article(): void
    {
        if ($this->oxorderarticles__oxstorno->value == 0) {
            $my_config = Registry::get_config();
            $this->oxorderarticles__oxstorno = new \Oxid_Esales\Eshop\Core\Field(1);
            if ($this->save()) {
                $this->update_article_stock($this->oxorderarticles__oxamount->value, $my_config->get_config_param('blAllowNegativeStock'));
            }
        }
    }
    /**
     * Deletes order article object. If deletion succeded - updates
     * article stock information. Returns deletion status
     *
     * @param string $oxid Article id
     *
     * @return bool
     */
    public function delete($oxid = null)
    {
        $is_deleted = parent::delete($oxid);
        if ($is_deleted && (int) $this->get_field_data('oxstorno') !== 1) {
            $this->update_article_stock($this->get_field_data('oxamount'), Registry::get_config()->get_config_param('blAllowNegativeStock'));
        }
        return $is_deleted;
    }
    /**
     * Saves order article object. If saving succeded - updates
     * article stock information if \OxidEsales\Eshop\Application\Model\OrderArticle::isNewOrderItem()
     * returns TRUE. Returns saving status
     *
     * @return bool
     */
    public function save()
    {
        // ordered articles
        if (($bl_save = parent::save()) && $this->is_new_order_item()) {
            $my_config = Registry::get_config();
            if ($my_config->get_config_param('blUseStock') && $my_config->get_config_param('blPsBasketReservationEnabled')) {
                $session = Registry::get_session();
                $session->get_basket_reservations()->commit_article_reservation($this->oxorderarticles__oxartid->value, $this->oxorderarticles__oxamount->value);
            } else {
                $this->update_article_stock($this->oxorderarticles__oxamount->value * -1, $my_config->get_config_param('blAllowNegativeStock'));
            }
            // seting downloadable products article files
            $this->set_order_files();
            // marking object as "non new" disable further stock changes
            $this->set_is_new_order_item(false);
        }
        return $bl_save;
    }
    /**
     * get used wrapping
     *
     * @return \OxidEsales\Eshop\Application\Model\Wrapping
     */
    public function get_wrapping()
    {
        if ($this->oxorderarticles__oxwrapid->value) {
            $o_wrapping = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
            if ($o_wrapping->load($this->oxorderarticles__oxwrapid->value)) {
                return $o_wrapping;
            }
        }
        return null;
    }
    /**
     * Returns true if ordered product is bundle
     *
     * @return bool
     */
    public function is_bundle()
    {
        return (bool) $this->oxorderarticles__oxisbundle->value;
    }
    /**
     * Get Total brut price formated
     *
     * @return string
     */
    public function get_total_brut_price_formated()
    {
        $o_lang = Registry::get_lang();
        $o_order = $this->get_order();
        $o_currency = Registry::get_config()->get_currency_object($o_order->oxorder__oxcurrency->value);
        return $o_lang->format_currency($this->oxorderarticles__oxbrutprice->value, $o_currency);
    }
    /**
     * Get  brut price formated
     *
     * @return string
     */
    public function get_brut_price_formated()
    {
        $o_lang = Registry::get_lang();
        $o_order = $this->get_order();
        $o_currency = Registry::get_config()->get_currency_object($o_order->oxorder__oxcurrency->value);
        return $o_lang->format_currency($this->oxorderarticles__oxbprice->value, $o_currency);
    }
    /**
     * Get Net price formated
     *
     * @return string
     */
    public function get_net_price_formated()
    {
        $o_lang = Registry::get_lang();
        $o_order = $this->get_order();
        $o_currency = Registry::get_config()->get_currency_object($o_order->oxorder__oxcurrency->value);
        return $o_lang->format_currency($this->oxorderarticles__oxnprice->value, $o_currency);
    }
    /**
     * Returns Order object that the article belongs to.
     *
     * @return null|\OxidEsales\Eshop\Application\Model\Order
     */
    public function get_order()
    {
        if ($this->oxorderarticles__oxorderid->value) {
            if ($this->order !== null && $this->order->get_id() === $this->oxorderarticles__oxorderid->value) {
                return $this->order;
            }
            $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            if ($o_order->load($this->oxorderarticles__oxorderid->value)) {
                return $this->order = $o_order;
            }
        }
        return null;
    }
    /**
     * Sets article creation date
     * (\OxidEsales\Eshop\Application\Model\OrderArticle::oxorderarticles__oxtimestamp). Then executes parent method
     * parent::_insert() and returns insertion status.
     *
     * @return bool
     */
    protected function insert()
    {
        $i_insert_time = time();
        $now = date('Y-m-d H:i:s', $i_insert_time);
        $this->oxorderarticles__oxtimestamp = new \Oxid_Esales\Eshop\Core\Field($now);
        return parent::insert();
    }
    /**
     * Set article
     *
     * @param object $oArticle - article object
     */
    public function set_article($o_article): void
    {
        $this->_o_article = $o_article;
    }
    /**
     * Get article
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_article()
    {
        if ($this->_o_article === null) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->load($this->oxorderarticles__oxartid->value);
            $this->_o_article = $o_article;
        }
        return $this->_o_article;
    }
    /**
     * Set order files
     */
    public function set_order_files(): void
    {
        $o_article = $this->get_article();
        if ($o_article->oxarticles__oxisdownloadable->value) {
            $o_config = Registry::get_config();
            $s_order_id = $this->oxorderarticles__oxorderid->value;
            $s_order_article_id = $this->get_id();
            $s_shop_id = $o_config->get_shop_id();
            $o_user = $o_config->get_user();
            $o_files = $o_article->get_article_files(true);
            if ($o_files) {
                foreach ($o_files as $o_file) {
                    $o_order_file = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_File::class);
                    $o_order_file->set_order_id($s_order_id);
                    $o_order_file->set_order_article_id($s_order_article_id);
                    $o_order_file->set_shop_id($s_shop_id);
                    $i_max_download_count = !empty($o_user) && !$o_user->has_account() ? $o_file->get_max_unregistered_downloads_count() : $o_file->get_max_downloads_count();
                    $o_order_file->set_file($o_file->oxfiles__oxfilename->value, $o_file->get_id(), $i_max_download_count * $this->oxorderarticles__oxamount->value, $o_file->get_link_expiration_time(), $o_file->get_download_expiration_time());
                    $o_order_file->save();
                }
            }
        }
    }
    /**
     * Get Total brut price formated
     *
     * @return string
     */
    public function get_total_net_price_formated()
    {
        $o_lang = Registry::get_lang();
        $o_order = $this->get_order();
        $o_currency = Registry::get_config()->get_currency_object($o_order->oxorder__oxcurrency->value);
        return $o_lang->format_currency($this->oxorderarticles__oxnetprice->value, $o_currency);
    }
}