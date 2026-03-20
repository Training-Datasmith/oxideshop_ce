<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages deliveryset groups
 */
class Delivery_Set_Groups_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,  visible, multilanguage, ident
        ['oxtitle', 'oxgroups', 1, 0, 0],
        ['oxid', 'oxgroups', 0, 0, 0],
        ['oxid', 'oxgroups', 0, 0, 1],
    ], 'container2' => [['oxtitle', 'oxgroups', 1, 0, 0], ['oxid', 'oxgroups', 0, 0, 0], ['oxid', 'oxobject2delivery', 0, 0, 1]]];
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
        $sgroup_table = $this->get_view_name('oxgroups');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$sgroup_table} where 1 ";
        } else {
            $s_q_add = " from oxobject2delivery, {$sgroup_table} " . 'where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_id) . " and oxobject2delivery.oxobjectid = {$sgroup_table}.oxid " . "and oxobject2delivery.oxtype = 'oxdelsetg' ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= " and {$sgroup_table}.oxid not in ( select {$sgroup_table}.oxid " . "from oxobject2delivery, {$sgroup_table} " . 'where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_synch_id) . " and oxobject2delivery.oxobjectid = {$sgroup_table}.oxid " . "and oxobject2delivery.oxtype = 'oxdelsetg' ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes user group from delivery sets config
     */
    public function remove_group_from_set(): void
    {
        $a_remove_groups = $this->get_action_ids('oxobject2delivery.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2delivery.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif ($a_remove_groups && is_array($a_remove_groups)) {
            $s_remove_groups = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_remove_groups));
            $s_q = 'delete from oxobject2delivery where oxobject2delivery.oxid in (' . $s_remove_groups . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds user group to delivery sets config
     */
    public function add_group_to_set(): void
    {
        $a_chosen_cat = $this->get_action_ids('oxgroups.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_group_table = $this->get_view_name('oxgroups');
            $a_chosen_cat = $this->get_all($this->add_filter("select {$s_group_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_cat)) {
            foreach ($a_chosen_cat as $s_chosen_cat) {
                $o_object2delivery = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2delivery->init('oxobject2delivery');
                $o_object2delivery->oxobject2delivery__oxdeliveryid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2delivery->oxobject2delivery__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_cat);
                $o_object2delivery->oxobject2delivery__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxdelsetg');
                $o_object2delivery->save();
            }
        }
    }
}