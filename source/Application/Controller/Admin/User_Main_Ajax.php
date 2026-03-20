<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages user assignment to groups
 */
class User_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxtitle', 'oxgroups', 1, 0, 0], ['oxid', 'oxgroups', 0, 0, 0], ['oxid', 'oxobject2group', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetch
     *
     * @return string
     */
    protected function get_query()
    {
        // looking for table/view
        $s_group_table = $this->get_view_name('oxgroups');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_deld_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_del_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_deld_id) {
            $s_q_add = " from {$s_group_table} where 1 ";
        } else {
            $s_q_add = " from {$s_group_table} left join oxobject2group on oxobject2group.oxgroupsid={$s_group_table}.oxid ";
            $s_q_add .= ' where oxobject2group.oxobjectid = ' . $o_db->quote($s_deld_id);
        }
        if ($s_synch_del_id && $s_synch_del_id != $s_deld_id) {
            $s_q_add .= " and {$s_group_table}.oxid not in ( select {$s_group_table}.oxid from {$s_group_table} left join oxobject2group on oxobject2group.oxgroupsid={$s_group_table}.oxid ";
            $s_q_add .= ' where oxobject2group.oxobjectid = ' . $o_db->quote($s_synch_del_id) . ' ) ';
        }
        return $s_q_add;
    }
    /**
     * Removes user from selected user group(s).
     */
    public function remove_user_from_group(): void
    {
        $a_remove_groups = $this->get_action_ids('oxobject2group.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2group.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif ($a_remove_groups && is_array($a_remove_groups)) {
            $s_q = 'delete from oxobject2group where oxobject2group.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_remove_groups)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds user to selected user group(s).
     */
    public function add_user_to_group(): void
    {
        $a_add_groups = $this->get_action_ids('oxgroups.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_group_table = $this->get_view_name('oxgroups');
            $a_add_groups = $this->get_all($this->add_filter("select {$s_group_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_add_groups)) {
            foreach ($a_add_groups as $s_addgroup) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Application\Model\Object2Group::class);
                $o_new_group->oxobject2group__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_new_group->oxobject2group__oxgroupsid = new \Oxid_Esales\Eshop\Core\Field($s_addgroup);
                $o_new_group->save();
            }
        }
    }
}