<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Class manages discount articles
 */
class Discount_Item_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = [
        // field , table, visible, multilanguage, id
        'container1' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxarticles', 0, 0, 1]],
        'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxitmartid', 'oxdiscount', 0, 0, 1]],
    ];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_article_table = $this->get_view_name('oxarticles');
        $s_o2c_view = $this->get_view_name('oxobject2category');
        $s_disc_table = $this->get_view_name('oxdiscount');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_oxid = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_oxid = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_oxid && $s_synch_oxid) {
            $s_q_add = " from {$s_article_table} where 1 ";
            $s_q_add .= $o_config->get_config_param('blVariantsSelection') ? '' : "and {$s_article_table}.oxparentid = '' ";
            //#6027
            //if we have variants then depending on config option the parent may be non buyable
            //when the checkbox is checked, blVariantParentBuyable is true.
            $s_q_add .= $o_config->get_config_param('blVariantParentBuyable') ? '' : "and {$s_article_table}.oxvarcount = 0";
        } else if ($s_synch_oxid && $s_oxid != $s_synch_oxid) {
            $s_q_add = " from {$s_o2c_view} left join {$s_article_table} on ";
            $s_q_add .= $o_config->get_config_param('blVariantsSelection') ? "({$s_article_table}.oxid={$s_o2c_view}.oxobjectid or {$s_article_table}.oxparentid={$s_o2c_view}.oxobjectid)" : " {$s_article_table}.oxid={$s_o2c_view}.oxobjectid ";
            $s_q_add .= " where {$s_o2c_view}.oxcatnid = " . $o_db->quote($s_oxid) . " and {$s_article_table}.oxid is not null ";
            //#6027
            $s_q_add .= $o_config->get_config_param('blVariantParentBuyable') ? '' : " and {$s_article_table}.oxvarcount = 0";
            // resetting
            $s_id = null;
        } else {
            $s_q_add = " from {$s_disc_table} left join {$s_article_table} on {$s_article_table}.oxid={$s_disc_table}.oxitmartid ";
            $s_q_add .= " where {$s_disc_table}.oxid = " . $o_db->quote($s_oxid) . " and {$s_disc_table}.oxitmartid != '' ";
        }
        if ($s_synch_oxid && $s_synch_oxid != $s_oxid) {
            // performance
            $s_sub_select = " select {$s_article_table}.oxid from {$s_disc_table}, {$s_article_table} where {$s_article_table}.oxid={$s_disc_table}.oxitmartid ";
            $s_sub_select .= " and {$s_disc_table}.oxid = " . $o_db->quote($s_synch_oxid);
            if (stristr($s_q_add, 'where') === false) {
                $s_q_add .= ' where ';
            } else {
                $s_q_add .= ' and ';
            }
            $s_q_add .= " {$s_article_table}.oxid not in ( {$s_sub_select} ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes selected article (articles) from discount list
     */
    public function remove_disc_art(): void
    {
        $sox_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $a_chosen_art = $this->get_action_ids('oxdiscount.oxitmartid');
        if (is_array($a_chosen_art)) {
            $s_q = "update oxdiscount set oxitmartid = '' where oxid = :oxid and oxitmartid = :oxitmartid";
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->execute($s_q, ['oxid' => $sox_id, 'oxitmartid' => reset($a_chosen_art)]);
        }
    }
    /**
     * Adds selected article (articles) to discount list
     */
    public function add_disc_art(): void
    {
        $a_chosen_art = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_art)) {
            $s_q = 'update oxdiscount set oxitmartid = :oxitmartid where oxid = :oxid';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->execute($s_q, ['oxitmartid' => reset($a_chosen_art), 'oxid' => $sox_id]);
        }
    }
    /**
     * Formats and returns chunk of SQL query string with definition of
     * fields to load from DB. Adds subselect to get variant title from parent article
     *
     * @return string
     */
    protected function get_query_cols()
    {
        $query_for_id_columns = $this->get_query_for_identifier_columns();
        return sprintf(' %s%s%s ', $this->get_query_for_visible_columns(), $query_for_id_columns ? ', ' : '', $query_for_id_columns);
    }
    private function get_query_for_visible_columns(): string
    {
        $query = '';
        $language_suffix = $this->get_language_suffix();
        $select_variants_enabled = Registry::get_config()->get_config_param('blVariantsSelection');
        foreach ($this->get_visible_col_names() as $key => [$column_name, $table_name]) {
            $view = $this->get_view_name($table_name);
            if ($select_variants_enabled && $column_name === 'oxtitle') {
                $query .= sprintf(' IF( %s.%s != \'\', %1$s.%2$s, CONCAT((select oxart.%2$s from %1$s as oxart where oxart.oxid = %1$s.oxparentid),\', \',%1$s.oxvarselect%s)) as _%s', $view, $column_name, $language_suffix, $key);
            } else {
                $query .= "{$view}.{$column_name} as _{$key}";
            }
            $query .= ', ';
        }
        return $query ? rtrim($query, ', ') : $query;
    }
    private function get_query_for_identifier_columns(): string
    {
        $query = '';
        foreach ($this->get_ident_col_names() as $key => [$column_name, $table_name]) {
            $view = $this->get_view_name($table_name);
            $query .= "{$view}.{$column_name} as _{$key}";
            $query .= ', ';
        }
        return $query ? rtrim($query, ', ') : $query;
    }
    private function get_language_suffix(): string
    {
        return Container_Facade::get_parameter('oxid_esales.skip_database_views_usage') ? Registry::get_lang()->get_language_tag() : '';
    }
}