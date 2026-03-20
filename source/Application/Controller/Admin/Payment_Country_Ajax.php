<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages payment countries
 */
class Payment_Country_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxtitle', 'oxcountry', 1, 1, 0], ['oxisoalpha2', 'oxcountry', 1, 0, 0], ['oxisoalpha3', 'oxcountry', 0, 0, 0], ['oxunnum3', 'oxcountry', 0, 0, 0], ['oxid', 'oxobject2payment', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        // looking for table/view
        $s_country_table = $this->get_view_name('oxcountry');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_country_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_country_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_country_id) {
            // which fields to load ?
            $s_q_add = " from {$s_country_table} where {$s_country_table}.oxactive = '1' ";
        } else {
            $s_q_add = " from oxobject2payment left join {$s_country_table} on {$s_country_table}.oxid=oxobject2payment.oxobjectid ";
            $s_q_add .= "where {$s_country_table}.oxactive = '1' and oxobject2payment.oxpaymentid = " . $o_db->quote($s_country_id) . " and oxobject2payment.oxtype = 'oxcountry' ";
        }
        if ($s_synch_country_id && $s_synch_country_id != $s_country_id) {
            $s_q_add .= "and {$s_country_table}.oxid not in ( ";
            $s_q_add .= "select {$s_country_table}.oxid from oxobject2payment left join {$s_country_table} on {$s_country_table}.oxid=oxobject2payment.oxobjectid ";
            $s_q_add .= 'where oxobject2payment.oxpaymentid = ' . $o_db->quote($s_synch_country_id) . " and oxobject2payment.oxtype = 'oxcountry' ) ";
        }
        return $s_q_add;
    }
    /**
     * Adds chosen user group (groups) to delivery list
     */
    public function add_pay_country(): void
    {
        $a_chosen_cntr = $this->get_action_ids('oxcountry.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_country_table = $this->get_view_name('oxcountry');
            $a_chosen_cntr = $this->get_all($this->add_filter("select {$s_country_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_cntr)) {
            foreach ($a_chosen_cntr as $s_chosen_cntr) {
                $o_object2payment = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2payment->init('oxobject2payment');
                $o_object2payment->oxobject2payment__oxpaymentid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2payment->oxobject2payment__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_cntr);
                $o_object2payment->oxobject2payment__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxcountry');
                $o_object2payment->save();
            }
        }
    }
    /**
     * Removes chosen user group (groups) from delivery list
     */
    public function remove_pay_country(): void
    {
        $a_chosen_cntr = $this->get_action_ids('oxobject2payment.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2payment.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_cntr)) {
            $s_q = 'delete from oxobject2payment where oxobject2payment.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_cntr)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
}