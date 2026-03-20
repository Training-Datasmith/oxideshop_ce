<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class controls article crossselling configuration
 */
class Article_Crossselling_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxobject2article', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_article_table = $this->get_view_name('oxarticles');
        $s_view = $this->get_view_name('oxobject2category');
        $s_sel_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_sel_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // category selected or not ?
        if (!$s_sel_id) {
            $s_q_add = " from {$s_article_table} where 1 ";
            $s_q_add .= $my_config->get_config_param('blVariantsSelection') ? '' : " and {$s_article_table}.oxparentid = '' ";
        } elseif ($s_synch_sel_id && $s_sel_id != $s_synch_sel_id) {
            // selected category ?
            $bl_variants_selection_parameter = $my_config->get_config_param('blVariantsSelection');
            $s_sql_if_true = " ({$s_article_table}.oxid=oxobject2category.oxobjectid " . "or {$s_article_table}.oxparentid=oxobject2category.oxobjectid)";
            $s_sql_if_false = " {$s_article_table}.oxid=oxobject2category.oxobjectid ";
            $s_variants_selection_snippet = $bl_variants_selection_parameter ? $s_sql_if_true : $s_sql_if_false;
            $s_q_add = " from {$s_view} as oxobject2category left join {$s_article_table} on {$s_variants_selection_snippet}" . ' where oxobject2category.oxcatnid = ' . $o_db->quote($s_sel_id) . ' ';
        } elseif ($my_config->get_config_param('blBidirectCross')) {
            $s_q_add = ' from oxobject2article ' . " inner join {$s_article_table} on ( oxobject2article.oxobjectid = {$s_article_table}.oxid " . " or oxobject2article.oxarticlenid = {$s_article_table}.oxid ) " . ' where ( oxobject2article.oxarticlenid = ' . $o_db->quote($s_sel_id) . ' or oxobject2article.oxobjectid = ' . $o_db->quote($s_sel_id) . ' ) ' . " and {$s_article_table}.oxid != " . $o_db->quote($s_sel_id) . ' ';
        } else {
            $s_q_add = " from oxobject2article left join {$s_article_table} " . "on oxobject2article.oxobjectid={$s_article_table}.oxid " . ' where oxobject2article.oxarticlenid = ' . $o_db->quote($s_sel_id) . ' ';
        }
        if ($s_synch_sel_id && $s_synch_sel_id != $s_sel_id) {
            if ($my_config->get_config_param('blBidirectCross')) {
                $s_sub_select = "select {$s_article_table}.oxid from oxobject2article " . "left join {$s_article_table} on (oxobject2article.oxobjectid={$s_article_table}.oxid " . "or oxobject2article.oxarticlenid={$s_article_table}.oxid) " . 'where (oxobject2article.oxarticlenid = ' . $o_db->quote($s_synch_sel_id) . ' or oxobject2article.oxobjectid = ' . $o_db->quote($s_synch_sel_id) . ' )';
            } else {
                $s_sub_select = "select {$s_article_table}.oxid from oxobject2article " . "left join {$s_article_table} on oxobject2article.oxobjectid={$s_article_table}.oxid " . 'where oxobject2article.oxarticlenid = ' . $o_db->quote($s_synch_sel_id) . ' ';
            }
            $s_sub_select .= " and {$s_article_table}.oxid IS NOT NULL ";
            $s_q_add .= " and {$s_article_table}.oxid not in ( {$s_sub_select} ) ";
        }
        // #1513C/#1826C - skip references, to not existing articles
        $s_q_add .= " and {$s_article_table}.oxid IS NOT NULL ";
        // skipping self from list
        $s_id = $s_synch_sel_id ?: $s_sel_id;
        return $s_q_add . (" and {$s_article_table}.oxid != " . $o_db->quote($s_id) . ' ');
    }
    /**
     * Removing article from corssselling list
     */
    public function remove_article_cross(): void
    {
        $a_chosen_art = $this->get_action_ids('oxobject2article.oxid');
        // removing all
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2article.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_chosen_articles = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_art));
            $s_q = 'delete from oxobject2article where oxobject2article.oxid in (' . $s_chosen_articles . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adding article to corssselling list
     */
    public function add_article_cross(): void
    {
        $a_chosen_art = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_art_table = $this->get_view_name('oxarticles');
            $a_chosen_art = $this->get_all(parent::add_filter("select {$s_art_table}.oxid " . $this->get_query()));
        }
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        if ($o_article->load($sox_id) && $sox_id && $sox_id != '-1' && is_array($a_chosen_art)) {
            foreach ($a_chosen_art as $s_add) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new_group->init('oxobject2article');
                $o_new_group->oxobject2article__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_add);
                $o_new_group->oxobject2article__oxarticlenid = new \Oxid_Esales\Eshop\Core\Field($o_article->oxarticles__oxid->value);
                $o_new_group->oxobject2article__oxsort = new \Oxid_Esales\Eshop\Core\Field(0);
                $o_new_group->save();
            }
            $this->on_article_adding_to_cross_selling($o_article);
        }
    }
    /**
     * Method is used to overload and add additional actions.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     */
    protected function on_article_adding_to_cross_selling($article)
    {
    }
}