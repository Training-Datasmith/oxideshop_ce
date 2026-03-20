<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Promotion List manager.
 */
class Action_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_s_objects_in_list_name = 'oxactions';
    /**
     * Loads x last finished promotions
     *
     * @param int $iCount count to load
     */
    public function load_finished_by_count($i_count): void
    {
        $s_view_name = $this->get_base_object()->get_view_name();
        $s_date = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = "select * from {$s_view_name} where oxtype=2 and oxactive=1 and oxshopid='" . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id() . "' and oxactiveto>0 and oxactiveto < " . $o_db->quote($s_date) . '
               ' . $this->get_user_group_filter() . '
               order by oxactiveto desc, oxactivefrom desc limit ' . (int) $i_count;
        $this->select_string($s_q);
        $this->_a_array = array_reverse($this->_a_array, true);
    }
    /**
     * Loads last finished promotions after given timespan
     *
     * @param int $iTimespan timespan to load
     */
    public function load_finished_by_timespan($i_timespan): void
    {
        $s_view_name = $this->get_base_object()->get_view_name();
        $s_date_to = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        $s_date_from = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time() - $i_timespan);
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = "select * from {$s_view_name} where oxtype=2 and oxactive=1 and oxshopid='" . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id() . "' and oxactiveto < " . $o_db->quote($s_date_to) . ' and oxactiveto > ' . $o_db->quote($s_date_from) . '
               ' . $this->get_user_group_filter() . '
               order by oxactiveto, oxactivefrom';
        $this->select_string($s_q);
    }
    /**
     * Loads current promotions
     */
    public function load_current(): void
    {
        $s_view_name = $this->get_base_object()->get_view_name();
        $s_date = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = "select * from {$s_view_name} where oxtype=2 and oxactive=1 and oxshopid='" . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id() . "' and (oxactiveto > " . $o_db->quote($s_date) . ' or oxactiveto=0) and oxactivefrom != 0 and oxactivefrom < ' . $o_db->quote($s_date) . '
               ' . $this->get_user_group_filter() . '
               order by oxactiveto, oxactivefrom';
        $this->select_string($s_q);
    }
    /**
     * Loads next not yet started promotions by cound
     *
     * @param int $iCount count to load
     */
    public function load_future_by_count($i_count): void
    {
        $s_view_name = $this->get_base_object()->get_view_name();
        $s_date = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = "select * from {$s_view_name} where oxtype=2 and oxactive=1 and oxshopid='" . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id() . "' and (oxactiveto > " . $o_db->quote($s_date) . ' or oxactiveto=0) and oxactivefrom > ' . $o_db->quote($s_date) . '
               ' . $this->get_user_group_filter() . '
               order by oxactiveto, oxactivefrom limit ' . (int) $i_count;
        $this->select_string($s_q);
    }
    /**
     * Loads next not yet started promotions before the given timespan
     *
     * @param int $iTimespan timespan to load
     */
    public function load_future_by_timespan($i_timespan): void
    {
        $s_view_name = $this->get_base_object()->get_view_name();
        $s_date = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        $s_date_to = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time() + $i_timespan);
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = "select * from {$s_view_name} where oxtype=2 and oxactive=1 and oxshopid='" . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id() . "' and (oxactiveto > " . $o_db->quote($s_date) . ' or oxactiveto=0) and oxactivefrom > ' . $o_db->quote($s_date) . ' and oxactivefrom < ' . $o_db->quote($s_date_to) . '
               ' . $this->get_user_group_filter() . '
               order by oxactiveto, oxactivefrom';
        $this->select_string($s_q);
    }
    /**
     * Returns part of user group filter query
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object
     *
     * @return string
     */
    protected function get_user_group_filter($o_user = null)
    {
        $o_user = $o_user == null ? $this->get_user() : $o_user;
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxactions');
        $s_group_table = $table_view_name_generator->get_view_name('oxgroups');
        $a_ids = [];
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($o_user && count($a_group_ids = $o_user->get_user_groups())) {
            foreach ($a_group_ids as $o_group) {
                $a_ids[] = $o_group->get_id();
            }
        }
        $s_group_sql = count($a_ids) ? "EXISTS(select oxobject2action.oxid from oxobject2action where oxobject2action.oxactionid={$s_table}.OXID and oxobject2action.oxclass='oxgroups' and oxobject2action.OXOBJECTID in (" . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_ids)) . ') )' : '0';
        return " and (\n                if(EXISTS(select 1 from oxobject2action, {$s_group_table} where {$s_group_table}.oxid=oxobject2action.oxobjectid and oxobject2action.oxactionid={$s_table}.OXID and oxobject2action.oxclass='oxgroups' LIMIT 1),\n                    {$s_group_sql},\n                    1)\n            ) ";
    }
    /**
     * return true if there are any active promotions
     *
     * @return boolean
     */
    public function are_any_active_promotions()
    {
        return (bool) $this->fetch_exists_active_promotion();
    }
    /**
     * Fetch the information, if there is an active promotion.
     *
     * @return string One, if there is an active promotion.
     */
    protected function fetch_exists_active_promotion()
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $query = 'select 1 from ' . $table_view_name_generator->get_view_name('oxactions') . ' 
            where oxtype = :oxtype and oxactive = :oxactive and oxshopid = :oxshopid 
            limit 1';
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($query, ['oxtype' => 2, 'oxactive' => 1, 'oxshopid' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id()]);
    }
    /**
     * load active shop banner list
     */
    public function load_banners(): void
    {
        $o_base_object = $this->get_base_object();
        $o_view_name = $o_base_object->get_view_name();
        $s_q = "select * from {$o_view_name} where oxtype=3 and " . $o_base_object->get_sql_active_snippet() . " and oxshopid='" . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id() . "' " . $this->get_user_group_filter() . ' order by oxsort';
        $this->select_string($s_q);
    }
}