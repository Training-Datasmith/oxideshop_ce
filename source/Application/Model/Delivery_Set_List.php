<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * DeliverySet list manager.
 */
class Delivery_Set_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Session user Id
     *
     * @var string
     */
    protected $_s_user_id;
    /**
     * Country Id
     *
     * @var string
     */
    protected $_s_country_id;
    /**
     * User object
     *
     * @var \OxidEsales\Eshop\Application\Model\User
     */
    protected $_o_user;
    /**
     * Home country info id
     *
     * @var array
     */
    protected $_s_home_country;
    /**
     * Calls parent constructor and sets home country
     */
    public function __construct()
    {
        $this->set_home_country(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aHomeCountry'));
        parent::__construct('oxdeliveryset');
    }
    /**
     * Home country setter
     *
     * @param string $sHomeCountry home country id
     */
    public function set_home_country($s_home_country): void
    {
        if (is_array($s_home_country)) {
            $this->_s_home_country = current($s_home_country);
        } else {
            $this->_s_home_country = $s_home_country;
        }
    }
    /**
     * Returns active delivery set list
     *
     * Loads all active delivery sets in list. Additionally
     * checks if set has user customized parameters like
     * assigned users, countries or user groups. Performs
     * additional filtering according to these parameters
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser      user object
     * @param string                                   $sCountryId user country id
     *
     * @return array
     */
    protected function get_active_delivery_set_list($o_user = null, $s_country_id = null)
    {
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($o_user === null) {
            $o_user = $this->get_user();
        } else {
            //set user
            $this->set_user($o_user);
        }
        $s_user_id = $o_user ? $o_user->get_id() : '';
        if ($s_user_id !== $this->_s_user_id || $s_country_id !== $this->_s_country_id) {
            // choosing delivery country if it is not set yet
            if (!$s_country_id) {
                if ($o_user) {
                    $s_country_id = $o_user->get_active_country();
                } else {
                    $s_country_id = $this->_s_home_country;
                }
            }
            $this->select_string($this->get_filter_select($o_user, $s_country_id));
            $this->_s_user_id = $s_user_id;
            $this->_s_country_id = $s_country_id;
        }
        $this->rewind();
        return $this;
    }
    /**
     * Creates delivery set list filter SQL to load current state delivery set list
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser      user object
     * @param string                                   $sCountryId user country id
     *
     * @return string
     */
    protected function get_filter_select($o_user, $s_country_id)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxdeliveryset');
        $s_q = "select {$s_table}.* from {$s_table} ";
        $s_q .= 'where ' . $this->get_base_object()->get_sql_active_snippet() . ' ';
        // defining initial filter parameters
        $s_user_id = null;
        $a_group_ids = [];
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($o_user) {
            // user ID
            $s_user_id = $o_user->get_id();
            // user groups ( maybe would be better to fetch by function \OxidEsales\Eshop\Application\Model\User::getUserGroups() ? )
            $a_group_ids = $o_user->get_user_groups();
        }
        $a_ids = [];
        if (count($a_group_ids)) {
            foreach ($a_group_ids as $o_group) {
                $a_ids[] = $o_group->get_id();
            }
        }
        $s_user_table = $table_view_name_generator->get_view_name('oxuser');
        $s_group_table = $table_view_name_generator->get_view_name('oxgroups');
        $s_country_table = $table_view_name_generator->get_view_name('oxcountry');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_country_sql = $s_country_id ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxdelset' and oxobject2delivery.OXOBJECTID=" . $o_db->quote($s_country_id) . ')' : '0';
        $s_user_sql = $s_user_id ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxdelsetu' and oxobject2delivery.OXOBJECTID=" . $o_db->quote($s_user_id) . ')' : '0';
        $s_group_sql = count($a_ids) ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxdelsetg' and oxobject2delivery.OXOBJECTID in (" . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_ids)) . ') )' : '0';
        $s_q .= "and (\n                if(EXISTS(select 1 from oxobject2delivery, {$s_country_table} where {$s_country_table}.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxdelset' LIMIT 1),\n                    {$s_country_sql},\n                    1) &&\n                if(EXISTS(select 1 from oxobject2delivery, {$s_user_table} where {$s_user_table}.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxdelsetu' LIMIT 1),\n                    {$s_user_sql},\n                    1) &&\n                if(EXISTS(select 1 from oxobject2delivery, {$s_group_table} where {$s_group_table}.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxdelsetg' LIMIT 1),\n                    {$s_group_sql},\n                    1)\n            )";
        //order by
        $s_q .= " order by {$s_table}.oxpos";
        return $s_q;
    }
    /**
     * Creates current state delivery set list
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser      user object
     * @param string                                   $sCountryId user country id
     * @param string                                   $sDelSet    preferred delivery set ID (optional)
     *
     * @return array
     */
    public function get_delivery_set_list($o_user, $s_country_id, $s_del_set = null)
    {
        $this->get_active_delivery_set_list($o_user, $s_country_id);
        // if there is already chosen delivery set we must start checking from it
        $a_list = $this->_a_array;
        if ($s_del_set && isset($a_list[$s_del_set])) {
            //set it as first element
            $o_del_set = $a_list[$s_del_set];
            unset($a_list[$s_del_set]);
            $a_list = array_merge([$s_del_set => $o_del_set], $a_list);
        }
        return $a_list;
    }
    /**
     * Loads delivery set data, checks if it has payments assigned. If active delivery set id
     * is passed - checks if it can be used, if not - takes first ship set id from list which
     * fits. For active ship set collects payment list info. Returns array containing:
     *   1. all ship sets that has payment (array)
     *   2. active ship set id (string)
     *   3. payment list for active ship set (array)
     *
     * @param string                                   $sShipSet current ship set id (can be null if not set yet)
     * @param \OxidEsales\Eshop\Application\Model\User $oUser    active user
     * @param double                                   $oBasket  basket object
     *
     * @return array
     */
    public function get_delivery_set_data($s_ship_set, $o_user, $o_basket)
    {
        $s_act_ship_set = null;
        $a_act_sets = [];
        $a_act_payment_list = [];
        if (!$o_user) {
            return;
        }
        $this->get_active_delivery_set_list($o_user, $o_user->get_active_country());
        // if there are no shipping sets we don't need to load payments
        if ($this->count()) {
            // one selected ?
            if ($s_ship_set && !isset($this->_a_array[$s_ship_set])) {
                $s_ship_set = null;
            }
            $o_pay_list = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Payment_List::class);
            $o_del_list = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Delivery_List::class);
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $d_basket_price = $o_basket->get_price_for_payment() / $o_cur->rate;
            // checking if these ship sets available (number of possible payment methods > 0)
            foreach ($this as $s_ship_set_id => $o_ship_set) {
                $a_payment_list = $o_pay_list->get_payment_list($s_ship_set_id, $d_basket_price, $o_user);
                if (count($a_payment_list)) {
                    // now checking for deliveries
                    if ($o_del_list->has_deliveries($o_basket, $o_user, $o_user->get_active_country(), $s_ship_set_id)) {
                        $a_act_sets[$s_ship_set_id] = $o_ship_set;
                        if (!$s_ship_set || $s_ship_set_id == $s_ship_set) {
                            $s_act_ship_set = $s_ship_set = $s_ship_set_id;
                            $a_act_payment_list = $a_payment_list;
                            $o_ship_set->bl_selected = true;
                        }
                    }
                }
            }
        }
        return [$a_act_sets, $s_act_ship_set, $a_act_payment_list];
    }
    /**
     * Get current user object. If user is not set, try to get current user.
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    public function get_user()
    {
        if (!$this->_o_user) {
            $this->_o_user = parent::get_user();
        }
        return $this->_o_user;
    }
    /**
     * Set current user object
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object
     */
    public function set_user($o_user): void
    {
        $this->_o_user = $o_user;
    }
    /**
     * Loads an object including all delivery sets which are not mapped to a
     * predefined GoodRelations delivery method.
     */
    public function load_non_rd_fa_delivery_set_list(): void
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxdeliveryset');
        $s_sub_sql = "SELECT * FROM oxobject2delivery WHERE oxobject2delivery.OXDELIVERYID = {$s_table}.OXID AND oxobject2delivery.OXTYPE = 'rdfadeliveryset'";
        $this->select_string("SELECT {$s_table}.* FROM {$s_table} WHERE NOT EXISTS({$s_sub_sql}) AND {$s_table}.OXACTIVE = 1");
    }
    /**
     * Loads delivery set mapped to a
     * predefined GoodRelations delivery method.
     *
     * @param string $sDelId delivery set id
     */
    public function load_rd_fa_delivery_set_list($s_del_id = null): void
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxdeliveryset');
        if ($s_del_id) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_sub_sql = "( select {$s_table}.* from {$s_table} left join oxdel2delset on oxdel2delset.oxdelsetid={$s_table}.oxid where " . $this->get_base_object()->get_sql_active_snippet() . " and oxdel2delset.oxdelid = :oxdelid ) as {$s_table}";
        } else {
            $s_sub_sql = $s_table;
        }
        $s_q = "select {$s_table}.*, oxobject2delivery.oxobjectid from {$s_sub_sql} left join (select oxobject2delivery.* from oxobject2delivery where oxobject2delivery.oxtype = 'rdfadeliveryset' ) as oxobject2delivery on oxobject2delivery.oxdeliveryid={$s_table}.oxid where " . $this->get_base_object()->get_sql_active_snippet() . ' ';
        $this->select_string($s_q, ['oxdelid' => $s_del_id]);
    }
}