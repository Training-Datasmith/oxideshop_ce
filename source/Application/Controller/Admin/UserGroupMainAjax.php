<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages users assignment to groups
 */
class User_Group_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,  visible, multilanguage, ident
        ['oxusername', 'oxuser', 1, 0, 0],
        ['oxlname', 'oxuser', 0, 0, 0],
        ['oxfname', 'oxuser', 0, 0, 0],
        ['oxstreet', 'oxuser', 0, 0, 0],
        ['oxstreetnr', 'oxuser', 0, 0, 0],
        ['oxcity', 'oxuser', 0, 0, 0],
        ['oxzip', 'oxuser', 0, 0, 0],
        ['oxfon', 'oxuser', 0, 0, 0],
        ['oxbirthdate', 'oxuser', 0, 0, 0],
        ['oxid', 'oxuser', 0, 0, 1],
    ], 'container2' => [['oxusername', 'oxuser', 1, 0, 0], ['oxlname', 'oxuser', 0, 0, 0], ['oxfname', 'oxuser', 0, 0, 0], ['oxstreet', 'oxuser', 0, 0, 0], ['oxstreetnr', 'oxuser', 0, 0, 0], ['oxcity', 'oxuser', 0, 0, 0], ['oxzip', 'oxuser', 0, 0, 0], ['oxfon', 'oxuser', 0, 0, 0], ['oxbirthdate', 'oxuser', 0, 0, 0], ['oxid', 'oxobject2group', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // looking for table/view
        $s_user_table = $this->get_view_name('oxuser');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_role_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_role_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_role_id) {
            $s_q_add = " from {$s_user_table} where 1 ";
        } else {
            $s_q_add = " from {$s_user_table}, oxobject2group where {$s_user_table}.oxid=oxobject2group.oxobjectid and ";
            $s_q_add .= ' oxobject2group.oxgroupsid = ' . $o_db->quote($s_role_id);
        }
        if ($s_synch_role_id && $s_synch_role_id != $s_role_id) {
            $s_q_add .= " and {$s_user_table}.oxid not in ( select {$s_user_table}.oxid from {$s_user_table}, oxobject2group where {$s_user_table}.oxid=oxobject2group.oxobjectid and ";
            $s_q_add .= ' oxobject2group.oxgroupsid = ' . $o_db->quote($s_synch_role_id);
            if (!$my_config->get_config_param('blMallUsers')) {
                $s_q_add .= " and {$s_user_table}.oxshopid = '" . $my_config->get_shop_id() . "' ";
            }
            $s_q_add .= ' ) ';
        }
        if (!$my_config->get_config_param('blMallUsers')) {
            $s_q_add .= " and {$s_user_table}.oxshopid = '" . $my_config->get_shop_id() . "' ";
        }
        return $s_q_add;
    }
    /**
     * Removes User from group
     */
    public function remove_user_from_u_group(): void
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
     * Adds User to group
     */
    public function add_user_to_u_group(): void
    {
        $a_add_users = $this->get_action_ids('oxuser.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_user_table = $this->get_view_name('oxuser');
            $a_add_users = $this->get_all($this->add_filter("select {$s_user_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_add_users)) {
            foreach ($a_add_users as $s_adduser) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Application\Model\Object2Group::class);
                $o_new_group->oxobject2group__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_adduser);
                $o_new_group->oxobject2group__oxgroupsid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_new_group->save();
            }
        }
    }
}