<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages discount groups
 */
class Discount_Groups_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /** If this discount id comes from request, it means that new discount should be created. */
    public const NEW_DISCOUNT_ID = '-1';
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = [
        // field , table,  visible, multilanguage, id
        'container1' => [['oxtitle', 'oxgroups', 1, 0, 0], ['oxid', 'oxgroups', 0, 0, 0], ['oxid', 'oxgroups', 0, 0, 1]],
        'container2' => [['oxtitle', 'oxgroups', 1, 0, 0], ['oxid', 'oxgroups', 0, 0, 0], ['oxid', 'oxobject2discount', 0, 0, 1]],
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
            $s_q_add = " from oxobject2discount, {$s_group_table} where {$s_group_table}.oxid=oxobject2discount.oxobjectid ";
            $s_q_add .= ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_id) . " and oxobject2discount.oxtype = 'oxgroups' ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            $s_q_add .= " and {$s_group_table}.oxid not in ( select {$s_group_table}.oxid " . "from oxobject2discount, {$s_group_table} where {$s_group_table}.oxid=oxobject2discount.oxobjectid " . ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_synch_id) . " and oxobject2discount.oxtype = 'oxgroups' ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes user group from discount config
     */
    public function remove_disc_group(): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $group_ids = $this->get_action_ids('oxobject2discount.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $query = $this->add_filter('delete oxobject2discount.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($query);
        } elseif ($group_ids && is_array($group_ids)) {
            $group_ids_quoted = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($group_ids));
            $query = 'delete from oxobject2discount where oxobject2discount.oxid in (' . $group_ids_quoted . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($query);
        }
    }
    /**
     * Adds user group to discount config
     */
    public function add_disc_group(): void
    {
        $group_ids = $this->get_action_ids('oxgroups.oxid');
        $discount_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $group_table = $this->get_view_name('oxgroups');
            $group_ids = $this->get_all($this->add_filter("select {$group_table}.oxid " . $this->get_query()));
        }
        if ($discount_id && $discount_id != self::NEW_DISCOUNT_ID && is_array($group_ids)) {
            foreach ($group_ids as $group_id) {
                $object2Discount = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $object2Discount->init('oxobject2discount');
                $object2Discount->oxobject2discount__oxdiscountid = new \Oxid_Esales\Eshop\Core\Field($discount_id);
                $object2Discount->oxobject2discount__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($group_id);
                $object2Discount->oxobject2discount__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxgroups');
                $object2Discount->save();
            }
        }
    }
}