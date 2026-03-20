<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Payment list manager.
 */
class Payment_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Home country id
     *
     * @var string
     */
    protected $_s_home_country;
    /**
     * Class Constructor
     */
    public function __construct()
    {
        $this->set_home_country(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aHomeCountry'));
        parent::__construct('oxpayment');
    }
    /**
     * Home country setter
     *
     * @param string $sHomeCountry country id
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
     * Creates payment list filter SQL to load current state payment list
     *
     * @param string                                   $sShipSetId user chosen delivery set
     * @param double                                   $dPrice     basket products price
     * @param \OxidEsales\Eshop\Application\Model\User $oUser      session user object
     *
     * @return string
     */
    protected function get_filter_select($s_ship_set_id, $d_price, $o_user)
    {
        $o_db = Database_Provider::get_db();
        $s_boni = $o_user && $o_user->get_field_data('oxboni') ? $o_user->oxuser__oxboni->value : 0;
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxpayments');
        $s_q = "select {$s_table}.* from ( select distinct {$s_table}.* from {$s_table} ";
        $s_q .= 'inner join oxobject2payment ON oxobject2payment.oxobjectid = ' . $o_db->quote($s_ship_set_id) . " and oxobject2payment.oxpaymentid = {$s_table}.oxid ";
        $s_q .= "where {$s_table}.oxactive='1' ";
        $s_q .= " and {$s_table}.oxfromboni <= " . $o_db->quote($s_boni) . " and {$s_table}.oxfromamount <= " . $o_db->quote($d_price) . " and {$s_table}.oxtoamount >= " . $o_db->quote($d_price);
        // defining initial filter parameters
        $s_group_ids = '';
        $s_country_id = $this->get_country_id($o_user);
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($o_user) {
            // user groups ( maybe would be better to fetch by function \OxidEsales\Eshop\Application\Model\User::getUserGroups() ? )
            foreach ($o_user->get_user_groups() as $o_group) {
                if ($s_group_ids) {
                    $s_group_ids .= ', ';
                }
                $s_group_ids .= "'" . $o_group->get_id() . "'";
            }
        }
        $s_group_table = $table_view_name_generator->get_view_name('oxgroups');
        $s_country_table = $table_view_name_generator->get_view_name('oxcountry');
        $s_country_sql = $s_country_id ? "exists( select 1 from oxobject2payment as s1 where s1.oxpaymentid={$s_table}.OXID and s1.oxtype='oxcountry' and s1.OXOBJECTID=" . $o_db->quote($s_country_id) . ' limit 1 )' : '0';
        $s_group_sql = $s_group_ids ? "exists( select 1 from oxobject2group as s3 where s3.OXOBJECTID={$s_table}.OXID and s3.OXGROUPSID in ( {$s_group_ids} ) limit 1 )" : '0';
        return $s_q . "  order by {$s_table}.oxsort asc ) as {$s_table} where (\n                if( exists( select 1 from oxobject2payment as ss1, {$s_country_table} where {$s_country_table}.oxid=ss1.oxobjectid and ss1.oxpaymentid={$s_table}.OXID and ss1.oxtype='oxcountry' limit 1 ),\n                    {$s_country_sql},\n                    1) &&\n                if( exists( select 1 from oxobject2group as ss3, {$s_group_table} where {$s_group_table}.oxid=ss3.oxgroupsid and ss3.OXOBJECTID={$s_table}.OXID limit 1 ),\n                    {$s_group_sql},\n                    1)\n                )  order by {$s_table}.oxsort asc ";
    }
    /**
     * Returns user country id for for payment selection
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser oxuser object
     *
     * @return string
     */
    public function get_country_id($o_user)
    {
        $s_country_id = null;
        if ($o_user) {
            $s_country_id = $o_user->get_active_country();
        }
        if (!$s_country_id) {
            return $this->_s_home_country;
        }
        return $s_country_id;
    }
    /**
     * Loads and returns list of user payments.
     *
     * @param string                                   $sShipSetId user chosen delivery set
     * @param double                                   $dPrice     basket product price excl. discount
     * @param \OxidEsales\Eshop\Application\Model\User $oUser      session user object
     *
     * @return array
     */
    public function get_payment_list($s_ship_set_id, $d_price, $o_user = null)
    {
        $this->select_string($this->get_filter_select($s_ship_set_id, $d_price, $o_user));
        return $this->_a_array;
    }
    /**
     * Loads an object including all payments which are not mapped to a
     * predefined GoodRelations payment method.
     */
    public function load_non_rd_fa_payment_list(): void
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxpayments');
        $s_sub_sql = "SELECT * FROM oxobject2payment WHERE oxobject2payment.OXPAYMENTID = {$s_table}.OXID AND oxobject2payment.OXTYPE = 'rdfapayment'";
        $this->select_string("SELECT {$s_table}.* FROM {$s_table} WHERE NOT EXISTS({$s_sub_sql}) AND {$s_table}.OXACTIVE = 1");
    }
    /**
     * Loads payments mapped to a
     * predefined GoodRelations payment method.
     *
     * @param double $dPrice product price
     */
    public function load_rd_fa_payment_list($d_price = null): void
    {
        $o_db = Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxpayments');
        $s_q = "select {$s_table}.*, oxobject2payment.oxobjectid from {$s_table} left join (select oxobject2payment.* from oxobject2payment where oxobject2payment.oxtype = 'rdfapayment') as oxobject2payment on oxobject2payment.oxpaymentid={$s_table}.oxid ";
        $s_q .= "where {$s_table}.oxactive = 1 ";
        if ($d_price !== null) {
            $s_q .= "and {$s_table}.oxfromamount <= :amount and {$s_table}.oxtoamount >= :amount";
        }
        $rs = $o_db->select($s_q, ['amount' => $d_price]);
        if ($rs != false && $rs->count() > 0) {
            $o_saved = clone $this->get_base_object();
            while (!$rs->EOF) {
                $o_list_object = clone $o_saved;
                $this->assign_element($o_list_object, $rs->fields);
                $this->_a_array[] = $o_list_object;
                $rs->fetch_row();
            }
        }
    }
}