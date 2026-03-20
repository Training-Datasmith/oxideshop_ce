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
 * Class manages deliveryset and delivery configuration
 */
class Delivery_Set_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxdelivery', 1, 1, 0],
        ['oxaddsum', 'oxdelivery', 1, 0, 0],
        ['oxaddsumtype', 'oxdelivery', 1, 0, 0],
        ['oxid', 'oxdelivery', 0, 0, 1],
    ], 'container2' => [['oxtitle', 'oxdelivery', 1, 1, 0], ['oxaddsum', 'oxdelivery', 1, 0, 0], ['oxaddsumtype', 'oxdelivery', 1, 0, 0], ['oxid', 'oxdel2delset', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_delivery_view_name = $this->get_view_name('oxdelivery');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$s_delivery_view_name} where 1 ";
        } else {
            $s_q_add = " from {$s_delivery_view_name} left join oxdel2delset on oxdel2delset.oxdelid={$s_delivery_view_name}.oxid ";
            $s_q_add .= 'where oxdel2delset.oxdelsetid = ' . $o_db->quote($s_id);
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= "and {$s_delivery_view_name}.oxid not in ( select {$s_delivery_view_name}.oxid from {$s_delivery_view_name} left join oxdel2delset on oxdel2delset.oxdelid={$s_delivery_view_name}.oxid ";
            $s_q_add .= 'where oxdel2delset.oxdelsetid = ' . $o_db->quote($s_synch_id) . ' ) ';
        }
        return $s_q_add;
    }
    /**
     * Remove this delivery cost from these sets
     */
    public function remove_from_set(): void
    {
        $a_remove_groups = $this->get_action_ids('oxdel2delset.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxdel2delset.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif ($a_remove_groups && is_array($a_remove_groups)) {
            $s_q = 'delete from oxdel2delset where oxdel2delset.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_remove_groups)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds this delivery cost to these sets
     *
     * @throws Exception
     */
    public function add_to_set(): void
    {
        $a_chosen_sets = $this->get_action_ids('oxdelivery.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_delivery_view_name = $this->get_view_name('oxdelivery');
            $a_chosen_sets = $this->get_all($this->add_filter("select {$s_delivery_view_name}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_sets)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            foreach ($a_chosen_sets as $s_chosen_set) {
                // check if we have this entry already in
                // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
                $s_id = $database->get_one('select oxid from oxdel2delset where oxdelid = :oxdelid and oxdelsetid = :oxdelsetid', ['oxdelid' => $s_chosen_set, 'oxdelsetid' => $sox_id]);
                if (!isset($s_id) || !$s_id) {
                    $o_del2delset = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                    $o_del2delset->init('oxdel2delset');
                    $o_del2delset->oxdel2delset__oxdelid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_set);
                    $o_del2delset->oxdel2delset__oxdelsetid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                    $o_del2delset->save();
                }
            }
        }
    }
}