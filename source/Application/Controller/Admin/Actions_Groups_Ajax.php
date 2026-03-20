<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages promotion groups
 */
class Actions_Groups_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = [
        // field , table,  visible, multilanguage, ident
        'container1' => [['oxtitle', 'oxgroups', 1, 0, 0], ['oxid', 'oxgroups', 0, 0, 0], ['oxid', 'oxgroups', 0, 0, 1]],
        'container2' => [['oxtitle', 'oxgroups', 1, 0, 0], ['oxid', 'oxgroups', 0, 0, 0], ['oxid', 'oxobject2action', 0, 0, 1]],
    ];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        // active AJAX component
        $s_group_table = $this->get_view_name('oxgroups');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$s_group_table} where 1 ";
        } else {
            $s_q_add = " from oxobject2action, {$s_group_table} where {$s_group_table}.oxid=oxobject2action.oxobjectid " . ' and oxobject2action.oxactionid = ' . $o_db->quote($s_id) . " and oxobject2action.oxclass = 'oxgroups' ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= " and {$s_group_table}.oxid not in ( select {$s_group_table}.oxid " . "from oxobject2action, {$s_group_table} where {$s_group_table}.oxid=oxobject2action.oxobjectid " . ' and oxobject2action.oxactionid = ' . $o_db->quote($s_synch_id) . " and oxobject2action.oxclass = 'oxgroups' ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes user group from promotion
     */
    public function remove_promotion_group(): void
    {
        $a_remove_groups = $this->get_action_ids('oxobject2action.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2action.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif ($a_remove_groups && is_array($a_remove_groups)) {
            $s_remove_groups = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_remove_groups));
            $s_q = 'delete from oxobject2action where oxobject2action.oxid in (' . $s_remove_groups . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds user group to promotion
     *
     * @return bool Whether at least one promotion was added.
     */
    public function add_promotion_group()
    {
        $a_chosen_group = $this->get_action_ids('oxgroups.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_group_table = $this->get_view_name('oxgroups');
            $a_chosen_group = $this->get_all($this->add_filter("select {$s_group_table}.oxid " . $this->get_query()));
        }
        $promotion_added = false;
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_group)) {
            foreach ($a_chosen_group as $s_chosen_group) {
                $o_object2promotion = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2promotion->init('oxobject2action');
                $o_object2promotion->oxobject2action__oxactionid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2promotion->oxobject2action__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_group);
                $o_object2promotion->oxobject2action__oxclass = new \Oxid_Esales\Eshop\Core\Field('oxgroups');
                $o_object2promotion->save();
            }
            $promotion_added = true;
        }
        return $promotion_added;
    }
}