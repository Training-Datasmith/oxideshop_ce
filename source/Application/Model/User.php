<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database\Adapter\Database_Interface;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Email;
use Oxid_Esales\Eshop\Core\Exception\Cookie_Exception;
use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
use Oxid_Esales\Eshop\Core\Exception\User_Exception;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Model\List_Model;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Application\Enum\Subscription_Opted_In_Status;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge\Password_Service_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge\Random_Token_Generator_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
/**
 * User manager.
 * Performs user managing function, as assigning to groups, updating
 * information, deletion and other.
 */
class User extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    public const USER_COOKIE_SALT = 'user_cookie_salt';
    /**
     * Shop control variable
     *
     * @var string
     */
    protected $_bl_disable_shop_check = true;
    /**
     * Current Subscription Object if there is any
     *
     * @var object
     */
    protected $_o_news_subscription;
    /**
     * Current object class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxuser';
    /**
     * User wish / notice list
     *
     * @var array
     */
    protected $_a_baskets = [];
    /**
     * User groups list
     *
     * @var ListModel
     */
    protected $_o_groups;
    /**
     * User address list array
     *
     * @var array
     */
    protected $_a_addresses = [];
    /**
     * User payment list
     *
     * @var ListModel
     */
    protected $_o_payments;
    /**
     * User recommendation list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var ListModel
     */
    protected $_o_recomm_list;
    /**
     * Mall user status
     *
     * @var bool
     */
    protected $_bl_mall_users = false;
    /**
     * user cookies
     *
     * @var array
     */
    protected static $_a_user_cookie = [];
    /**
     * Notice list item's count
     *
     * @var integer
     */
    protected $_i_cnt_notice_list_articles;
    /**
     * Wishlist item's count
     *
     * @var integer
     */
    protected $_i_cnt_wish_list_articles;
    /**
     * User recommlist count
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var integer
     */
    protected $_i_cnt_recomm_lists;
    /**
     * Password update key
     *
     * @var string
     */
    protected $_s_update_key;
    /**
     * User loaded from cookie
     *
     * @var bool
     */
    protected $_bl_loaded_from_cookie;
    /**
     * User selected shipping address id
     *
     * @var string
     */
    protected $_s_sel_address_id;
    /**
     * User selected shipping address
     *
     * @var object
     */
    protected $_o_sel_address;
    /**
     * Id of wishlist user
     *
     * @var string
     */
    protected $_s_wish_id;
    /**
     * Country title field
     *
     * @var object
     */
    protected $_o_user_country_title;
    /**
     * @var \OxidEsales\Eshop\Application\Model\State
     */
    protected $_o_state_object;
    /**
     * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
     *                                        was added as the new default for hashing passwords.
     */
    private bool $is_outdated_password_hash_algorithm_used = false;
    /**
     * Gets state object.
     *
     * @return \OxidEsales\Eshop\Application\Model\State
     */
    protected function get_state_object()
    {
        if (is_null($this->_o_state_object)) {
            $this->_o_state_object = ox_new(\Oxid_Esales\Eshop\Application\Model\State::class);
        }
        return $this->_o_state_object;
    }
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        $this->set_mall_users_status(Registry::get_config()->get_config_param('blMallUsers'));
        parent::__construct();
        $this->init('oxuser');
    }
    /**
     * Sets mall user status
     *
     * @param bool $blOn mall users is on or off
     */
    public function set_mall_users_status($bl_on = false): void
    {
        $this->_bl_mall_users = $bl_on;
    }
    /**
     * Getter for special not frequently used fields
     *
     * @param string $sParamName name of parameter to get value
     *
     * @return mixed
     */
    public function __get($s_param_name)
    {
        // it saves memory using - loads data only if it is used
        switch ($s_param_name) {
            case 'oGroups':
                return $this->_o_groups = $this->get_user_groups();
            case 'iCntNoticeListArticles':
                return $this->_i_cnt_notice_list_articles = $this->get_notice_list_art_cnt();
            case 'iCntWishListArticles':
                return $this->_i_cnt_wish_list_articles = $this->get_wish_list_art_cnt();
            // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            case 'iCntRecommLists':
                return $this->_i_cnt_recomm_lists = $this->get_recomm_lists_count();
            // END deprecated
            case 'oAddresses':
                return $this->get_user_addresses();
            case 'oPayments':
                return $this->_o_payments = $this->get_user_payments();
            case 'oxuser__oxcountry':
                return $this->oxuser__oxcountry = $this->get_user_country();
            case 'sDBOptin':
                return $this->s_db_optin = $this->get_news_subscription()->get_opt_in_status();
            case 'sEmailFailed':
                return $this->s_email_failed = $this->get_news_subscription()->get_opt_in_email_status();
        }
    }
    /**
     * Returns user newsletter subscription controller object
     *
     * @return object oxnewssubscribed
     */
    public function get_news_subscription()
    {
        if ($this->_o_news_subscription !== null) {
            return $this->_o_news_subscription;
        }
        $this->_o_news_subscription = ox_new(\Oxid_Esales\Eshop\Application\Model\News_Subscribed::class);
        // if subscription object is not set yet - we should create one
        if (!$this->_o_news_subscription->load_from_user_id($this->get_id())) {
            if (!$this->_o_news_subscription->load_from_email($this->get_field_data('oxusername'))) {
                // no subscription defined yet - creating one
                $this->_o_news_subscription->assign(['oxuserid' => $this->get_id(), 'oxemail' => $this->get_field_data('oxusername'), 'oxsal' => $this->get_field_data('oxsal'), 'oxfname' => $this->get_field_data('oxfname'), 'oxlname' => $this->get_field_data('oxlname')]);
            }
        }
        return $this->_o_news_subscription;
    }
    /**
     * Returns user country (object) according to passed parameters or they
     * are taken from user object ( oxid, country id) and session (language)
     *
     * @param string $sCountryId country id (optional)
     * @param int    $iLang      active language (optional)
     *
     * @return string
     */
    public function get_user_country($s_country_id = null, $i_lang = null)
    {
        if ($this->_o_user_country_title == null || $s_country_id) {
            $s_id = $s_country_id ?: $this->oxuser__oxcountryid->value;
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_view_name = $table_view_name_generator->get_view_name('oxcountry', $i_lang);
            $country_title = $o_db->get_one("select oxtitle from {$s_view_name} where oxid = :oxid", ['oxid' => $s_id]);
            $o_country = new \Oxid_Esales\Eshop\Core\Field($country_title, \Oxid_Esales\Eshop\Core\Field::T_RAW);
            if (!$s_country_id) {
                $this->_o_user_country_title = $o_country;
            }
        } else {
            return $this->_o_user_country_title;
        }
        return $o_country;
    }
    /**
     * Returns user countryid according to passed name
     *
     * @param string $sCountry country
     *
     * @return string
     */
    public function get_user_country_id($s_country = null)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_q = 'select oxid from ' . $table_view_name_generator->get_view_name('oxcountry') . "\n            where oxactive = '1' and oxisoalpha2 = :oxisoalpha2";
        return $o_db->get_one($s_q, ['oxisoalpha2' => $s_country]);
    }
    /**
     * Returns assigned user groups list object
     *
     * @param string $sOXID object ID (default is null)
     *
     * @return object
     */
    public function get_user_groups($s_oxid = null)
    {
        if (isset($this->_o_groups)) {
            return $this->_o_groups;
        }
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxgroups');
        $this->_o_groups = ox_new(List_Model::class, 'oxgroups');
        $s_select = "select {$s_view_name}.* from {$s_view_name} left join oxobject2group on oxobject2group.oxgroupsid = {$s_view_name}.oxid\n                     where oxobject2group.oxobjectid = :oxobjectid";
        $this->_o_groups->select_string($s_select, ['oxobjectid' => $s_oxid]);
        return $this->_o_groups;
    }
    /**
     * Returns user defined Address list object
     *
     * @param string $sUserId object ID (default is null)
     *
     * @return array
     */
    public function get_user_addresses($s_user_id = null)
    {
        $s_user_id ??= $this->get_id();
        if (!isset($this->_a_addresses[$s_user_id])) {
            $o_user_address_list = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Address_List::class);
            $o_user_address_list->load($s_user_id);
            $this->_a_addresses[$s_user_id] = $o_user_address_list;
            // marking selected
            if ($s_address_id = $this->get_selected_address_id()) {
                foreach ($this->_a_addresses[$s_user_id] as $o_address) {
                    if ($o_address->get_id() === $s_address_id) {
                        $o_address->set_selected();
                        break;
                    }
                }
            }
        }
        return $this->_a_addresses[$s_user_id];
    }
    /**
     * Selected user address setter
     *
     * @param string $sAddressId selected address id
     */
    public function set_selected_address_id($s_address_id): void
    {
        $this->_s_sel_address_id = $s_address_id;
    }
    /**
     * Returns user chosen address id ("oxaddressid" or "deladrid")
     *
     * @return string
     */
    public function get_selected_address_id()
    {
        if ($this->_s_sel_address_id !== null) {
            return $this->_s_sel_address_id;
        }
        $s_address_id = Registry::get_request()->get_request_escaped_parameter('oxaddressid');
        if (!$s_address_id && !Registry::get_request()->get_request_escaped_parameter('reloadaddress')) {
            return Registry::get_session()->get_variable('deladrid');
        }
        return $s_address_id;
    }
    /**
     * Checks if product from wishlist is added
     *
     * @return $sWishId
     */
    protected function get_wish_list_id()
    {
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        $this->_s_wish_id = null;
        // check if we have to set it here
        $o_basket = $session->get_basket();
        foreach ($o_basket->get_contents() as $o_basket_item) {
            if ($this->_s_wish_id = $o_basket_item->get_wish_id()) {
                // stop on first found
                break;
            }
        }
        return $this->_s_wish_id;
    }
    /**
     * Sets in the array \OxidEsales\Eshop\Application\Model\User::_aAddresses selected address.
     * Returns user selected address object.
     *
     * @return object $oSelectedAddress
     */
    public function get_selected_address()
    {
        if ($this->_o_sel_address !== null) {
            return $this->_o_sel_address;
        }
        $o_selected_address = null;
        $o_addresses = $this->get_user_addresses();
        if ($o_addresses->count()) {
            if ($s_address_id = $this->get_selected_address_id()) {
                foreach ($o_addresses as $o_address) {
                    if ($o_address->get_id() == $s_address_id) {
                        $o_address->selected = 1;
                        $o_address->set_selected();
                        $o_selected_address = $o_address;
                        break;
                    }
                }
            }
            // in case none is set - setting first one
            if (!$o_selected_address) {
                if (!$s_address_id || $s_address_id >= 0) {
                    $o_addresses->rewind();
                    $o_address = $o_addresses->current();
                } else {
                    $a_addresses = $o_addresses->get_array();
                    $o_address = array_pop($a_addresses);
                }
                $o_address->selected = 1;
                $o_address->set_selected();
                $o_selected_address = $o_address;
            }
        }
        $this->_o_sel_address = $o_selected_address;
        return $o_selected_address;
    }
    /**
     * Returns user payment history list object
     *
     * @param string $sOXID object ID (default is null)
     *
     * @return ListModel with oxuserpayments objects
     */
    public function get_user_payments($s_oxid = null)
    {
        if ($this->_o_payments === null) {
            if (!$s_oxid) {
                $s_oxid = $this->get_id();
            }
            $s_select = 'select * from oxuserpayments
                where oxuserid = :oxuserid ';
            $this->_o_payments = ox_new(List_Model::class);
            $this->_o_payments->init('oxUserPayment');
            $this->_o_payments->select_string($s_select, ['oxuserid' => $s_oxid]);
        }
        return $this->_o_payments;
    }
    /**
     * Saves (updates) user object data information in DB. Return true on success.
     *
     * @return bool
     */
    public function save()
    {
        $bl_add_remark = false;
        if ($this->get_field_data('oxpassword') && (!$this->oxuser__oxregister instanceof \Oxid_Esales\Eshop\Core\Field || $this->oxuser__oxregister->value < 1)) {
            $bl_add_remark = true;
            //save oxregister value
            $this->oxuser__oxregister = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s'), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        // setting user rights
        $this->oxuser__oxrights = new \Oxid_Esales\Eshop\Core\Field($this->get_user_rights(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        // processing birth date which came from output as array
        if ($this->oxuser__oxbirthdate && is_array($this->oxuser__oxbirthdate->value)) {
            $this->oxuser__oxbirthdate = new \Oxid_Esales\Eshop\Core\Field($this->convert_birthday($this->oxuser__oxbirthdate->value), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        $bl_ret = parent::save();
        //add registered remark
        if ($bl_add_remark && $bl_ret) {
            $o_remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
            $o_remark->oxremark__oxtext = new \Oxid_Esales\Eshop\Core\Field(Registry::get_lang()->translate_string('usrRegistered', null, true), \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_remark->oxremark__oxtype = new \Oxid_Esales\Eshop\Core\Field('r', \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_remark->oxremark__oxparentid = new \Oxid_Esales\Eshop\Core\Field($this->get_id(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_remark->save();
        }
        return $bl_ret;
    }
    /**
     * Overrides parent isDerived check and returns true
     *
     * @return bool
     */
    public function allow_derived_update()
    {
        return true;
    }
    /**
     * Checks if this object is in group, returns true on success.
     *
     * @param string $sGroupID user group ID
     *
     * @return bool
     */
    public function in_group($s_group_id)
    {
        if ($o_groups = $this->get_user_groups()) {
            return isset($o_groups[$s_group_id]);
        }
        return false;
    }
    /**
     * Removes user data stored in some DB tables (such as oxuserpayments, oxaddress
     * oxobject2group, oxremark, etc). Return true on success.
     *
     * @param string $oxid object ID (default null)
     *
     * @return bool
     */
    public function delete($oxid = null)
    {
        if (!$oxid) {
            $oxid = $this->get_id();
        }
        if (!$oxid) {
            return false;
        }
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $database->start_transaction();
        try {
            if (parent::delete($oxid)) {
                $quoted_user_id = $database->quote($oxid);
                $this->delete_addresses($database);
                $this->delete_user_from_groups($database);
                $this->delete_baskets($database);
                $this->delete_newsletter_subscriptions($database);
                $this->delete_deliveries($database);
                $this->delete_discounts($database);
                $this->delete_recommendation_lists($database);
                $this->delete_reviews($database);
                $this->delete_ratings($database);
                $this->delete_price_alarms($database);
                $this->delete_accepted_terms($database);
                $this->delete_not_order_related_remarks($database);
                $this->delete_additionally($quoted_user_id);
            }
            $database->commit_transaction();
            $deleted = true;
        } catch (\Exception $exeption) {
            $database->rollback_transaction();
            throw $exeption;
        }
        return $deleted;
    }
    /**
     * Loads object (user) details from DB. Returns true on success.
     *
     * @param string $oxID User ID
     *
     * @return bool
     */
    public function load($ox_id)
    {
        $bl_ret = parent::load($ox_id);
        // convert date's to international format
        if (isset($this->oxuser__oxcreate->value)) {
            $this->oxuser__oxcreate->set_value(Registry::get_utils_date()->format_db_date($this->oxuser__oxcreate->value));
        }
        // change newsSubcription user id
        if (isset($this->_o_news_subscription)) {
            $this->_o_news_subscription->oxnewssubscribed__oxuserid = new \Oxid_Esales\Eshop\Core\Field($ox_id, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        return $bl_ret;
    }
    /**
     * Checks if user exists in database.
     *
     * @param string $sOXID object ID (default null)
     *
     * @return bool
     */
    public function exists($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        //#5901 if physical record exists return true unconditionally
        if (parent::exists($s_oxid)) {
            $this->set_id($s_oxid);
            return true;
        }
        //additional username check
        //This part is used by not yet saved user object, to detect the case when such username exists in db.
        //Basically it is called when anonymous visitor enters existing username for newsletter subscription
        //see Newsletter::send()
        //TODO: transfer this validation to newsletter part
        $params = [];
        $s_shop_select = '';
        if (!$this->_bl_mall_users && $this->get_field_data('oxrights') != 'malladmin') {
            $s_shop_select = ' AND oxshopid = :oxshopid ';
            $params['oxshopid'] = Registry::get_config()->get_shop_id();
        }
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        $s_select = 'SELECT oxid FROM ' . $this->get_view_name() . '
                    WHERE oxusername = :oxusername ';
        $s_select .= $s_shop_select;
        $params['oxusername'] = (string) $this->get_field_data('oxusername');
        if ($s_oxid = $master_db->get_one($s_select, $params)) {
            // update - set oxid
            $this->set_id($s_oxid);
            return true;
        }
        return false;
    }
    /**
     * Returns object with ordering information (order articles list).
     *
     * @param int $iLimit how many entries to load
     * @param int $iPage  which page to start
     *
     * @return ListModel
     */
    public function get_orders($i_limit = false, $i_page = 0)
    {
        $o_orders = ox_new(List_Model::class);
        $o_orders->init('oxorder');
        if ($i_limit !== false) {
            $o_orders->set_sql_limit($i_limit * $i_page, $i_limit);
        }
        //P
        // Lists does not support loading from two tables, so orders
        // articles now are loaded in account_order.php view and no need to use blLoadProdInfo
        // forcing to load product info which is used in templates
        // $oOrders->aSetBeforeAssign['blLoadProdInfo'] = true;
        //loading order for registered user
        if ($this->oxuser__oxregister->value > 1) {
            $s_q = 'select * from oxorder
                where oxuserid = :oxuserid
                and oxorderdate >= :oxorderdate ';
            $s_q = $this->update_get_orders_query($s_q);
            $s_q .= ' order by oxorderdate desc ';
            $o_orders->select_string($s_q, ['oxuserid' => $this->get_id(), 'oxorderdate' => $this->oxuser__oxregister->value]);
        }
        return $o_orders;
    }
    /**
     * Caclulates amount of orders made by user
     *
     * @return int
     */
    public function get_order_count()
    {
        $i_cnt = 0;
        if ($this->get_id() && $this->oxuser__oxregister->value > 1) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'select count(*) from oxorder
                where oxuserid = :oxuserid
                    AND oxorderdate >= :oxorderdate
                    and oxshopid = :oxshopid ';
            $i_cnt = (int) $o_db->get_one($s_q, ['oxuserid' => $this->get_id(), 'oxorderdate' => $this->oxuser__oxregister->value, 'oxshopid' => Registry::get_config()->get_shop_id()]);
        }
        return $i_cnt;
    }
    /**
     * Returns amount of articles in noticelist
     *
     * @return int
     */
    public function get_notice_list_art_cnt()
    {
        if ($this->_i_cnt_notice_list_articles === null) {
            $this->_i_cnt_notice_list_articles = 0;
            if ($this->get_id()) {
                $this->_i_cnt_notice_list_articles = $this->get_basket('noticelist')->get_item_count();
            }
        }
        return $this->_i_cnt_notice_list_articles;
    }
    /**
     * Calculating user wishlist item count
     *
     * @return int
     */
    public function get_wish_list_art_cnt()
    {
        if ($this->_i_cnt_wish_list_articles === null) {
            $this->_i_cnt_wish_list_articles = false;
            if ($this->get_id()) {
                $this->_i_cnt_wish_list_articles = $this->get_basket('wishlist')->get_item_count();
            }
        }
        return $this->_i_cnt_wish_list_articles;
    }
    /**
     * Returns encoded delivery address.
     *
     * @return string
     */
    public function get_encoded_delivery_address()
    {
        return md5($this->get_merged_address_fields());
    }
    /**
     * Returns user country ID, but If delivery address is given - returns
     * delivery country.
     *
     * @return string
     */
    public function get_active_country()
    {
        $delivery_country_id = '';
        $delivery_address_id = Registry::get_session()->get_variable('deladrid');
        if ($delivery_address_id) {
            $delivery_address = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
            $delivery_address->load($delivery_address_id);
            $delivery_country_id = $delivery_address->get_field_data('oxcountryid');
        } elseif ($this->get_id()) {
            $delivery_country_id = $this->get_field_data('oxcountryid');
        } else {
            $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            if ($user->load_active_user()) {
                $delivery_country_id = $user->get_field_data('oxcountryid');
            }
        }
        return $delivery_country_id;
    }
    /**
     * Inserts new or updates existing user
     *
     * @throws UserException exception
     *
     * @return bool
     */
    public function create_user()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        // check if user exists AND there is no password - in this case we update otherwise we try to insert
        $s_select = 'select oxid from oxuser
            where oxusername = :oxusername
            and oxpassword = :oxpassword ';
        $params = ['oxusername' => (string) $this->oxuser__oxusername->value, 'oxpassword' => ''];
        if (!$this->_bl_mall_users) {
            $s_select .= ' and oxshopid = :oxshopid ';
            $params['oxshopid'] = $s_shop_id;
        }
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        $old_user_id = $master_db->get_one($s_select, $params);
        if ($old_user_id) {
            $this->delete($old_user_id);
        } elseif ($this->_bl_mall_users) {
            // must be sure if there is no duplicate user
            $s_q = "select oxid from oxuser\n                where oxusername = :oxusername\n                and oxusername != '' ";
            $params = ['oxusername' => (string) $this->oxuser__oxusername->value];
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
            if ($master_db->get_one($s_q, $params)) {
                /** @var UserException $oEx */
                $o_ex = ox_new(User_Exception::class);
                $o_lang = Registry::get_lang();
                $o_ex->set_message(sprintf($o_lang->translate_string('ERROR_MESSAGE_USER_USEREXISTS', $o_lang->get_tpl_language()), $this->oxuser__oxusername->value));
                throw $o_ex;
            }
        }
        $this->oxuser__oxshopid = new Field($s_shop_id, Field::T_RAW);
        $new_user_id = $this->save();
        if ($new_user_id === false) {
            throw ox_new(User_Exception::class, 'ERROR_MESSAGE_USER_USERCREATIONFAILED');
        }
        // @TODO the following statements make no sense and should be removed: oxuser__oxid is freshly created and the conditions will never match
        // dropping/cleaning old delivery address/payment info
        $o_db->execute('delete from oxaddress where oxaddress.oxuserid = :oxuserid', ['oxuserid' => $this->oxuser__oxid->value]);
        $query = 'update oxuserpayments
                      set oxuserpayments.oxuserid = :newUserId
                      where oxuserpayments.oxuserid = :oldUserId';
        $o_db->execute($query, ['newUserId' => $this->oxuser__oxusername->value, 'oldUserId' => $this->oxuser__oxid->value]);
        return $new_user_id;
    }
    public function send_registration_email(bool $send_confirm_email = false): void
    {
        $email = ox_new(Email::class);
        if ($send_confirm_email) {
            $email->send_register_confirm_email($this);
        } else {
            $email->send_register_email($this);
        }
    }
    /**
     * Adds user to the group
     *
     * @param string $sGroupID group id
     *
     * @return bool
     */
    public function add_to_group($s_group_id)
    {
        if (!$this->in_group($s_group_id)) {
            // create oxgroup object
            $o_group = ox_new(\Oxid_Esales\Eshop\Application\Model\Groups::class);
            if ($o_group->load($s_group_id)) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Application\Model\Object2Group::class);
                $o_new_group->oxobject2group__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($this->get_id(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
                $o_new_group->oxobject2group__oxgroupsid = new \Oxid_Esales\Eshop\Core\Field($s_group_id, \Oxid_Esales\Eshop\Core\Field::T_RAW);
                if ($o_new_group->save()) {
                    $this->_o_groups[$s_group_id] = $o_group;
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Removes user from passed user group.
     *
     * @param string $sGroupID group id
     */
    public function remove_from_group($s_group_id = null): void
    {
        if ($s_group_id != null && $this->in_group($s_group_id)) {
            $o_groups = ox_new(List_Model::class);
            $o_groups->init('oxobject2group');
            $s_select = 'select * from oxobject2group
                where oxobject2group.oxobjectid = :oxobjectid
                and oxobject2group.oxgroupsid = :oxgroupsid ';
            $o_groups->select_string($s_select, ['oxobjectid' => $this->get_id(), 'oxgroupsid' => $s_group_id]);
            foreach ($o_groups as $o_remgroup) {
                if ($o_remgroup->delete()) {
                    unset($this->_o_groups[$o_remgroup->oxobject2group__oxgroupsid->value]);
                }
            }
        }
    }
    /**
     * Called after saving an order.
     *
     * @param object $oBasket  Shopping basket object
     * @param int    $iSuccess order success status
     */
    public function on_order_execute($o_basket, $i_success): void
    {
        if (is_numeric($i_success) && $i_success != 2 && $i_success <= 3) {
            //adding user to particular customer groups
            $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            $d_midlle_cust_price = (float) $my_config->get_config_param('sMidlleCustPrice');
            $d_large_cust_price = (float) $my_config->get_config_param('sLargeCustPrice');
            $this->add_to_group('oxidcustomer');
            $d_basket_price = $o_basket->get_price()->get_brutto_price();
            if ($d_basket_price < $d_midlle_cust_price) {
                $this->add_to_group('oxidsmallcust');
            }
            if ($d_basket_price >= $d_midlle_cust_price && $d_basket_price < $d_large_cust_price) {
                $this->add_to_group('oxidmiddlecust');
            }
            if ($d_basket_price >= $d_large_cust_price) {
                $this->add_to_group('oxidgoodcust');
            }
            if ($this->in_group('oxidnotyetordered')) {
                $this->remove_from_group('oxidnotyetordered');
            }
        }
    }
    /**
     * Returns notice, wishlist or saved basket object
     *
     * @param string $sName name/type of basket
     *
     * @return \OxidEsales\Eshop\Application\Model\UserBasket
     */
    public function get_basket($s_name)
    {
        if (!isset($this->_a_baskets[$s_name])) {
            /** @var \OxidEsales\Eshop\Application\Model\UserBasket $oBasket */
            $o_basket = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Basket::class);
            $a_where = ['oxuserbaskets.oxuserid' => $this->get_id(), 'oxuserbaskets.oxtitle' => $s_name];
            $query = $o_basket->build_select_string($a_where);
            $record = Database_Provider::get_db()->select($query);
            if ($record && $record->count() > 0) {
                $o_basket->assign($record->fields);
            } else {
                //creating if it does not exist
                $o_basket->oxuserbaskets__oxtitle = new \Oxid_Esales\Eshop\Core\Field($s_name);
                $o_basket->oxuserbaskets__oxuserid = new \Oxid_Esales\Eshop\Core\Field($this->get_id());
                // marking basket as new (it will not be saved in DB yet)
                $o_basket->set_is_new_basket();
            }
            $this->_a_baskets[$s_name] = $o_basket;
        }
        return $this->_a_baskets[$s_name];
    }
    /**
     * User birthday converter. Usually this data comes in array form, so before
     * writing into DB it must be converted into string
     *
     * @param array $aData birthday data
     *
     * @return string
     */
    public function convert_birthday($a_data)
    {
        // preparing data to process
        $i_year = isset($a_data['year']) ? (int) $a_data['year'] : false;
        $i_month = isset($a_data['month']) ? (int) $a_data['month'] : false;
        $i_day = isset($a_data['day']) ? (int) $a_data['day'] : false;
        // leaving empty if not set
        if (!$i_year && !$i_month && !$i_day) {
            return '';
        }
        // year
        if (!$i_year || $i_year < 1000 || $i_year > 9999) {
            $i_year = date('Y');
        }
        // month
        if (!$i_month || $i_month < 1 || $i_month > 12) {
            $i_month = 1;
        }
        // maximum number of days in month
        $i_max_days = 31;
        switch ($i_month) {
            case 2:
                $i_max_days = $i_year % 4 == 0 && ($i_year % 100 != 0 || $i_year % 400 == 0) ? 29 : 28;
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                $i_max_days = min(30, $i_max_days);
                break;
        }
        // day
        if (!$i_day || $i_day < 1 || $i_day > $i_max_days) {
            $i_day = 1;
        }
        // whole date
        return sprintf('%04d-%02d-%02d', $i_year, $i_month, $i_day);
    }
    /**
     * Return standard credit rating, can be set in config option iCreditRating;
     *
     * @return integer
     */
    public function get_boni()
    {
        return Container_Facade::get_parameter('oxid_esales.shop_credit_rating');
    }
    /**
     * Performs bunch of checks if user profile data is correct; on any
     * error exception is thrown
     *
     * @param string $sLogin      user login name
     * @param string $sPassword   user password
     * @param string $sPassword2  user password to compare
     * @param array  $aInvAddress array of user profile data
     * @param array  $aDelAddress array of user profile data
     *
     * @throws StandardException
     */
    public function check_values($s_login, $s_password, $s_password2, $a_inv_address, $a_del_address): void
    {
        /** @var \OxidEsales\Eshop\Core\InputValidator $oInputValidator */
        $o_input_validator = Registry::get_input_validator();
        // 1. checking user name
        $s_login = $o_input_validator->check_login($this, $s_login, $a_inv_address);
        // 2. checking email
        $o_input_validator->check_email($this, $s_login);
        // 3. password
        $o_input_validator->check_password($this, $s_password, $s_password2, (int) Registry::get_request()->get_request_escaped_parameter('option') == 3);
        // 4. required fields
        $o_input_validator->check_required_fields($this, $a_inv_address, $a_del_address);
        // 5. country check
        $o_input_validator->check_countries($this, $a_inv_address, $a_del_address);
        // 6. vat id check.
        try {
            $o_input_validator->check_vat_id($this, $a_inv_address);
        } catch (\Oxid_Esales\Eshop\Core\Exception\Connection_Exception) {
            // R080730 just oxInputException is passed here
            // if it oxConnectionException, it means it could not check vat id
            // and will set 'not checked' status to it later
        }
        // throwing first validation error
        if ($o_error = Registry::get_input_validator()->get_first_validation_error()) {
            throw $o_error;
        }
    }
    /**
     * Sets newsletter subscription status to user
     *
     * @param bool $blSubscribe       subscribes/unsubscribes user from newsletter
     * @param bool $blSendOptIn       if to send confirmation email
     * @param bool $blForceCheckOptIn forces to check subscription even when it is set to 1
     *
     * @return bool
     */
    public function set_news_subscription($bl_subscribe, $bl_send_opt_in, $bl_force_check_opt_in = false)
    {
        $news_subscription = $this->get_news_subscription();
        if (!$news_subscription) {
            return false;
        }
        if (!$bl_subscribe) {
            return $this->handle_unsubscription($news_subscription);
        }
        return $this->handle_subscription($news_subscription, $bl_send_opt_in, $bl_force_check_opt_in);
    }
    private function handle_unsubscription($news_subscription): bool
    {
        $this->remove_from_group('oxidnewsletter');
        $news_subscription->set_opt_in_status(Subscription_Opted_In_Status::Disabled->value);
        return true;
    }
    private function handle_subscription($news_subscription, $bl_send_opt_in, $bl_force_check_opt_in): bool
    {
        $opt_in_status = $news_subscription->get_opt_in_status();
        if (!$bl_force_check_opt_in && $opt_in_status == Subscription_Opted_In_Status::Active->value) {
            return true;
        }
        if (!$bl_send_opt_in) {
            return $this->activate_directly($news_subscription);
        }
        return $this->process_opt_in_email($news_subscription);
    }
    private function activate_directly($news_subscription): bool
    {
        $this->add_to_group('oxidnewsletter');
        $news_subscription->set_opt_in_status(Subscription_Opted_In_Status::Active->value);
        return true;
    }
    private function process_opt_in_email($news_subscription): bool
    {
        $email = ox_new(Email::class);
        if (!$email->send_newsletter_db_opt_in_mail($this)) {
            return false;
        }
        $news_subscription->set_opt_in_status(Subscription_Opted_In_Status::Pending->value);
        return true;
    }
    /**
     * When changing/updating user information in frontend this method validates user
     * input. If data is fine - automatically assigns this values. Additionally calls
     * methods (\OxidEsales\Eshop\Application\Model\User::_setAutoGroups, \OxidEsales\Eshop\Application\Model\User::setNewsSubscription) to perform automatic
     * groups assignment and returns newsletter subscription status. If some action
     * fails - exception is thrown.
     *
     * @param string $sUser       user login name
     * @param string $sPassword   user password
     * @param string $sPassword2  user confirmation password
     * @param array  $aInvAddress user billing address
     * @param array  $aDelAddress delivery address
     *
     * @throws StandardException
     */
    public function change_user_data($s_user, $s_password, $s_password2, $a_inv_address, $a_del_address): void
    {
        // validating values before saving. If validation fails - exception is thrown
        $this->check_values($s_user, $s_password, $s_password2, $a_inv_address, $a_del_address);
        // input data is fine - lets save updated user info
        $this->assign($a_inv_address);
        $this->on_change_user_data($a_inv_address);
        // update old or add new delivery address
        $this->assign_address($a_del_address);
        // saving new values
        if ($this->save()) {
            // assigning automatically to specific groups
            $s_country_id = $a_inv_address['oxuser__oxcountryid'] ?? '';
            $this->set_auto_groups($s_country_id);
        }
    }
    /**
     * Returns merged delivery address fields.
     *
     * @return string
     */
    protected function get_merged_address_fields()
    {
        $s_del_address = '';
        $s_del_address .= $this->oxuser__oxcompany;
        $s_del_address .= $this->oxuser__oxusername;
        $s_del_address .= $this->oxuser__oxfname;
        $s_del_address .= $this->oxuser__oxlname;
        $s_del_address .= $this->oxuser__oxstreet;
        $s_del_address .= $this->oxuser__oxstreetnr;
        $s_del_address .= $this->oxuser__oxaddinfo;
        $s_del_address .= $this->oxuser__oxustid;
        $s_del_address .= $this->oxuser__oxcity;
        $s_del_address .= $this->oxuser__oxcountryid;
        $s_del_address .= $this->oxuser__oxstateid;
        $s_del_address .= $this->oxuser__oxzip;
        $s_del_address .= $this->oxuser__oxfon;
        $s_del_address .= $this->oxuser__oxfax;
        return $s_del_address . $this->oxuser__oxsal;
    }
    /**
     * creates new address entry or updates existing
     *
     * @param array $aDelAddress address data array
     */
    protected function assign_address($a_del_address)
    {
        if (is_array($a_del_address) && count($a_del_address)) {
            $s_address_id = Registry::get_request()->get_request_escaped_parameter('oxaddressid');
            $s_address_id = $s_address_id === null || $s_address_id == -1 || $s_address_id == -2 ? null : $s_address_id;
            $o_address = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
            $o_address->set_id($s_address_id);
            $o_address->load($s_address_id);
            $o_address->assign($a_del_address);
            $o_address->oxaddress__oxuserid = new \Oxid_Esales\Eshop\Core\Field($this->get_id(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_address->oxaddress__oxcountry = $this->get_user_country($o_address->oxaddress__oxcountryid->value);
            $o_address->save();
            // resetting addresses
            $this->_a_addresses = null;
            // saving delivery Address for later use
            Registry::get_session()->set_variable('deladrid', $o_address->get_id());
        } else {
            // resetting
            Registry::get_session()->set_variable('deladrid', null);
        }
    }
    /**
     * Builds and returns user login query.
     *
     * MD5 encoding is used in legacy eShop versions.
     * We still allow to perform the login for users registered in the previous eshop versions.
     *
     * @param string $userName login name
     * @param string $password login password
     * @param int    $shopId   shopid
     * @param bool   $isAdmin  admin/non admin mode
     *
     * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
     *                                        was added as the new default for hashing passwords. Hashing passwords with
     *                                        MD5 and SHA512 is still supported in order support login with older
     *                                        password hashes. Therefor this method might not be
     *                                        compatible with the current passhword hash any more.
     *
     * @return string
     */
    protected function _get_login_query_hashed_with_md5($user_name, $password, $shop_id, $is_admin)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $user_name_condition = $this->form_query_part_for_user_name($user_name, $database);
        $password_condition = $this->form_query_part_for_md5password($password, $database);
        $shop_or_rights_condition = $this->form_query_part_for_admin_view($shop_id, $is_admin);
        $user_active_condition = $this->form_query_part_for_active_user();
        return "SELECT `oxid`\n                    FROM oxuser\n                    WHERE 1\n                    AND {$user_active_condition}\n                    AND {$password_condition}\n                    AND {$user_name_condition}\n                    {$shop_or_rights_condition}\n                    ";
    }
    /**
     * Builds and returns user login query
     *
     * @param string $userName
     * @param string $password
     * @param int    $shopId
     * @param bool   $isAdmin
     *
     * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
     *                                        was added as the new default for hashing passwords. Hashing passwords with
     *                                        MD5 and SHA512 is still supported in order support login with older
     *                                        password hashes. Therefor this method might not be
     *                                        compatible with the current passhword hash any more.
     *
     * @return string
     */
    protected function _get_login_query($user_name, $password, $shop_id, $is_admin)
    {
        $database = Database_Provider::get_db();
        $user_name_condition = $this->form_query_part_for_user_name($user_name, $database);
        $shop_or_rights_condition = $this->form_query_part_for_admin_view($shop_id, $is_admin);
        $password_condition = $this->form_query_part_for_sha512password($password, $database, $user_name_condition, $shop_or_rights_condition);
        $user_active_condition = $this->form_query_part_for_active_user();
        return "SELECT `oxid`\n                    FROM oxuser\n                    WHERE 1\n                    AND {$user_active_condition}\n                    AND {$password_condition}\n                    AND {$user_name_condition}\n                    {$shop_or_rights_condition}\n                    ";
    }
    /**
     * Performs user login by username and password. Fetches user data from DB.
     * Registers in session. Returns true on success, FALSE otherwise.
     *
     * NOTE: It the user has already been loaded prior calling \OxidEsales\Eshop\Application\Model\User::login,
     * NO valid password is necessary for login.
     *
     * @param string $userName         User username
     * @param string $password         User password
     * @param bool   $setSessionCookie (default false)
     *
     * @throws CookieException
     * @throws UserException
     *
     * @return bool
     */
    public function login($user_name, $password, $set_session_cookie = false)
    {
        $is_admin = $this->is_admin();
        $cookie = Registry::get_utils_server()->get_ox_cookie();
        if ($cookie === null && $is_admin) {
            throw ox_new(Cookie_Exception::class, 'ERROR_MESSAGE_COOKIE_NOCOOKIE');
        }
        $config = Registry::get_config();
        $shop_id = $config->get_shop_id();
        /** New authentication mechanism */
        $password_hash = $this->get_password_hash_from_database($user_name, $shop_id, $is_admin);
        $password_service_bridge = Container_Facade::get(Password_Service_Bridge_Interface::class);
        if ($password && !$this->is_loaded() && $this->verify_hash($password, $password_hash)) {
            $this->load_authenticated_user($user_name, $shop_id);
        }
        /** Old authentication + authorization */
        if ($password && !$this->is_loaded()) {
            $this->_db_login($user_name, $password, $shop_id);
        }
        /** If needed, store a rehashed password with the authenticated user */
        if ($password && $this->is_loaded()) {
            $password_needs_rehash = $this->is_outdated_password_hash_algorithm_used || $password_service_bridge->password_needs_rehash($password_hash);
            if ($password_needs_rehash) {
                $password_hash = $this->get_hash($password);
                $this->oxuser__oxpassword = new Field($password_hash, Field::T_RAW);
                /** The use of a salt is deprecated and an empty salt will be stored */
                $this->oxuser__oxpasssalt = new Field('');
                $this->save();
            }
        }
        /** Event for alternative authentication and authorization mechanisms, or whatsoever */
        $this->on_login($user_name, $password);
        /**
         * If the user has not been loaded until this point, authentication & authorization is considered as failed.
         */
        if (!$this->is_loaded()) {
            throw ox_new(User_Exception::class, 'ERROR_MESSAGE_USER_NOVALIDLOGIN');
        }
        //resetting active user
        $this->set_user(null);
        $user_id_parameter = $is_admin ? 'auth' : 'usr';
        Registry::get_session()->set_variable($user_id_parameter, $this->get_field_data('oxid'));
        Registry::get_session()->set_variable('login-token', $this->get_hash($password_hash));
        // cookie must be set ?
        if ($set_session_cookie && $config->get_config_param('blShowRememberMe')) {
            Registry::get_utils_server()->set_user_cookie($this->oxuser__oxusername->value, $this->oxuser__oxpassword->value, $config->get_shop_id(), 31536000, static::USER_COOKIE_SALT);
        }
        return true;
    }
    /**
     *
     * @throws UserException
     */
    private function load_authenticated_user(string $user_name, int $shop_id): void
    {
        $is_login_to_admin_backend = $this->is_admin();
        $user_id = $this->get_authenticated_user_id($user_name, $shop_id, $is_login_to_admin_backend);
        if (!$this->load($user_id)) {
            throw ox_new(User_Exception::class, 'ERROR_MESSAGE_USER_NOVALIDLOGIN');
        }
    }
    /**
     *
     * @return false|string
     */
    private function get_authenticated_user_id(string $user_name, int $shop_id, bool $is_login_to_admin_backend)
    {
        $database = Database_Provider::get_db();
        $user_name_condition = $this->form_query_part_for_user_name($user_name, $database);
        $shop_or_rights_condition = $this->form_query_part_for_admin_view($shop_id, $is_login_to_admin_backend);
        $user_active_condition = $this->form_query_part_for_active_user();
        $query = "SELECT `OXID`\n                    FROM oxuser\n                    WHERE 1\n                    AND {$user_active_condition}\n                    AND {$user_name_condition}\n                    {$shop_or_rights_condition}\n                    ";
        return $database->get_one($query);
    }
    /**
     * Logs out session user. Returns true on success
     *
     * @return bool
     */
    public function logout()
    {
        Registry::get_session()->delete_variable('usr');
        // for front end
        Registry::get_session()->delete_variable('auth');
        // for back end
        Registry::get_session()->delete_variable('dynvalue');
        Registry::get_session()->delete_variable('paymentid');
        Registry::get_session()->delete_variable('login-token');
        Registry::get_utils_server()->delete_user_cookie(Registry::get_config()->get_shop_id());
        $this->set_user(null);
        return true;
    }
    /**
     * Loads active admin user object (if possible). If
     * user is not available - returns false.
     *
     * @return bool
     */
    public function load_admin_user()
    {
        return $this->load_active_user(true);
    }
    /**
     * @param bool $blForceAdmin
     *
     * @return bool
     */
    public function load_active_user($bl_force_admin = false)
    {
        $is_admin = $this->is_admin() || $bl_force_admin;
        $user_id_parameter = $is_admin ? 'auth' : 'usr';
        $user_id = Registry::get_session()->get_variable($user_id_parameter);
        // trying automatic login (by 'remember me' cookie)
        $is_user_id_in_cookie = false;
        if (!$user_id && !$is_admin && Registry::get_config()->get_config_param('blShowRememberMe')) {
            $user_id = $this->get_cookie_user_id();
            $is_user_id_in_cookie = (bool) $user_id;
        }
        if (!$user_id) {
            Registry::get_session()->delete_variable($user_id_parameter);
            return false;
        }
        if ($this->load($user_id)) {
            $login_token = (string) Registry::get_session()->get_variable('login-token');
            $password_hash = (string) $this->get_field_data('oxpassword');
            if ($login_token && !$this->verify_hash($password_hash, $login_token)) {
                $this->logout();
                return false;
            }
            Registry::get_session()->set_variable($user_id_parameter, $user_id);
            $this->_bl_loaded_from_cookie = $is_user_id_in_cookie;
            return true;
        }
    }
    /**
     * Checks if user is connected via cookies and if so, returns user id.
     *
     * @return string
     */
    protected function get_cookie_user_id()
    {
        $s_user_id = null;
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_shop_id = $o_config->get_shop_id();
        if ($s_set = Registry::get_utils_server()->get_user_cookie($s_shop_id)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $a_data = explode('@@@', (string) $s_set);
            $s_user = $a_data[0];
            $s_pwd = @$a_data[1];
            $s_select = $this->form_user_cookie_query($s_user, $s_shop_id);
            $rs = $o_db->select($s_select);
            if ($rs != false && $rs->count() > 0) {
                while (!$rs->EOF) {
                    if ($this->verify_hash($rs->fields['oxpassword'] . static::USER_COOKIE_SALT, $s_pwd)) {
                        // found
                        $s_user_id = $rs->fields['oxid'];
                        break;
                    }
                    $rs->fetch_row();
                }
            }
            // if cookie info is not valid, remove it.
            if (!$s_user_id) {
                Registry::get_utils_server()->delete_user_cookie($s_shop_id);
            }
        }
        return $s_user_id;
    }
    /**
     * Returns user rights index. Index cannot be higher than current session
     * user rights index.
     *
     * @return string
     */
    protected function get_user_rights()
    {
        // previously user had no rights defined
        if (!$this->oxuser__oxrights instanceof \Oxid_Esales\Eshop\Core\Field || !$this->oxuser__oxrights->value) {
            return 'user';
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_auth_rights = null;
        // choosing possible user rights index
        $s_auth_user_id = $this->is_admin() ? Registry::get_session()->get_variable('auth') : null;
        $s_auth_user_id = $s_auth_user_id ?: Registry::get_session()->get_variable('usr');
        if ($s_auth_user_id) {
            $auth_rights_sql = 'select oxrights from ' . $this->get_view_name() . ' where oxid = :oxid';
            $s_auth_rights = $o_db->get_one($auth_rights_sql, ['oxid' => $s_auth_user_id]);
        }
        //preventing user rights edit for non admin
        $a_rights = [];
        // selecting current users rights ...
        $current_rights_sql = 'select oxrights from ' . $this->get_view_name() . ' where oxid = :oxid';
        $params = ['oxid' => $this->get_id()];
        if ($s_curr_rights = $o_db->get_one($current_rights_sql, $params)) {
            $a_rights[] = $s_curr_rights;
        }
        $a_rights[] = 'user';
        if (!$s_auth_rights || !($s_auth_rights == 'malladmin' || $s_auth_rights == $my_config->get_shop_id())) {
            return current($a_rights);
        }
        if ($s_auth_rights == $my_config->get_shop_id()) {
            $a_rights[] = $s_auth_rights;
            if (!in_array($this->oxuser__oxrights->value, $a_rights)) {
                return current($a_rights);
            }
        }
        // leaving as it was set ...
        return $this->oxuser__oxrights->value;
    }
    /**
     * Inserts user object data to DB. Returns true on success.
     *
     * @return bool
     */
    protected function insert()
    {
        // set oxcreate date
        $this->oxuser__oxcreate = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s'), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        if (!isset($this->oxuser__oxboni->value)) {
            $this->oxuser__oxboni = new \Oxid_Esales\Eshop\Core\Field($this->get_boni(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        return parent::insert();
    }
    /**
     * Updates changed user object data to DB. Returns true on success.
     *
     * @return bool
     */
    protected function update()
    {
        //V #M418: for not registered users, don't change boni during update
        if (!$this->oxuser__oxpassword->value && $this->oxuser__oxregister->value < 1) {
            $this->_a_skip_save_fields[] = 'oxboni';
        }
        // don't change this field
        $this->_a_skip_save_fields[] = 'oxcreate';
        if (!$this->is_admin()) {
            $this->_a_skip_save_fields[] = 'oxcustnr';
            $this->_a_skip_save_fields[] = 'oxrights';
        }
        // updating subscription information
        if ($bl_update = parent::update()) {
            $this->get_news_subscription()->update_subscription($this);
        }
        return $bl_update;
    }
    /**
     * Checks for already used email
     *
     * @param string $email user email/login
     *
     * @return bool
     */
    public function check_if_email_exists($email)
    {
        if (!$email) {
            return false;
        }
        $config = Registry::get_config();
        $master_db = Database_Provider::get_master();
        $shop_id = $config->get_shop_id();
        $query = 'SELECT oxshopid, oxrights, oxpassword FROM oxuser WHERE oxusername = :oxusername';
        $params = ['oxusername' => (string) $email];
        if ($id = $this->get_id()) {
            $query .= ' AND oxid <> :notoxid';
            $params['notoxid'] = $id;
        }
        $result = $master_db->select($query, $params);
        if (!$result || $result->count() === 0) {
            return false;
        }
        if ($this->_bl_mall_users) {
            if ($result->fields['oxrights'] === 'user' && !$result->fields['oxpassword']) {
                return false;
                // No password set - allow override
            }
            return true;
        }
        while (!$result->EOF) {
            if ($result->fields['oxrights'] !== 'user') {
                return true;
                // Admin user exists
            }
            if ($result->fields['oxshopid'] == $shop_id && $result->fields['oxpassword']) {
                return true;
                // User exists in same shop with password
            }
            $result->fetch_row();
        }
        return false;
    }
    public function is_email_in_use(string $email): bool
    {
        $query_builder = Container_Facade::get(Query_Builder_Factory_Interface::class)->create();
        $query_builder->select('1')->from('oxuser')->where('oxusername = :email')->and_where('oxshopid = :shopId')->set_parameters(['email' => $email, 'shopId' => Registry::get_config()->get_shop_id()]);
        if ($this->get_id()) {
            $query_builder->and_where('oxid != :currentUserId')->set_parameter('currentUserId', $this->get_id());
        }
        return (bool) $query_builder->fetch_one();
    }
    /**
     * Returns user recommendation list object
     *
     * @param string $sOXID object ID (default is null)
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return ListModel with oxrecommlist objects
     */
    public function get_user_recomm_lists($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        // sets active page
        $i_act_page = (int) Registry::get_request()->get_request_escaped_parameter('pgNr');
        $i_act_page = $i_act_page < 0 ? 0 : $i_act_page;
        // load only lists which we show on screen
        $i_nrof_cat_articles = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
        $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
        $o_recomm_list = ox_new(List_Model::class);
        $o_recomm_list->init('oxrecommlist');
        $o_recomm_list->set_sql_limit($i_nrof_cat_articles * $i_act_page, $i_nrof_cat_articles);
        $i_shop_id = Registry::get_config()->get_shop_id();
        $s_select = 'select * from oxrecommlists
            where oxuserid = :oxuserid
                and oxshopid = :oxshopid';
        $o_recomm_list->select_string($s_select, ['oxuserid' => $s_oxid, 'oxshopid' => $i_shop_id]);
        return $o_recomm_list;
    }
    /**
     * Returns recommlist count
     *
     * @param string $sOx object ID (default is null)
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return int
     */
    public function get_recomm_lists_count($s_ox = null)
    {
        if (!$s_ox) {
            $s_ox = $this->get_id();
        }
        if ($this->_i_cnt_recomm_lists === null || $s_ox) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $this->_i_cnt_recomm_lists = 0;
            $i_shop_id = Registry::get_config()->get_shop_id();
            $s_select = 'select count(oxid) from oxrecommlists
                where oxuserid = :oxuserid and oxshopid = :oxshopid';
            $this->_i_cnt_recomm_lists = $o_db->get_one($s_select, ['oxuserid' => $s_ox, 'oxshopid' => $i_shop_id]);
        }
        return $this->_i_cnt_recomm_lists;
    }
    /**
     * Automatically assigns user to specific groups
     * according to users country information
     *
     * @param string $sCountryId users country id
     */
    protected function set_auto_groups($s_country_id)
    {
        // assigning automatically to specific groups
        $bl_foreigner = true;
        $bl_foreign_group_exists = false;
        $bl_inland_group_exists = false;
        $a_home_country = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aHomeCountry');
        // foreigner ?
        if (is_array($a_home_country)) {
            if (in_array($s_country_id, $a_home_country)) {
                $bl_foreigner = false;
            }
        } elseif ($s_country_id == $a_home_country) {
            $bl_foreigner = false;
        }
        if ($this->in_group('oxidforeigncustomer')) {
            $bl_foreign_group_exists = true;
            if (!$bl_foreigner) {
                $this->remove_from_group('oxidforeigncustomer');
            }
        }
        if ($this->in_group('oxidnewcustomer')) {
            $bl_inland_group_exists = true;
            if ($bl_foreigner) {
                $this->remove_from_group('oxidnewcustomer');
            }
        }
        if (!$bl_foreign_group_exists && $bl_foreigner) {
            $this->add_to_group('oxidforeigncustomer');
        }
        if (!$bl_inland_group_exists && !$bl_foreigner) {
            $this->add_to_group('oxidnewcustomer');
        }
    }
    /**
     * Tries to load user object by passed update id. Update id is
     * generated when user forgot passwords and wants to update it
     *
     * @param string $sUid update id
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    public function load_user_by_update_id($s_uid)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = 'select oxid from ' . $this->get_view_name() . '
            where oxupdateexp >= :time
                and MD5( CONCAT( oxid, oxshopid, oxupdatekey ) ) = :hash';
        if ($s_user_id = $o_db->get_one($s_q, ['time' => time(), 'hash' => $s_uid])) {
            return $this->load($s_user_id);
        }
    }
    /**
     * Generates or resets and saves users update key
     *
     * @param bool $reset marker to reset update info
     */
    public function set_update_key($reset = false): void
    {
        $token = $reset ? '' : $this->get_random_token();
        $token_expiration_time = $reset ? 0 : Registry::get_utils_date()->get_time() + $this->get_update_link_term();
        $this->oxuser__oxupdatekey = new Field($token, Field::T_RAW);
        $this->oxuser__oxupdateexp = new Field($token_expiration_time, Field::T_RAW);
        $this->save();
    }
    /**
     * Return password update link validity term (seconds). Default 3600 * 6
     *
     * @return int
     */
    public function get_update_link_term()
    {
        return 3600 * 6;
    }
    /**
     * Checks if password update key is not expired yet
     *
     * @param string $sKey key
     *
     * @return bool
     */
    public function is_expired_update_id($s_key)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = 'select 1 from ' . $this->get_view_name() . '
            where oxupdateexp >= :time
            and MD5( CONCAT( oxid, oxshopid, oxupdatekey ) ) = :hash';
        return !(bool) $o_db->get_one($s_q, ['time' => time(), 'hash' => $s_key]);
    }
    /**
     * Returns user passwords update id
     *
     * @return string
     */
    public function get_update_id()
    {
        if ($this->_s_update_key === null) {
            $this->set_update_key();
            $this->_s_update_key = md5($this->get_id() . $this->oxuser__oxshopid->value . $this->oxuser__oxupdatekey->value);
        }
        return $this->_s_update_key;
    }
    /**
     * Encodes and returns given password
     *
     * @param string $sPassword password to encode
     * @param string $sSalt     any unique string value
     * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
     *                                        was added as the new default for hashing passwords. Hashing passwords with
     *                                        MD5 and SHA512 is still supported in order support login with older
     *                                        password hashes. Therefor this method might not be
     *                                        compatible with the current passhword hash any more.
     *
     * @return string
     */
    public function encode_password($s_password, $s_salt)
    {
        $o_sha512hasher = ox_new(\Oxid_Esales\Eshop\Core\Sha512Hasher::class);
        $o_hasher = ox_new(\Oxid_Esales\Eshop\Core\Password_Hasher::class, $o_sha512hasher);
        return $o_hasher->hash($s_password, $s_salt);
    }
    /**
     * Sets new password for user (save is not called)
     *
     * @param string $password
     */
    public function set_password($password = null): void
    {
        $this->oxuser__oxpassword = new Field(empty($password) ? '' : $this->get_hash($password), Field::T_RAW);
        $this->oxuser__oxpasssalt = new Field('');
    }
    /**
     * Checks if user entered password is the same as old
     *
     * @param string $password new password
     *
     * @return bool
     */
    public function is_same_password($password)
    {
        return password_verify($password, (string) $this->oxuser__oxpassword->value);
    }
    /**
     * Returns if user was loaded from cookie
     *
     * @return bool
     */
    public function is_loaded_from_cookie()
    {
        return $this->_bl_loaded_from_cookie;
    }
    /**
     * Generates user password and username hash for review
     *
     * @param string $sUserId userid
     *
     * @return string
     */
    public function get_review_user_hash($s_user_id)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $hash_sql = 'select md5(concat("oxid", oxpassword, oxusername )) from oxuser
            where oxid = :oxid';
        return $o_db->get_one($hash_sql, ['oxid' => $s_user_id]);
    }
    /**
     * Gets from review user hash user id
     *
     * @param string $sReviewUserHash review user hash
     *
     * @return string
     */
    public function get_review_user_id($s_review_user_hash)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $user_id_sql = 'select oxid from oxuser where md5(concat("oxid", oxpassword, oxusername )) = :hash';
        return $o_db->get_one($user_id_sql, ['hash' => $s_review_user_hash]);
    }
    /**
     * Get state id for current user
     *
     * @return mixed
     */
    public function get_state_id()
    {
        return $this->oxuser__oxstateid->value;
    }
    /**
     * Get state title by id
     *
     * @param string $sId state ID
     *
     * @return string
     */
    public function get_state_title($s_id = null)
    {
        $o_state = $this->get_state_object();
        if (is_null($s_id)) {
            $s_id = $this->get_state_id();
        }
        return $o_state->get_title_by_id($s_id);
    }
    /**
     * Checks if user accepted latest shopping terms and conditions version
     *
     * @return bool
     */
    public function is_terms_accepted()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $terms_sql = 'select 1 from oxacceptedterms where oxuserid = :oxuserid and oxshopid = :oxshopid';
        return (bool) $o_db->get_one($terms_sql, ['oxuserid' => $this->get_id(), 'oxshopid' => Registry::get_config()->get_shop_id()]);
    }
    /**
     * Writes terms acceptance info to db
     */
    public function accept_terms(): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        $s_version = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class)->get_terms_version();
        $o_db->execute('replace oxacceptedterms set oxuserid=?, oxshopid=?, oxtermversion=?', [$this->get_id(), $s_shop_id, $s_version]);
    }
    /**
     * Assigns registration points for invited user and
     * its inviter (calls \OxidEsales\Eshop\Application\Model\User::setInvitationCreditPoints())
     *
     * @param string $sUserId   inviter user id
     * @param string $sRecEmail recipient (registrant) email
     *
     * @return bool
     */
    public function set_credit_points_for_registrant($s_user_id, $s_rec_email)
    {
        $bl_set = false;
        $i_points = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('dPointsForRegistration');
        // check if this invitation is still not accepted
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        $pending_sql = 'select count(oxuserid) from oxinvitations
            where oxuserid = :oxuserid
                and md5(oxemail) = :oxemailhash
                and oxpending = :oxpending
                and oxaccepted = :oxaccepted';
        $i_pending = $master_db->get_one($pending_sql, ['oxuserid' => $s_user_id, 'oxemailhash' => $s_rec_email, 'oxpending' => 1, 'oxaccepted' => 0]);
        if ($i_points && $i_pending) {
            $this->oxuser__oxpoints = new Field($i_points, Field::T_RAW);
            if ($bl_set = $this->save()) {
                // updating users statistics
                $query = "UPDATE oxinvitations\n                          SET oxpending = '0',\n                              oxaccepted = '1'\n                          WHERE oxuserid = :oxuserid AND\n                                md5(oxemail) = :oxemail";
                $master_db->execute($query, ['oxuserid' => $s_user_id, 'oxemail' => $s_rec_email]);
                $o_inv_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
                if ($o_inv_user->load($s_user_id)) {
                    $bl_set = $o_inv_user->set_credit_points_for_inviter();
                }
            }
        }
        Registry::get_session()->delete_variable('su');
        Registry::get_session()->delete_variable('re');
        return $bl_set;
    }
    /**
     * Assigns credit points to inviter
     *
     * @return bool
     */
    public function set_credit_points_for_inviter()
    {
        $bl_set = false;
        $i_points = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('dPointsForInvitation');
        if ($i_points) {
            $i_new_points = $this->oxuser__oxpoints->value + $i_points;
            $this->oxuser__oxpoints = new Field($i_new_points, Field::T_RAW);
            $bl_set = $this->save();
        }
        return $bl_set;
    }
    /**
     * Updating invitations statistics
     *
     * @param array $aRecEmail array of recipients emails
     */
    public function update_invitation_statistics($a_rec_email): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_user_id = $this->get_id();
        if ($s_user_id && is_array($a_rec_email) && count($a_rec_email) > 0) {
            //iserting statistics about invitation
            $s_date = Registry::get_utils_date()->format_db_date(date('Y-m-d'), true);
            foreach ($a_rec_email as $s_rec_email) {
                $s_sql = "INSERT INTO oxinvitations SET oxuserid = :oxuserid, oxemail = :oxemail, oxdate = :oxdate, oxpending = '1', oxaccepted = '0', oxtype = '1'";
                $o_db->execute($s_sql, ['oxuserid' => $s_user_id, 'oxemail' => $s_rec_email, 'oxdate' => $s_date]);
            }
        }
    }
    /**
     * return user id by user name
     *
     * @param string $userName
     *
     * @return false|string
     */
    public function get_id_by_user_name($user_name)
    {
        return Database_Provider::get_db()->get_one('SELECT `OXID` FROM `oxuser` WHERE `OXUSERNAME` = :oxusername AND `OXSHOPID` = :oxshopid', ['oxusername' => (string) $user_name, 'oxshopid' => Registry::get_config()->get_shop_id()]);
    }
    /**
     * returns true if user registered and have account
     *
     * @return bool
     */
    public function has_account()
    {
        return (bool) $this->oxuser__oxpassword->value;
    }
    /**
     * Return user price view mode, true - if netto mode
     *
     * @return bool
     */
    public function is_price_view_mode_netto()
    {
        return (bool) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowNetPrice');
    }
    /**
     * Returns true if User is mall admin.
     *
     * @return bool
     */
    public function is_mall_admin()
    {
        return 'malladmin' === $this->oxuser__oxrights->value;
    }
    /**
     * Initiates user login against data in DB.
     *
     * @param string $userName User
     * @param string $password Password
     * @param int    $shopId   Shop id
     *
     * @throws UserException
     *
     * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
     *                                        was added as the new default for hashing passwords. Hashing passwords with
     *                                        MD5 and SHA512 is still supported in order support login with older
     *                                        password hashes. Therefor this method might not be
     *                                        compatible with the current passhword hash any more.
     *
     * @return void
     */
    protected function _db_login(string $user_name, $password, $shop_id)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $user_id = $database->get_one($this->_get_login_query($user_name, $password, $shop_id, $this->is_admin()));
        if (!$user_id) {
            $user_id = $database->get_one($this->_get_login_query_hashed_with_md5($user_name, $password, $shop_id, $this->is_admin()));
        }
        /** Return here to give other log-in mechanisms the possibility to be triggered */
        if (!$user_id) {
            return;
        }
        $this->load_authenticated_user($user_name, $shop_id);
        $this->is_outdated_password_hash_algorithm_used = true;
    }
    /**
     *
     * @return false|string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function get_password_hash_from_database(string $user_name, int $shop_id, bool $is_login_to_admin_backend)
    {
        $database = Database_Provider::get_db();
        $user_name_condition = $this->form_query_part_for_user_name($user_name, $database);
        $shop_or_rights_condition = $this->form_query_part_for_admin_view($shop_id, $is_login_to_admin_backend);
        $user_active_condition = $this->form_query_part_for_active_user();
        $query = "SELECT `oxpassword`\n                    FROM oxuser\n                    WHERE 1\n                    AND {$user_active_condition}\n                    AND {$user_name_condition}\n                    {$shop_or_rights_condition}\n                    ";
        return $database->get_one($query);
    }
    /**
     * Return true - if shop is in demo mode
     *
     * @return bool
     */
    protected function is_demo_shop()
    {
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->is_demo_shop()) {
            return true;
        }
        return false;
    }
    /**
     * Return sql to get id of mall admin in demo shop
     *
     * @param string $sUser     User name
     * @param string $sPassword User password
     *
     * @throws object $oEx
     *
     * @return string
     */
    protected function get_demo_shop_login_query($s_user, $s_password)
    {
        if ($s_password == 'admin' && $s_user == 'admin') {
            $s_select = "SELECT `oxid` FROM `oxuser` WHERE `oxrights` = 'malladmin' ";
        } else {
            /** @var UserException $oEx */
            $o_ex = ox_new(User_Exception::class);
            $o_ex->set_message('ERROR_MESSAGE_USER_NOVALIDLOGIN');
            throw $o_ex;
        }
        return $s_select;
    }
    /**
     * Method used for override.
     *
     * @param array $aInvAddress
     */
    protected function on_change_user_data($a_inv_address)
    {
    }
    /**
     * Method is used to make additional delete actions.
     *
     * @param string $sOXIDQuoted
     */
    protected function delete_additionally($s_oxid_quoted)
    {
    }
    /**
     * Updates query for selecting orders.
     *
     * @param string $query
     *
     * @return string
     */
    protected function update_get_orders_query($query)
    {
        return $query;
    }
    /**
     * Method is used for overriding and add additional actions when logging in.
     *
     * @param string $userName
     * @param string $password
     */
    protected function on_login($user_name, $password)
    {
        /** Demo shop log in */
        if (!$this->is_loaded() && $this->is_demo_shop() && $this->is_admin()) {
            $database = Database_Provider::get_db();
            $user_id = $database->get_one($this->get_demo_shop_login_query($user_name, $password));
            if ($user_id) {
                $this->load($user_id);
            }
        }
    }
    /**
     * Deletes User from groups.
     */
    private function delete_user_from_groups(Database_Interface $database): void
    {
        $database->execute('delete from oxobject2group where oxobject2group.oxobjectid = :oxobjectid', ['oxobjectid' => $this->get_id()]);
    }
    /**
     * Deletes deliveries.
     */
    private function delete_deliveries(Database_Interface $database): void
    {
        $database->execute('delete from oxobject2delivery where oxobjectid = :oxobjectid', ['oxobjectid' => $this->get_id()]);
    }
    /**
     * Deletes discounts.
     */
    private function delete_discounts(Database_Interface $database): void
    {
        $database->execute('delete from oxobject2discount where oxobjectid = :oxobjectid', ['oxobjectid' => $this->get_id()]);
    }
    /**
     * Deletes user accepted terms.
     */
    private function delete_accepted_terms(Database_Interface $database): void
    {
        $database->execute('delete from oxacceptedterms where oxuserid = :oxuserid', ['oxuserid' => $this->get_id()]);
    }
    /**
     * Deletes User addresses.
     */
    private function delete_addresses(Database_Interface $database): void
    {
        $ids = $database->get_col('SELECT oxid FROM oxaddress WHERE oxuserid = :oxuserid', ['oxuserid' => $this->get_id()]);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\Address::class);
    }
    /**
     * Deletes noticelists, wishlists or saved baskets
     */
    private function delete_baskets(Database_Interface $database): void
    {
        $ids = $database->get_col('SELECT oxid FROM oxuserbaskets WHERE oxuserid = :oxuserid', ['oxuserid' => $this->get_id()]);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\User_Basket::class);
    }
    /**
     * Deletes not Order related remarks.
     */
    private function delete_not_order_related_remarks(Database_Interface $database): void
    {
        $sql = 'SELECT oxid FROM oxremark WHERE oxparentid = :oxparentid and oxtype != :notoxtype';
        $ids = $database->get_col($sql, ['oxparentid' => $this->get_id(), 'notoxtype' => 'o']);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\Remark::class);
    }
    /**
     * Deletes recommendation lists.
     */
    private function delete_recommendation_lists(Database_Interface $database): void
    {
        $ids = $database->get_col('SELECT oxid FROM oxrecommlists WHERE oxuserid = :oxuserid ', ['oxuserid' => $this->get_id()]);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
    }
    /**
     * Deletes newsletter subscriptions.
     */
    private function delete_newsletter_subscriptions(Database_Interface $database): void
    {
        $ids = $database->get_col('SELECT oxid FROM oxnewssubscribed WHERE oxuserid = :oxuserid ', ['oxuserid' => $this->get_id()]);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\News_Subscribed::class);
    }
    /**
     * Deletes User reviews.
     */
    private function delete_reviews(Database_Interface $database): void
    {
        $ids = $database->get_col('select oxid from oxreviews where oxuserid = :oxuserid', ['oxuserid' => $this->get_id()]);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\Review::class);
    }
    /**
     * Deletes User ratings.
     */
    private function delete_ratings(Database_Interface $database): void
    {
        $ids = $database->get_col('SELECT oxid FROM oxratings WHERE oxuserid = :oxuserid', ['oxuserid' => $this->get_id()]);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\Rating::class);
    }
    /**
     * Deletes price alarms.
     */
    private function delete_price_alarms(Database_Interface $database): void
    {
        $ids = $database->get_col('SELECT oxid FROM oxpricealarm WHERE oxuserid = :oxuserid', ['oxuserid' => $this->get_id()]);
        array_walk($ids, $this->delete_item_by_id(...), \Oxid_Esales\Eshop\Application\Model\Price_Alarm::class);
    }
    /**
     * Callback function for array_walk to delete items using the delete method of the given model class
     *
     * @param string  $id        Id of the item to be deleted
     * @param string  $className Model class to be used
     */
    private function delete_item_by_id($id, string $class_name): void
    {
        /** @var \OxidEsales\Eshop\Core\Model\BaseModel $modelObject */
        $model_object = ox_new($class_name);
        if ($model_object->load($id)) {
            if ($this->_bl_mall_users) {
                $model_object->set_is_derived(false);
            }
            $model_object->delete();
        }
    }
    /**
     *
     * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
     *                                        was added as the new default for hashing passwords. Hashing passwords with
     *                                        MD5 and SHA512 is still supported in order support login with older
     *                                        password hashes. Therefor this method might not be
     *                                        compatible with the current passhword hash any more.
     *
     */
    protected function form_query_part_for_sha512password(string $password, Database_Interface $database, string $user_condition, string $shop_condition): string
    {
        $salt = $database->get_one("SELECT `oxpasssalt` FROM `oxuser` WHERE  1 AND {$user_condition} {$shop_condition}");
        if (false !== $salt) {
            return ' oxuser.oxpassword = ' . $database->quote($this->encode_password($password, $salt));
        }
        return ' 1 ';
    }
    /**
     * @param string            $password
     *
     * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
     *                                        was added as the new default for hashing passwords. Hashing passwords with
     *                                        MD5 and SHA512 is still supported in order support login with older
     *                                        password hashes. Therefor this method might not be
     *                                        compatible with the current passhword hash any more.
     *
     */
    protected function form_query_part_for_md5password($password, Database_Interface $databaseb): string
    {
        return ' oxuser.oxpassword = BINARY MD5( CONCAT( ' . $databaseb->quote($password) . ', UNHEX( oxuser.oxpasssalt ) ) ) ';
    }
    /**
     * @param string            $user
     *
     */
    private function form_query_part_for_user_name($user, Database_Interface $database): string
    {
        return 'oxuser.oxusername = ' . $database->quote($user);
    }
    /**
     * Forms shop select query.
     *
     * @param string $sShopID Shop id is used when method is overridden.
     * @param bool   $blAdmin
     *
     * @return string
     */
    protected function form_query_part_for_admin_view($s_shop_id, $bl_admin)
    {
        // Admin view: can only login with higher than 'user' rights
        if ($bl_admin) {
            return " and ( oxrights != 'user' ) ";
        }
        return '';
    }
    private function form_query_part_for_active_user(): string
    {
        return 'oxuser.oxactive = 1';
    }
    /**
     * Updates given query. Method is for overriding.
     *
     * @param string $user
     * @param int    $shopId
     *
     * @return string
     */
    protected function form_user_cookie_query($user, $shop_id)
    {
        return 'select oxid, oxpassword, oxpasssalt from oxuser ' . 'where oxuser.oxpassword != "" and  oxuser.oxactive = 1 and oxuser.oxusername = ' . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($user);
    }
    private function get_random_token(): string
    {
        return Container_Facade::get(Random_Token_Generator_Bridge_Interface::class)->get_alphanumeric_token(32);
    }
    private function get_hash(string $password): string
    {
        return Container_Facade::get(Password_Service_Bridge_Interface::class)->hash($password);
    }
    private function verify_hash(string $password, string $hash): string
    {
        return Container_Facade::get(Password_Service_Bridge_Interface::class)->verify_password($password, $hash);
    }
}