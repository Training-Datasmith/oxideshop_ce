<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages deliveryset countries
 */
class Delivery_Set_Country_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxcountry', 1, 1, 0],
        ['oxisoalpha2', 'oxcountry', 1, 0, 0],
        ['oxisoalpha3', 'oxcountry', 0, 0, 0],
        ['oxunnum3', 'oxcountry', 0, 0, 0],
        ['oxid', 'oxcountry', 0, 0, 1],
    ], 'container2' => [['oxtitle', 'oxcountry', 1, 1, 0], ['oxisoalpha2', 'oxcountry', 1, 0, 0], ['oxisoalpha3', 'oxcountry', 0, 0, 0], ['oxunnum3', 'oxcountry', 0, 0, 0], ['oxid', 'oxobject2delivery', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $s_country_table = $this->get_view_name('oxcountry');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$s_country_table} where {$s_country_table}.oxactive = '1' ";
        } else {
            $s_q_add = " from oxobject2delivery, {$s_country_table} " . 'where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_id) . " and oxobject2delivery.oxobjectid = {$s_country_table}.oxid " . "and oxobject2delivery.oxtype = 'oxdelset' ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= "and {$s_country_table}.oxid not in ( select {$s_country_table}.oxid " . "from oxobject2delivery, {$s_country_table} " . 'where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_synch_id) . "and oxobject2delivery.oxobjectid = {$s_country_table}.oxid " . "and oxobject2delivery.oxtype = 'oxdelset' ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes chosen countries from delivery list
     */
    public function remove_country_from_set(): void
    {
        $a_chosen_cntr = $this->get_action_ids('oxobject2delivery.oxid');
        // removing all
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2delivery.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_cntr)) {
            $s_chosen_countries = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_cntr));
            $s_q = 'delete from oxobject2delivery where oxobject2delivery.oxid in (' . $s_chosen_countries . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds chosen countries to delivery list
     */
    public function add_country_to_set(): void
    {
        $a_chosen_cntr = $this->get_action_ids('oxcountry.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_country_table = $this->get_view_name('oxcountry');
            $a_chosen_cntr = $this->get_all($this->add_filter("select {$s_country_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_cntr)) {
            foreach ($a_chosen_cntr as $s_chosen_cntr) {
                $o_object2delivery = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2delivery->init('oxobject2delivery');
                $o_object2delivery->oxobject2delivery__oxdeliveryid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2delivery->oxobject2delivery__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_cntr);
                $o_object2delivery->oxobject2delivery__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxdelset');
                $o_object2delivery->save();
            }
        }
    }
}