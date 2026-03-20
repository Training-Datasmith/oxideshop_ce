<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
/**
 * Class controls article assignment to action
 */
class Actions_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_bl_allow_ext_columns = true;
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxartnum', 'oxarticles', 1, 0, 0],
        ['oxtitle', 'oxarticles', 1, 1, 0],
        ['oxean', 'oxarticles', 1, 0, 0],
        ['oxmpn', 'oxarticles', 0, 0, 0],
        ['oxprice', 'oxarticles', 0, 0, 0],
        ['oxstock', 'oxarticles', 0, 0, 0],
        ['oxid', 'oxarticles', 0, 0, 1],
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxsort', 'oxactions2article', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxactions2article', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // looking for table/view
        $s_art_table = $this->get_view_name('oxarticles');
        $s_view = $this->get_view_name('oxobject2category');
        $s_sel_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_sel_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_sel_id) {
            //performance
            $s_q_add = " from {$s_art_table} where 1 ";
            $s_q_add .= $my_config->get_config_param('blVariantsSelection') ? '' : " and {$s_art_table}.oxparentid = '' ";
        } else if ($s_synch_sel_id && $s_sel_id != $s_synch_sel_id) {
            $s_q_add = " from {$s_view} left join {$s_art_table} on ";
            $bl_variants_selection_parameter = $my_config->get_config_param('blVariantsSelection');
            $s_sql_if_true = " ( {$s_art_table}.oxid={$s_view}.oxobjectid or {$s_art_table}.oxparentid={$s_view}.oxobjectid) ";
            $s_sql_if_false = " {$s_art_table}.oxid={$s_view}.oxobjectid ";
            $s_q_add .= $bl_variants_selection_parameter ? $s_sql_if_true : $s_sql_if_false;
            $s_q_add .= " where {$s_view}.oxcatnid = " . $o_db->quote($s_sel_id);
        } else {
            $s_q_add = " from {$s_art_table} left join oxactions2article " . "on {$s_art_table}.oxid=oxactions2article.oxartid " . ' where oxactions2article.oxactionid = ' . $o_db->quote($s_sel_id) . " and oxactions2article.oxshopid = '" . $my_config->get_shop_id() . "' ";
        }
        if ($s_synch_sel_id && $s_synch_sel_id != $s_sel_id) {
            $s_q_add .= " and {$s_art_table}.oxid not in ( select oxactions2article.oxartid from oxactions2article " . ' where oxactions2article.oxactionid = ' . $o_db->quote($s_synch_sel_id) . " and oxactions2article.oxshopid = '" . $my_config->get_shop_id() . "' ) ";
        }
        return $s_q_add;
    }
    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     */
    protected function add_filter($s_q)
    {
        $s_q = parent::add_filter($s_q);
        // display variants or not ?
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blVariantsSelection')) {
            $s_q .= ' group by ' . $this->get_view_name('oxarticles') . '.oxid ';
            $o_str = Str::get_str();
            if ($o_str->strpos($s_q, 'select count( * ) ') === 0) {
                $s_q = "select count( * ) from ( {$s_q} ) as _cnttable";
            }
        }
        return $s_q;
    }
    /**
     * Returns SQL query addon for sorting
     *
     * @return string
     */
    protected function get_sorting()
    {
        $s_ox_id_parameter = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_oxid_parameter = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if ($s_ox_id_parameter && !$s_synch_oxid_parameter) {
            return 'order by oxactions2article.oxsort ';
        }
        return parent::get_sorting();
    }
    /**
     * Removes article from Promotions list
     */
    public function remove_art_from_act(): void
    {
        $a_chosen_art = $this->get_action_ids('oxactions2article.oxid');
        Registry::get_request()->get_request_escaped_parameter('oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = parent::add_filter('delete oxactions2article.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_chosen_articles = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_art));
            $s_q = 'delete from oxactions2article where oxactions2article.oxid in (' . $s_chosen_articles . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds article to Promotions list
     *
     * @return bool Whether any article was added to action.
     *
     * @throws Exception
     */
    public function add_art_to_act()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $a_articles = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_art_table = $this->get_view_name('oxarticles');
            $a_articles = $this->get_all($this->add_filter("select {$s_art_table}.oxid " . $this->get_query()));
        }
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        $s_art_table = $this->get_view_name('oxarticles');
        $s_q = "select max(oxactions2article.oxsort) from oxactions2article join {$s_art_table} " . "on {$s_art_table}.oxid=oxactions2article.oxartid " . 'where oxactions2article.oxactionid = :oxactionid ' . 'and oxactions2article.oxshopid = :oxshopid ' . "and {$s_art_table}.oxid is not null";
        $parameters = ['oxactionid' => $sox_id, 'oxshopid' => $my_config->get_shop_id()];
        $i_sort = (int) $database->get_one($s_q, $parameters) + 1;
        $article_added = false;
        if ($sox_id && $sox_id != '-1' && is_array($a_articles)) {
            $s_shop_id = $my_config->get_shop_id();
            foreach ($a_articles as $s_add) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new_group->init('oxactions2article');
                $o_new_group->oxactions2article__oxshopid = new \Oxid_Esales\Eshop\Core\Field($s_shop_id);
                $o_new_group->oxactions2article__oxactionid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_new_group->oxactions2article__oxartid = new \Oxid_Esales\Eshop\Core\Field($s_add);
                $o_new_group->oxactions2article__oxsort = new \Oxid_Esales\Eshop\Core\Field($i_sort++);
                $o_new_group->save();
            }
            $article_added = true;
        }
        return $article_added;
    }
    /**
     * Sets sorting position for current action article
     */
    public function set_sorting(): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_art_table = $this->get_view_name('oxarticles');
        $s_sel_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_select = "select * from {$s_art_table} left join oxactions2article on {$s_art_table}.oxid=oxactions2article.oxartid ";
        $s_select .= 'where oxactions2article.oxactionid = :oxactionid ' . 'and oxactions2article.oxshopid = :oxshopid ' . $this->get_sorting();
        $o_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_list->init('oxbase', 'oxactions2article');
        $o_list->select_string($s_select, ['oxactionid' => $s_sel_id, 'oxshopid' => $my_config->get_shop_id()]);
        // fixing indexes
        $i_sel_cnt = 0;
        $a_idx2id = [];
        foreach ($o_list as $s_key => $o_sel) {
            if ($o_sel->oxactions2article__oxsort->value != $i_sel_cnt) {
                $o_sel->oxactions2article__oxsort->set_value($i_sel_cnt);
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
                $i_copy = $o_dir1->oxactions2article__oxsort->value;
                $o_dir1->oxactions2article__oxsort->set_value($o_dir2->oxactions2article__oxsort->value);
                $o_dir2->oxactions2article__oxsort->set_value($i_copy);
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