<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages deliveryset payment
 */
class Delivery_Set_Payment_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxdesc', 'oxpayments', 1, 1, 0],
        ['oxaddsum', 'oxpayments', 1, 0, 0],
        ['oxaddsumtype', 'oxpayments', 0, 0, 0],
        ['oxid', 'oxpayments', 0, 0, 1],
    ], 'container2' => [['oxdesc', 'oxpayments', 1, 1, 0], ['oxaddsum', 'oxpayments', 1, 0, 0], ['oxaddsumtype', 'oxpayments', 0, 0, 0], ['oxid', 'oxobject2payment', 0, 0, 1]]];
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
        $s_pay_table = $this->get_view_name('oxpayments');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$s_pay_table} where 1 ";
        } else {
            $s_q_add = " from oxobject2payment, {$s_pay_table} where oxobject2payment.oxobjectid = " . $o_db->quote($s_id);
            $s_q_add .= " and oxobject2payment.oxpaymentid = {$s_pay_table}.oxid and oxobject2payment.oxtype = 'oxdelset' ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= "and {$s_pay_table}.oxid not in ( select {$s_pay_table}.oxid from oxobject2payment, {$s_pay_table} where oxobject2payment.oxobjectid = " . $o_db->quote($s_synch_id);
            $s_q_add .= "and oxobject2payment.oxpaymentid = {$s_pay_table}.oxid and oxobject2payment.oxtype = 'oxdelset' ) ";
        }
        return $s_q_add;
    }
    /**
     * Remove these payments from this set
     */
    public function remove_pay_from_set(): void
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
    /**
     * Adds this payments to this set
     *
     * @throws Exception
     */
    public function add_pay_to_set(): void
    {
        $a_chosen_sets = $this->get_action_ids('oxpayments.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_pay_table = $this->get_view_name('oxpayments');
            $a_chosen_sets = $this->get_all($this->add_filter("select {$s_pay_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_sets)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            foreach ($a_chosen_sets as $s_chosen_set) {
                // check if we have this entry already in
                // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
                $s_id = $database->get_one("select oxid from oxobject2payment where oxpaymentid = :oxpaymentid and oxobjectid = :oxobjectid and oxtype = 'oxdelset'", ['oxpaymentid' => $s_chosen_set, 'oxobjectid' => $sox_id]);
                if (!isset($s_id) || !$s_id) {
                    $o_object = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                    $o_object->init('oxobject2payment');
                    $o_object->oxobject2payment__oxpaymentid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_set);
                    $o_object->oxobject2payment__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                    $o_object->oxobject2payment__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxdelset');
                    $o_object->save();
                }
            }
        }
    }
}