<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages delivery categories
 */
class Delivery_Categories_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxcategories', 1, 1, 0],
        ['oxdesc', 'oxcategories', 1, 1, 0],
        ['oxid', 'oxcategories', 0, 0, 0],
        ['oxid', 'oxcategories', 0, 0, 1],
    ], 'container2' => [['oxtitle', 'oxcategories', 1, 1, 0], ['oxdesc', 'oxcategories', 1, 1, 0], ['oxid', 'oxcategories', 0, 0, 0], ['oxid', 'oxobject2delivery', 0, 0, 1], ['oxid', 'oxcategories', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        // looking for table/view
        $s_cat_table = $this->get_view_name('oxcategories');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_del_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_del_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_del_id) {
            $s_q_add = " from {$s_cat_table} ";
        } else {
            $s_q_add = " from oxobject2delivery left join {$s_cat_table} " . "on {$s_cat_table}.oxid=oxobject2delivery.oxobjectid " . ' where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_del_id) . " and oxobject2delivery.oxtype = 'oxcategories' ";
        }
        if ($s_synch_del_id && $s_synch_del_id != $s_del_id) {
            // performance
            $s_sub_select = " select {$s_cat_table}.oxid from oxobject2delivery left join {$s_cat_table} " . "on {$s_cat_table}.oxid=oxobject2delivery.oxobjectid " . ' where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_synch_del_id) . " and oxobject2delivery.oxtype = 'oxcategories' ";
            if (stristr($s_q_add, 'where') === false) {
                $s_q_add .= ' where ';
            } else {
                $s_q_add .= ' and ';
            }
            $s_q_add .= " {$s_cat_table}.oxid not in ( {$s_sub_select} ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes category from delivery configuration
     */
    public function remove_cat_from_del(): void
    {
        $a_chosen_cat = $this->get_action_ids('oxobject2delivery.oxid');
        // removing all
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2delivery.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_cat)) {
            $s_chosen_categoriess = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_cat));
            $s_q = 'delete from oxobject2delivery where oxobject2delivery.oxid in (' . $s_chosen_categoriess . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds category to delivery configuration
     */
    public function add_cat_to_del(): void
    {
        $a_chosen_cat = $this->get_action_ids('oxcategories.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_cat_table = $this->get_view_name('oxcategories');
            $a_chosen_cat = $this->get_all($this->add_filter("select {$s_cat_table}.oxid " . $this->get_query()));
        }
        if (isset($sox_id) && $sox_id != '-1' && isset($a_chosen_cat) && $a_chosen_cat) {
            foreach ($a_chosen_cat as $s_chosen_cat) {
                $o_object2delivery = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2delivery->init('oxobject2delivery');
                $o_object2delivery->oxobject2delivery__oxdeliveryid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2delivery->oxobject2delivery__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_cat);
                $o_object2delivery->oxobject2delivery__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxcategories');
                $o_object2delivery->save();
            }
        }
    }
}