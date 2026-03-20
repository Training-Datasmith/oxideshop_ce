<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages delivery users
 */
class Delivery_Users_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxusername', 'oxuser', 1, 0, 0], ['oxlname', 'oxuser', 0, 0, 0], ['oxfname', 'oxuser', 0, 0, 0], ['oxstreet', 'oxuser', 0, 0, 0], ['oxstreetnr', 'oxuser', 0, 0, 0], ['oxcity', 'oxuser', 0, 0, 0], ['oxzip', 'oxuser', 0, 0, 0], ['oxfon', 'oxuser', 0, 0, 0], ['oxbirthdate', 'oxuser', 0, 0, 0], ['oxid', 'oxobject2delivery', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_user_table = $this->get_view_name('oxuser');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$s_user_table} where 1 ";
            if (!$my_config->get_config_param('blMallUsers')) {
                $s_q_add .= " and {$s_user_table}.oxshopid = '" . $my_config->get_shop_id() . "' ";
            }
        } elseif ($s_synch_id && $s_synch_id != $s_id) {
            // selected group ?
            $s_q_add = " from oxobject2group left join {$s_user_table} on {$s_user_table}.oxid = oxobject2group.oxobjectid ";
            $s_q_add .= ' where oxobject2group.oxgroupsid = ' . $o_db->quote($s_id);
            if (!$my_config->get_config_param('blMallUsers')) {
                $s_q_add .= " and {$s_user_table}.oxshopid = '" . $my_config->get_shop_id() . "' ";
            }
        } else {
            $s_q_add = " from oxobject2delivery left join {$s_user_table} on {$s_user_table}.oxid=oxobject2delivery.oxobjectid ";
            $s_q_add .= ' where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_id) . " and oxobject2delivery.oxtype = 'oxuser' and {$s_user_table}.oxid IS NOT NULL ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= " and {$s_user_table}.oxid not in ( select {$s_user_table}.oxid from oxobject2delivery left join {$s_user_table} on {$s_user_table}.oxid=oxobject2delivery.oxobjectid ";
            $s_q_add .= ' where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_synch_id) . " and oxobject2delivery.oxtype = 'oxuser' and {$s_user_table}.oxid IS NOT NULL ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes user from delivery configuration
     */
    public function remove_user_from_del(): void
    {
        $a_remove_groups = $this->get_action_ids('oxobject2delivery.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2delivery.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif ($a_remove_groups && is_array($a_remove_groups)) {
            $s_q = 'delete from oxobject2delivery where oxobject2delivery.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_remove_groups)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds user from delivery configuration
     */
    public function add_user_to_del(): void
    {
        $a_chosen_usr = $this->get_action_ids('oxuser.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_user_table = $this->get_view_name('oxuser');
            $a_chosen_usr = $this->get_all($this->add_filter("select {$s_user_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_usr)) {
            foreach ($a_chosen_usr as $s_chosen_usr) {
                $o_object2delivery = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2delivery->init('oxobject2delivery');
                $o_object2delivery->oxobject2delivery__oxdeliveryid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2delivery->oxobject2delivery__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_usr);
                $o_object2delivery->oxobject2delivery__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxuser');
                $o_object2delivery->save();
            }
        }
    }
}