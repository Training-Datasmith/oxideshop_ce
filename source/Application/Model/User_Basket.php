<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Virtual basket manager class. Virtual baskets are user article lists which are stored in database (noticelists, wishlists).
 * The name of the class is left like this because of historic reasons.
 * It is more relevant to wishlist and noticelist than to shoping basket.
 * Collects shopping basket information, updates it (DB level), removes or adds
 * articles to it.
 */
class User_Basket extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Array of fields which must be skipped when updating object data
     *
     * @var array
     */
    protected $_a_skip_save_fields = ['oxcreate', 'oxtimestamp'];
    /**
     * Current object class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxUserbasket';
    /**
     * Array of basket items
     *
     * @var array
     */
    protected $_a_basket_items;
    /**
     * Marker if basket is newly created. This avoids empty basket storing to DB
     *
     * @var bool
     */
    protected $_bl_new_basket = false;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxuserbaskets');
    }
    /**
     * Inserts object data to DB, returns true on success.
     *
     * @return mixed
     */
    protected function insert()
    {
        // marking basket as not new any more
        $this->_bl_new_basket = false;
        if (!isset($this->oxuserbaskets__oxpublic->value)) {
            $public = in_array($this->oxuserbaskets__oxtitle->value, ['noticelist', 'wishlist']);
            $this->oxuserbaskets__oxpublic = new \Oxid_Esales\Eshop\Core\Field($public, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        $i_time = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $this->oxuserbaskets__oxupdate = new \Oxid_Esales\Eshop\Core\Field($i_time);
        return parent::insert();
    }
    /**
     * Sets basket as newly created. This usually means that it is not
     * yet stored in DB and will only be stored if some item is added
     */
    public function set_is_new_basket(): void
    {
        $this->_bl_new_basket = true;
        $i_time = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $this->oxuserbaskets__oxupdate = new \Oxid_Esales\Eshop\Core\Field($i_time);
    }
    /**
     * Checks if user basket is newly created
     *
     * @return bool
     */
    public function is_new_basket()
    {
        return $this->_bl_new_basket;
    }
    /**
     * Checks if user basket is empty
     *
     * @return bool
     */
    public function is_empty()
    {
        if ($this->is_new_basket() || $this->get_item_count() < 1) {
            return true;
        }
        return false;
    }
    /**
     * Returns an array of articles belonging to the Items in the basket
     *
     * @return array of oxArticle
     */
    public function get_articles()
    {
        $a_res = [];
        $a_items = $this->get_items();
        if (is_array($a_items)) {
            foreach ($a_items as $s_id => $o_item) {
                $o_article = $o_item->get_article($s_id);
                $a_res[$this->get_item_key($o_article->get_id(), $o_item->get_sel_list(), $o_item->get_pers_params())] = $o_article;
            }
        }
        return $a_res;
    }
    /**
     * Returns list of basket items
     *
     * @param bool $blReload      if TRUE forces to reload list
     * @param bool $blActiveCheck should articles be checked for active state?
     *
     * @return array of oxUserBasketItems
     */
    public function get_items($bl_reload = false, $bl_active_check = true)
    {
        // cached ?
        if ($this->_a_basket_items !== null && !$bl_reload) {
            return $this->_a_basket_items;
        }
        // initializing
        $this->_a_basket_items = [];
        // loading basket items
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $s_view_name = $o_article->get_view_name();
        $s_select = "select oxuserbasketitems.* from oxuserbasketitems \n            left join {$s_view_name} on oxuserbasketitems.oxartid = {$s_view_name}.oxid ";
        if ($bl_active_check) {
            $s_select .= 'and ' . $o_article->get_sql_active_snippet() . ' ';
        }
        $s_select .= "where oxuserbasketitems.oxbasketid = :oxbasketid and {$s_view_name}.oxid is not null ";
        $s_select .= ' order by oxartnum, oxsellist, oxpersparam ';
        $o_items = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_items->init('oxuserbasketitem');
        $o_items->selectstring($s_select, ['oxbasketid' => $this->get_id()]);
        foreach ($o_items as $o_item) {
            $s_key = $this->get_item_key($o_item->oxuserbasketitems__oxartid->value, $o_item->get_sel_list(), $o_item->get_pers_params());
            $this->_a_basket_items[$s_key] = $o_item;
        }
        return $this->_a_basket_items;
    }
    /**
     * Creates and returns  oxuserbasketitem object
     *
     * @param string $sProductId  Product Id
     * @param array  $aSelList    product select lists
     * @param string $aPersParams persistent parameters
     *
     * @return \OxidEsales\Eshop\Application\Model\UserBasketItem
     */
    protected function create_item($s_product_id, $a_sel_list = null, $a_pers_params = null)
    {
        $o_new_item = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Basket_Item::class);
        $o_new_item->oxuserbasketitems__oxartid = new \Oxid_Esales\Eshop\Core\Field($s_product_id, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $o_new_item->oxuserbasketitems__oxbasketid = new \Oxid_Esales\Eshop\Core\Field($this->get_id(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        if ($a_pers_params && count($a_pers_params)) {
            $o_new_item->set_pers_params($a_pers_params);
        }
        if (!$a_sel_list) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->load($s_product_id);
            $a_select_lists = $o_article->get_select_lists();
            if ($i_sel_cnt = count($a_select_lists)) {
                $a_sel_list = array_fill(0, $i_sel_cnt, '0');
            }
        }
        $o_new_item->set_sel_list($a_sel_list);
        return $o_new_item;
    }
    /**
     * Searches for item in basket items array and returns it. If not item was
     * found - new item is created.
     *
     * @param string $sProductId  product id, basket item id or basket item index
     * @param array  $aSelList    select lists
     * @param string $aPersParams persistent parameters
     *
     * @return \OxidEsales\Eshop\Application\Model\UserBasketItem
     */
    public function get_item($s_product_id, $a_sel_list, $a_pers_params = null)
    {
        // loading basket item list
        $a_items = $this->get_items();
        $s_item_key = $this->get_item_key($s_product_id, $a_sel_list, $a_pers_params);
        $o_item = null;
        // returning existing item
        if (isset($a_items[$s_product_id])) {
            $o_item = $a_items[$s_product_id];
        } elseif (isset($a_items[$s_item_key])) {
            $o_item = $a_items[$s_item_key];
        } else {
            $o_item = $this->create_item($s_product_id, $a_sel_list, $a_pers_params);
        }
        return $o_item;
    }
    /**
     * Returns unique item key according to its ID and user chosen select
     *
     * @param string $sProductId Product Id
     * @param array  $aSel       product select lists
     * @param array  $aPersParam basket item persistent parameters
     *
     * @return string
     */
    protected function get_item_key($s_product_id, $a_sel = null, $a_pers_param = null)
    {
        $a_sel = $a_sel != null ? $a_sel : [0 => '0'];
        return md5($s_product_id . '|' . serialize($a_sel) . '|' . serialize($a_pers_param));
    }
    /**
     * Returns current basket item count
     *
     * @param bool $blReload if TRUE forces to reload list
     *
     * @return int
     */
    public function get_item_count($bl_reload = false)
    {
        return count($this->get_items($bl_reload));
    }
    /**
     * Method adds/removes user chosen article to/from his noticelist or wishlist. Returns total amount
     * of articles in list.
     *
     * @param string $sProductId Article ID
     * @param double $dAmount    Product amount
     * @param array  $aSel       product select lists
     * @param bool   $blOverride if true overrides $dAmount, else sums previous with current it
     * @param array  $aPersParam product persistent parameters (default null)
     *
     * @return integer
     */
    public function add_item_to_basket($s_product_id = null, $d_amount = null, $a_sel = null, $bl_override = false, $a_pers_param = null)
    {
        // basket info is only written in DB when something is in it
        if ($this->_bl_new_basket) {
            $this->save();
        }
        if ($o_user_basket_item = $this->get_item($s_product_id, $a_sel, $a_pers_param)) {
            // updating object info and adding (if not yet added) item into basket items array
            if (!$bl_override && !empty($o_user_basket_item->oxuserbasketitems__oxamount->value)) {
                $d_amount += $o_user_basket_item->oxuserbasketitems__oxamount->value;
            }
            if (!$d_amount) {
                // amount = 0 removes the item
                $o_user_basket_item->delete();
                if (isset($this->_a_basket_items[$this->get_item_key($s_product_id, $a_sel, $a_pers_param)])) {
                    unset($this->_a_basket_items[$this->get_item_key($s_product_id, $a_sel, $a_pers_param)]);
                }
            } else {
                $o_user_basket_item->oxuserbasketitems__oxamount = new \Oxid_Esales\Eshop\Core\Field($d_amount, \Oxid_Esales\Eshop\Core\Field::T_RAW);
                $o_user_basket_item->save();
                $this->_a_basket_items[$this->get_item_key($s_product_id, $a_sel, $a_pers_param)] = $o_user_basket_item;
            }
            //update timestamp
            $this->oxuserbaskets__oxupdate = new \Oxid_Esales\Eshop\Core\Field(\Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
            $this->save();
            return $d_amount;
        }
    }
    /**
     * Deletes current basket history
     *
     * @param string $sOXID Object ID(default null)
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        $bl_delete = false;
        if ($s_oxid && $bl_delete = parent::delete($s_oxid)) {
            // cleaning up related data
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'delete from oxuserbasketitems where oxbasketid = :oxbasketid';
            $o_db->execute($s_q, ['oxbasketid' => $s_oxid]);
            $this->_a_basket_items = null;
        }
        return $bl_delete;
    }
    /**
     * Checks if user basket is visible for current user (public or own basket)
     *
     * @return bool
     */
    public function is_visible()
    {
        $o_activ_user = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_user();
        $s_activ_user_id = null;
        if ($o_activ_user) {
            $s_activ_user_id = $o_activ_user->get_id();
        }
        return (bool) $this->oxuserbaskets__oxpublic->value || $s_activ_user_id && $this->oxuserbaskets__oxuserid->value == $s_activ_user_id;
    }
}