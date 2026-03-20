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
 * Class manages category attributes
 */
class Attribute_Category_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxtitle', 'oxcategories', 1, 1, 0], ['oxdesc', 'oxcategories', 1, 1, 0], ['oxid', 'oxcategories', 0, 0, 0], ['oxid', 'oxcategory2attribute', 0, 0, 1], ['oxid', 'oxcategories', 0, 0, 1]], 'container3' => [['oxtitle', 'oxattribute', 1, 1, 0], ['oxsort', 'oxcategory2attribute', 1, 0, 0], ['oxid', 'oxcategory2attribute', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_cat_table = $this->get_view_name('oxcategories');
        $s_discount_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_discount_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_discount_id) {
            $s_q_add = " from {$s_cat_table} where {$s_cat_table}.oxshopid = '" . $my_config->get_shop_id() . "' ";
            $s_q_add .= " and {$s_cat_table}.oxactive = '1' ";
        } else {
            $s_q_add = " from {$s_cat_table} left join oxcategory2attribute " . "on {$s_cat_table}.oxid=oxcategory2attribute.oxobjectid " . ' where oxcategory2attribute.oxattrid = ' . $o_db->quote($s_discount_id) . " and {$s_cat_table}.oxshopid = '" . $my_config->get_shop_id() . "' " . " and {$s_cat_table}.oxactive = '1' ";
        }
        if ($s_synch_discount_id && $s_synch_discount_id != $s_discount_id) {
            $s_q_add .= " and {$s_cat_table}.oxid not in ( select {$s_cat_table}.oxid " . "from {$s_cat_table} left join oxcategory2attribute " . "on {$s_cat_table}.oxid=oxcategory2attribute.oxobjectid " . ' where oxcategory2attribute.oxattrid = ' . $o_db->quote($s_synch_discount_id) . " and {$s_cat_table}.oxshopid = '" . $my_config->get_shop_id() . "' " . " and {$s_cat_table}.oxactive = '1' ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes category from Attributes list
     */
    public function remove_cat_from_attr(): void
    {
        $a_chosen_cat = $this->get_action_ids('oxcategory2attribute.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxcategory2attribute.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_cat)) {
            $s_chosen_categories = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_cat));
            $s_q = 'delete from oxcategory2attribute where oxcategory2attribute.oxid in (' . $s_chosen_categories . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
        $this->reset_content_cache();
    }
    /**
     * Adds category to Attributes list
     *
     * @throws Exception
     */
    public function add_cat_to_attr(): void
    {
        $a_add_category = $this->get_action_ids('oxcategories.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $o_attribute = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_cat_table = $this->get_view_name('oxcategories');
            $a_add_category = $this->get_all($this->add_filter("select {$s_cat_table}.oxid " . $this->get_query()));
        }
        if ($o_attribute->load($sox_id) && is_array($a_add_category)) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            foreach ($a_add_category as $s_add) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new_group->init('oxcategory2attribute');
                $s_ox_sort_field = 'oxcategory2attribute__oxsort';
                $s_object_id_field = 'oxcategory2attribute__oxobjectid';
                $s_attribute_id_field = 'oxcategory2attribute__oxattrid';
                $s_ox_id_field = 'oxattribute__oxid';
                $o_new_group->{$s_object_id_field} = new \Oxid_Esales\Eshop\Core\Field($s_add);
                $o_new_group->{$s_attribute_id_field} = new \Oxid_Esales\Eshop\Core\Field($o_attribute->{$s_ox_id_field}->value);
                $s_sql = 'select max(oxsort) + 1 from oxcategory2attribute where oxobjectid = :oxobjectid';
                $o_new_group->{$s_ox_sort_field} = new \Oxid_Esales\Eshop\Core\Field((int) $database->get_one($s_sql, ['oxobjectid' => $s_add]));
                $o_new_group->save();
            }
        }
        $this->reset_content_cache();
    }
}