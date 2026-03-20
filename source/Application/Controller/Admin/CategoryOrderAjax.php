<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Object2Category;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages category articles order
 */
class Category_Order_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table, visible, multilanguage, ident
        ['oxartnum', 'oxarticles', 1, 0, 0],
        ['oxtitle', 'oxarticles', 1, 1, 0],
        ['oxpos', 'oxobject2category', 1, 0, 0],
        ['oxean', 'oxarticles', 0, 0, 0],
        ['oxmpn', 'oxarticles', 0, 0, 0],
        ['oxprice', 'oxarticles', 0, 0, 0],
        ['oxstock', 'oxarticles', 0, 0, 0],
        ['oxid', 'oxarticles', 0, 0, 1],
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 0, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxarticles', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        // looking for table/view
        $s_art_table = $this->get_view_name('oxarticles');
        $s_o2c_view = $this->get_view_name('oxobject2category');
        $o_db = Database_Provider::get_db();
        // category selected or not ?
        if ($s_synch_oxid = Registry::get_request()->get_request_escaped_parameter('synchoxid')) {
            $s_q_add = " from {$s_art_table} left join {$s_o2c_view} on {$s_art_table}.oxid={$s_o2c_view}.oxobjectid where" . " {$s_o2c_view}.oxcatnid = " . $o_db->quote($s_synch_oxid);
            if ($a_skip_art = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('neworder_sess')) {
                $s_q_add .= " and {$s_art_table}.oxid not in ( " . implode(', ', Database_Provider::get_db()->quote_array($a_skip_art)) . ' ) ';
            }
        } else {
            // which fields to load ?
            $s_q_add = " from {$s_art_table} where ";
            if ($a_skip_art = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('neworder_sess')) {
                $s_q_add .= " {$s_art_table}.oxid in ( " . implode(', ', Database_Provider::get_db()->quote_array($a_skip_art)) . ' ) ';
            } else {
                $s_q_add .= ' 1 = 0 ';
            }
        }
        return $s_q_add;
    }
    /**
     * Returns SQL query addon for sorting
     *
     * @return string
     */
    protected function get_sorting()
    {
        $s_order = '';
        if (Registry::get_request()->get_request_escaped_parameter('synchoxid')) {
            $s_order = parent::get_sorting();
        } elseif ($a_skip_art = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('neworder_sess')) {
            $s_order_by = '';
            $s_art_table = $this->get_view_name('oxarticles');
            $s_sep = '';
            foreach ($a_skip_art as $s_id) {
                $s_order_by = " {$s_art_table}.oxid=" . Database_Provider::get_db()->quote($s_id) . ' ' . $s_sep . $s_order_by;
                $s_sep = ', ';
            }
            $s_order = 'order by ' . $s_order_by;
        }
        return $s_order;
    }
    /**
     * Removes article from list for sorting in category
     */
    public function remove_cat_order_article(): void
    {
        $a_remove_art = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $a_skip_art = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('neworder_sess');
        if (is_array($a_remove_art) && is_array($a_skip_art)) {
            foreach ($a_remove_art as $s_rem) {
                if (($i_key = array_search($s_rem, $a_skip_art)) !== false) {
                    unset($a_skip_art[$i_key]);
                }
            }
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('neworder_sess', $a_skip_art);
            $s_article_table = $this->get_view_name('oxarticles');
            $s_o2c_view = $this->get_view_name('oxobject2category');
            // checking if all articles were moved from one
            $s_select = "select 1 from {$s_article_table} left join {$s_o2c_view} on {$s_article_table}.oxid={$s_o2c_view}.oxobjectid ";
            $s_select .= "where {$s_o2c_view}.oxcatnid = :oxcatnid";
            if (count($a_skip_art)) {
                $s_select .= " and {$s_article_table}.oxparentid = '' and {$s_article_table}.oxid ";
                $s_select .= 'not in ( ' . implode(', ', Database_Provider::get_db()->quote_array($a_skip_art)) . ' ) ';
            }
            // Simply echoing "1" if some items found, and 0 if nothing was found
            // We force reading from master to prevent issues with slow replications or open transactions
            // (see ESDEV-3804).
            echo (int) Database_Provider::get_master()->get_one($s_select, ['oxcatnid' => $sox_id]);
        }
    }
    /**
     * Adds article to list for sorting in category
     */
    public function add_cat_order_article(): void
    {
        $a_add_article = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $a_ord_art = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('neworder_sess');
        if (!is_array($a_ord_art)) {
            $a_ord_art = [];
        }
        if (is_array($a_add_article)) {
            // storing newly ordered article seq.
            foreach ($a_add_article as $s_add) {
                if (array_search($s_add, $a_ord_art) === false) {
                    $a_ord_art[] = $s_add;
                }
            }
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('neworder_sess', $a_ord_art);
            $s_article_table = $this->get_view_name('oxarticles');
            $s_o2c_view = $this->get_view_name('oxobject2category');
            // checking if all articles were moved from one
            $s_select = "select 1 from {$s_article_table} left join {$s_o2c_view} on {$s_article_table}.oxid={$s_o2c_view}.oxobjectid " . "where {$s_o2c_view}.oxcatnid = :oxcatnid and {$s_article_table}.oxparentid = '' and {$s_article_table}.oxid " . 'not in ( ' . implode(', ', Database_Provider::get_db()->quote_array($a_ord_art)) . ' ) ';
            // Simply echoing "1" if some items found, and 0 if nothing was found
            // We force reading from master to prevent issues with slow replications or open transactions
            // (see ESDEV-3804).
            echo (int) Database_Provider::get_master()->get_one($s_select, ['oxcatnid' => $sox_id]);
        }
    }
    /**
     * Saves category articles ordering.
     */
    public function save_new_order(): void
    {
        $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        if ($o_category->load($s_id)) {
            //Disable editing for derived items
            if ($o_category->is_derived()) {
                return;
            }
            $this->reset_content_cache();
            $a_new_order = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('neworder_sess');
            if (is_array($a_new_order) && count($a_new_order)) {
                $s_o2c_view = $this->get_view_name('oxobject2category');
                $s_select = "select * from {$s_o2c_view} where {$s_o2c_view}.oxcatnid = :oxcatnid and {$s_o2c_view}.oxobjectid in (" . implode(', ', Database_Provider::get_db()->quote_array($a_new_order)) . ' )';
                $o_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
                $o_list->init($this->get_object2category_class(), 'oxobject2category');
                $o_list->select_string($s_select, ['oxcatnid' => $o_category->get_id()]);
                // setting new position
                foreach ($o_list as $o_obj) {
                    if (($i_new_pos = array_search($o_obj->oxobject2category__oxobjectid->value, $a_new_order)) !== false) {
                        $o_obj->oxobject2category__oxpos->set_value($i_new_pos);
                        $o_obj->save();
                    }
                }
                \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('neworder_sess', null);
            }
            $this->on_category_change($s_id);
        }
    }
    /**
     * Removes category articles ordering set by saveneworder() method.
     */
    public function rem_new_order(): void
    {
        $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $s_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        if ($o_category->load($s_id)) {
            //Disable editing for derived items
            if ($o_category->is_derived()) {
                return;
            }
            $o_db = Database_Provider::get_db();
            $s_sql_shop_filter = $this->update_query_filter_for_reset_category_articles_order();
            $s_select = sprintf("update oxobject2category set oxpos = '0' where oxobject2category.oxcatnid = :id %s", $s_sql_shop_filter);
            $o_db->execute($s_select, ['id' => $o_category->get_id()]);
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('neworder_sess', null);
            $this->on_category_change($s_id);
        }
    }
    /**
     * @return string
     */
    protected function update_query_filter_for_reset_category_articles_order()
    {
        return '';
    }
    /**
     * @param string $categoryId
     */
    protected function on_category_change($category_id)
    {
    }
    private function get_object2category_class(): string
    {
        return Registry::get_utils_object()->get_class_name(Object2Category::class);
    }
}