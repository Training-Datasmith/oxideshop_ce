<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Delivery list manager.
 */
class Delivery_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Session user Id
     *
     * @var string
     */
    protected $_s_user_id;
    /**
     * Performance - load or not delivery list
     *
     * @var bool
     */
    protected $_bl_perf_load_delivery;
    /**
     * Deliveries list
     *
     * @var array
     */
    protected $_a_deliveries = [];
    /**
     * User object
     *
     * @var \OxidEsales\Eshop\Application\Model\User
     */
    protected $_o_user;
    /**
     * Home country info array
     *
     * @var array
     */
    protected $_s_home_country;
    /**
     * Collect fitting deliveries sets instead of fitting deliveries
     * Default is false
     *
     * @var bool
     */
    protected $_bl_collect_fitting_deliveries_sets = false;
    /**
     * Calls parent constructor and sets home country
     */
    public function __construct()
    {
        parent::__construct('oxdelivery');
        // load or not delivery list
        $this->set_home_country(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aHomeCountry'));
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
     * Returns active delivery list
     *
     * Loads all active delivery in list. Additionally
     * checks if set has user customized parameters like
     * assigned users, countries or user groups. Performs
     * additional filtering according to these parameters
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser      session user object
     * @param string                                   $sCountryId user country id
     * @param string                                   $sDelSet    user chosen delivery set
     *
     * @return array
     */
    protected function get_active_delivery_list($o_user = null, $s_country_id = null, $s_del_set = null)
    {
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($o_user === null) {
            $o_user = $this->get_user();
        } else {
            //set user
            $this->set_user($o_user);
        }
        $s_user_id = $o_user ? $o_user->get_id() : '';
        // choosing delivery country if it is not set yet
        if (!$s_country_id) {
            if ($o_user) {
                $s_country_id = $o_user->get_active_country();
            } else {
                $s_country_id = $this->_s_home_country;
            }
        }
        if ($s_user_id . $s_country_id . $s_del_set !== $this->_s_user_id) {
            $this->select_string($this->get_filter_select($o_user, $s_country_id, $s_del_set));
            $this->_s_user_id = $s_user_id . $s_country_id . $s_del_set;
        }
        $this->rewind();
        return $this;
    }
    /**
     * Creates delivery list filter SQL to load current state delivery list
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser      session user object
     * @param string                                   $sCountryId user country id
     * @param string                                   $sDelSet    user chosen delivery set
     *
     * @return string
     */
    protected function get_filter_select($o_user, $s_country_id, $s_del_set)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxdelivery');
        $s_q = "select {$s_table}.* from ( select distinct {$s_table}.* from {$s_table} left join oxdel2delset on oxdel2delset.oxdelid={$s_table}.oxid ";
        $s_q .= 'where ' . $this->get_base_object()->get_sql_active_snippet() . ' and oxdel2delset.oxdelsetid = ' . $o_db->quote($s_del_set) . ' ';
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
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_user_table = $table_view_name_generator->get_view_name('oxuser');
        $s_group_table = $table_view_name_generator->get_view_name('oxgroups');
        $s_country_table = $table_view_name_generator->get_view_name('oxcountry');
        $s_country_sql = $s_country_id ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxcountry' and oxobject2delivery.OXOBJECTID=" . $o_db->quote($s_country_id) . ')' : '0';
        $s_user_sql = $s_user_id ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxuser' and oxobject2delivery.OXOBJECTID=" . $o_db->quote($s_user_id) . ')' : '0';
        $s_group_sql = count($a_ids) ? "EXISTS(select oxobject2delivery.oxid from oxobject2delivery where oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxgroups' and oxobject2delivery.OXOBJECTID in (" . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_ids)) . ') )' : '0';
        $s_q .= " order by {$s_table}.oxsort asc ) as {$s_table} where (\n                if(EXISTS(select 1 from oxobject2delivery, {$s_country_table} where {$s_country_table}.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxcountry' LIMIT 1),\n                    {$s_country_sql},\n                    1) &&\n                if(EXISTS(select 1 from oxobject2delivery, {$s_user_table} where {$s_user_table}.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxuser' LIMIT 1),\n                    {$s_user_sql},\n                    1) &&\n                if(EXISTS(select 1 from oxobject2delivery, {$s_group_table} where {$s_group_table}.oxid=oxobject2delivery.oxobjectid and oxobject2delivery.oxdeliveryid={$s_table}.OXID and oxobject2delivery.oxtype='oxgroups' LIMIT 1),\n                    {$s_group_sql},\n                    1)\n            )";
        return $s_q . " order by {$s_table}.oxsort asc ";
    }
    /**
     * Loads and returns list of deliveries.
     *
     * Process:
     *
     *  - first checks if delivery loading is enabled in config -
     *    $myConfig->bl_perfLoadDelivery is TRUE;
     *  - loads delivery set list by calling this::GetDeliverySetList(...);
     *  - checks if there is any active (eg. chosen delivery set in order
     *    process etc) delivery set defined and if its set - rearranges
     *    delivery set list by storing active set at the beginning in the
     *    list.
     *  - goes through delivery sets and loads its deliveries, checks if any
     *    delivery fits. By checking calculates and stores conditional
     *    amounts:
     *
     *       oDelivery->iItemCnt - items in basket that fits this delivery
     *       oDelivery->iProdCnt - products in basket that fits this delivery
     *       oDelivery->dPrice   - price of products that fits this delivery
     *
     *  - returns a list of deliveries.
     *    NOTICE: for performance reasons deliveries is cached in
     *    $myConfig->aDeliveryList.
     *
     * @param object                                   $oBasket     basket object
     * @param \OxidEsales\Eshop\Application\Model\User $oUser       session user
     * @param string                                   $sDelCountry user country id
     * @param string                                   $sDelSet     delivery set id
     *
     * @return array
     */
    public function get_delivery_list($o_basket, $o_user = null, $s_del_country = null, $s_del_set = null)
    {
        // ids of deliveries that does not fit for us to skip double check
        $a_skip_deliveries = [];
        $a_fitting_del_sets = [];
        $this->_a_deliveries = [];
        $a_del_set_list = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Delivery_Set_List::class)->get_delivery_set_list($o_user, $s_del_country, $s_del_set);
        // must choose right delivery set to use its delivery list
        foreach ($a_del_set_list as $s_delivery_set_id => $o_delivery_set) {
            // loading delivery list to check if some of them fits
            $a_deliveries = $this->get_active_delivery_list($o_user, $s_del_country, $s_delivery_set_id);
            $bl_del_found = false;
            foreach ($a_deliveries as $s_delivery_id => $o_delivery) {
                // skipping that was checked and didn't fit before
                if (in_array($s_delivery_id, $a_skip_deliveries)) {
                    continue;
                }
                $a_skip_deliveries[] = $s_delivery_id;
                if ($o_delivery->is_for_basket($o_basket)) {
                    // delivery fits conditions
                    $this->_a_deliveries[$s_delivery_id] = $a_deliveries[$s_delivery_id];
                    $bl_del_found = true;
                    // removing from unfitting list
                    array_pop($a_skip_deliveries);
                    // maybe checked "Stop processing after first match" ?
                    if ($o_delivery->oxdelivery__oxfinalize->value) {
                        break;
                    }
                }
            }
            // found delivery set and deliveries that fits
            if ($bl_del_found) {
                if ($this->_bl_collect_fitting_deliveries_sets) {
                    // collect only deliveries sets that fits deliveries
                    $a_fitting_del_sets[$s_delivery_set_id] = $o_delivery_set;
                } else {
                    // return collected fitting deliveries
                    \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('sShipSet', $s_delivery_set_id);
                    return $this->_a_deliveries;
                }
            }
        }
        //return deliveries sets if found
        if ($this->_bl_collect_fitting_deliveries_sets && count($a_fitting_del_sets)) {
            //resetting getting delivery sets list instead of deliveries before return
            $this->_bl_collect_fitting_deliveries_sets = false;
            //reset cache and list
            $this->set_user(null);
            $this->clear();
            return $a_fitting_del_sets;
        }
        // nothing what fits was found
        return [];
    }
    /**
     * Checks if deliveries in list fits for current basket and delivery set
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket        shop basket
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser          session user
     * @param string                                     $sDelCountry    delivery country
     * @param string                                     $sDeliverySetId delivery set id to check its relation to delivery list
     *
     * @return bool
     */
    public function has_deliveries($o_basket, $o_user, $s_del_country, $s_delivery_set_id)
    {
        $bl_has = false;
        // loading delivery list to check if some of them fits
        $this->get_active_delivery_list($o_user, $s_del_country, $s_delivery_set_id);
        foreach ($this as $o_delivery) {
            if ($o_delivery->is_for_basket($o_basket)) {
                $bl_has = true;
                break;
            }
        }
        return $bl_has;
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
     * Force or not to collect deliveries sets instead of deliveries when
     * getting deliveries list in getDeliveryList()
     *
     * @param bool $blCollectFittingDeliveriesSets collect deliveries sets or not
     */
    public function set_collect_fitting_deliveries_sets($bl_collect_fitting_deliveries_sets = false): void
    {
        $this->_bl_collect_fitting_deliveries_sets = $bl_collect_fitting_deliveries_sets;
    }
    /**
     * Load oxDeliveryList for product
     *
     * @param object $oProduct oxArticle object
     */
    public function load_delivery_list_for_product($o_product): void
    {
        $d_price = $o_product->get_price()->get_brutto_price();
        $d_size = $o_product->get_size();
        $d_weight = $o_product->get_weight();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxdelivery');
        $params = [];
        $s_q = "select {$s_table}.* from {$s_table}";
        $s_q .= ' where ' . $this->get_base_object()->get_sql_active_snippet();
        $s_q .= " and ({$s_table}.oxdeltype != 'a' || ( {$s_table}.oxparam <= 1 && {$s_table}.oxparamend >= 1))";
        if ($d_price) {
            $s_q .= " and ({$s_table}.oxdeltype != 'p' || ( {$s_table}.oxparam <= :dprice && {$s_table}.oxparamend >= :dprice))";
            $params['dprice'] = $d_price;
        }
        if ($d_size) {
            $s_q .= " and ({$s_table}.oxdeltype != 's' || ( {$s_table}.oxparam <= :dsize && {$s_table}.oxparamend >= :dsize))";
            $params['dsize'] = $d_size;
        }
        if ($d_weight) {
            $s_q .= " and ({$s_table}.oxdeltype != 'w' || ( {$s_table}.oxparam <= :dweight && {$s_table}.oxparamend >= :dweight))";
            $params['dweight'] = $d_weight;
        }
        $this->select_string($s_q, $params);
    }
}