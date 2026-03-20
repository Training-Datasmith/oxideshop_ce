<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages discount users
 */
class Discount_Users_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxusername', 'oxuser', 1, 0, 0], ['oxlname', 'oxuser', 0, 0, 0], ['oxfname', 'oxuser', 0, 0, 0], ['oxstreet', 'oxuser', 0, 0, 0], ['oxstreetnr', 'oxuser', 0, 0, 0], ['oxcity', 'oxuser', 0, 0, 0], ['oxzip', 'oxuser', 0, 0, 0], ['oxfon', 'oxuser', 0, 0, 0], ['oxbirthdate', 'oxuser', 0, 0, 0], ['oxid', 'oxobject2discount', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_user_table = $this->get_view_name('oxuser');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$s_user_table} where 1 ";
            if (!$o_config->get_config_param('blMallUsers')) {
                $s_q_add .= " and oxshopid = '" . $o_config->get_shop_id() . "' ";
            }
        } else if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add = " from oxobject2group left join {$s_user_table} on {$s_user_table}.oxid = oxobject2group.oxobjectid where oxobject2group.oxgroupsid = " . $o_db->quote($s_id);
            if (!$o_config->get_config_param('blMallUsers')) {
                $s_q_add .= " and {$s_user_table}.oxshopid = '" . $o_config->get_shop_id() . "' ";
            }
        } else {
            $s_q_add = " from oxobject2discount, {$s_user_table} where {$s_user_table}.oxid=oxobject2discount.oxobjectid ";
            $s_q_add .= ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_id) . " and oxobject2discount.oxtype = 'oxuser' ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= " and {$s_user_table}.oxid not in ( select {$s_user_table}.oxid from oxobject2discount, {$s_user_table} where {$s_user_table}.oxid=oxobject2discount.oxobjectid ";
            $s_q_add .= ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_synch_id) . " and oxobject2discount.oxtype = 'oxuser' ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes user from discount config
     */
    public function remove_disc_user(): void
    {
        $a_remove_groups = $this->get_action_ids('oxobject2discount.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2discount.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif ($a_remove_groups && is_array($a_remove_groups)) {
            $s_q = 'delete from oxobject2discount where oxobject2discount.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_remove_groups)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds user to discount config
     */
    public function add_disc_user(): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $a_chosen_usr = $this->get_action_ids('oxuser.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_user_table = $this->get_view_name('oxuser');
            $a_chosen_usr = $this->get_all($this->add_filter("select {$s_user_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_usr)) {
            foreach ($a_chosen_usr as $s_chosen_usr) {
                $o_object2discount = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2discount->init('oxobject2discount');
                $o_object2discount->oxobject2discount__oxdiscountid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2discount->oxobject2discount__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_usr);
                $o_object2discount->oxobject2discount__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxuser');
                $o_object2discount->save();
            }
        }
    }
}