<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages article select lists sorting
 */
class Attribute_Order_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [['oxtitle', 'oxattribute', 1, 1, 0], ['oxsort', 'oxcategory2attribute', 1, 0, 0], ['oxid', 'oxcategory2attribute', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $s_sel_table = $this->get_view_name('oxattribute');
        $s_art_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        return " from {$s_sel_table} left join oxcategory2attribute on oxcategory2attribute.oxattrid = {$s_sel_table}.oxid " . 'where oxobjectid = ' . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($s_art_id) . ' ';
    }
    /**
     * Returns SQL query addon for sorting
     *
     * @return string
     */
    protected function get_sorting()
    {
        return 'order by oxcategory2attribute.oxsort ';
    }
    /**
     * Applies sorting for selection lists
     */
    public function set_sorting(): void
    {
        $s_sel_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_select = 'select * from oxcategory2attribute where oxobjectid = :oxobjectid order by oxsort';
        $o_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_list->init('oxbase', 'oxcategory2attribute');
        $o_list->select_string($s_select, ['oxobjectid' => $s_sel_id]);
        // fixing indexes
        $i_sel_cnt = 0;
        $a_idx2id = [];
        foreach ($o_list as $s_key => $o_sel) {
            if ($o_sel->oxcategory2attribute__oxsort->value != $i_sel_cnt) {
                $o_sel->oxcategory2attribute__oxsort->set_value($i_sel_cnt);
                // saving new index
                $o_sel->save();
            }
            $a_idx2id[$i_sel_cnt] = $s_key;
            $i_sel_cnt++;
        }
        if (($i_key = array_search(Registry::get_request()->get_request_escaped_parameter('sortoxid'), $a_idx2id)) !== false) {
            $i_dir = Registry::get_request()->get_request_escaped_parameter('direction') == 'up' ? $i_key - 1 : $i_key + 1;
            if (isset($a_idx2id[$i_dir])) {
                // exchanging indexes
                $o_dir1 = $o_list->offsetGet($a_idx2id[$i_dir]);
                $o_dir2 = $o_list->offsetGet($a_idx2id[$i_key]);
                $i_copy = $o_dir1->oxcategory2attribute__oxsort->value;
                $o_dir1->oxcategory2attribute__oxsort->set_value($o_dir2->oxcategory2attribute__oxsort->value);
                $o_dir2->oxcategory2attribute__oxsort->set_value($i_copy);
                $o_dir1->save();
                $o_dir2->save();
            }
        }
        $s_q_add = $this->get_query();
        $s_q = 'select ' . $this->get_query_cols() . $s_q_add;
        $s_count_q = 'select count( * ) ' . $s_q_add;
        $this->output_response($this->get_data($s_count_q, $s_q));
    }
}