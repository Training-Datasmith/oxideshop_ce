<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages discount categories
 */
class Discount_Categories_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /** If this discount id comes from request, it means that new discount should be created. */
    public const NEW_DISCOUNT_ID = '-1';
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = [
        // field , table, visible, multilanguage, id
        'container1' => [['oxtitle', 'oxcategories', 1, 1, 0], ['oxdesc', 'oxcategories', 1, 1, 0], ['oxid', 'oxcategories', 0, 0, 0], ['oxid', 'oxcategories', 0, 0, 1]],
        'container2' => [['oxtitle', 'oxcategories', 1, 1, 0], ['oxdesc', 'oxcategories', 1, 1, 0], ['oxid', 'oxcategories', 0, 0, 0], ['oxid', 'oxobject2discount', 0, 0, 1], ['oxid', 'oxcategories', 0, 0, 1]],
    ];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $s_category_table = $this->get_view_name('oxcategories');
        // category selected or not ?
        if (!$s_id) {
            $s_q_add = " from {$s_category_table}";
        } else {
            $s_q_add = " from oxobject2discount, {$s_category_table} " . "where {$s_category_table}.oxid=oxobject2discount.oxobjectid " . ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_id) . " and oxobject2discount.oxtype = 'oxcategories' ";
        }
        if ($s_synch_id && $s_synch_id != $s_id) {
            // performance
            $s_sub_select = " select {$s_category_table}.oxid from oxobject2discount, {$s_category_table} " . "where {$s_category_table}.oxid=oxobject2discount.oxobjectid " . ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_synch_id) . " and oxobject2discount.oxtype = 'oxcategories' ";
            if (stristr($s_q_add, 'where') === false) {
                $s_q_add .= ' where ';
            } else {
                $s_q_add .= ' and ';
            }
            $s_q_add .= " {$s_category_table}.oxid not in ( {$s_sub_select} ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes selected category (categories) from discount list
     */
    public function remove_disc_cat(): void
    {
        $category_ids = $this->get_action_ids('oxobject2discount.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $query = $this->add_filter('delete oxobject2discount.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($query);
        } elseif (is_array($category_ids)) {
            $chosen_categories = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($category_ids));
            $query = 'delete from oxobject2discount where oxobject2discount.oxid in (' . $chosen_categories . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($query);
        }
    }
    /**
     * Adds selected category (categories) to discount list
     */
    public function add_disc_cat(): void
    {
        $category_ids = $this->get_action_ids('oxcategories.oxid');
        $discount_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $category_table = $this->get_view_name('oxcategories');
            $category_ids = $this->get_all($this->add_filter("select {$category_table}.oxid " . $this->get_query()));
        }
        if ($discount_id && $discount_id != self::NEW_DISCOUNT_ID && is_array($category_ids)) {
            foreach ($category_ids as $category_id) {
                $this->add_category_to_discount($discount_id, $category_id);
            }
        }
    }
    /**
     * Adds category to discounts list.
     *
     * @param string $discountId
     * @param string $categoryId
     */
    protected function add_category_to_discount($discount_id, $category_id)
    {
        $object2Discount = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
        $object2Discount->init('oxobject2discount');
        $object2Discount->oxobject2discount__oxdiscountid = new \Oxid_Esales\Eshop\Core\Field($discount_id);
        $object2Discount->oxobject2discount__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($category_id);
        $object2Discount->oxobject2discount__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxcategories');
        $object2Discount->save();
    }
}