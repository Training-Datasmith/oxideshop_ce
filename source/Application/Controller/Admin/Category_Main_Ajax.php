<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\After_Model_Update_Event;
/**
 * Class manages category articles
 */
class Category_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxarticles', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $s_article_table = $this->get_view_name('oxarticles');
        $s_o2c_view = $this->get_view_name('oxobject2category');
        $s_oxid = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_oxid = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // category selected or not ?
        if (!$s_oxid && $s_synch_oxid) {
            // dodger performance
            $s_q_add = ' from ' . $s_article_table . ' where 1 ';
        } else {
            // copied from oxadminview
            $s_join = " {$s_article_table}.oxid={$s_o2c_view}.oxobjectid ";
            $s_sub_select = '';
            if ($s_synch_oxid && $s_oxid != $s_synch_oxid) {
                $s_sub_select = ' and ' . $s_article_table . '.oxid not in ( ';
                $s_sub_select .= "select {$s_article_table}.oxid from {$s_o2c_view} left join {$s_article_table} ";
                $s_sub_select .= "on {$s_join} where {$s_o2c_view}.oxcatnid =  " . $o_db->quote($s_synch_oxid) . ' ';
                $s_sub_select .= 'and ' . $s_article_table . '.oxid is not null ) ';
            }
            $s_q_add = " from {$s_o2c_view} join {$s_article_table} ";
            $s_q_add .= " on {$s_join} where {$s_o2c_view}.oxcatnid = " . $o_db->quote($s_oxid);
            $s_q_add .= " and {$s_article_table}.oxid is not null {$s_sub_select} ";
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
        $s_art_table = $this->get_view_name('oxarticles');
        $s_q = parent::add_filter($s_q);
        // display variants or not ?
        if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blVariantsSelection')) {
            $s_q .= " and {$s_art_table}.oxparentid = '' ";
        }
        return $s_q;
    }
    /**
     * Adds article to category
     * Creates new list
     *
     * @throws Exception
     */
    public function add_article(): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $a_articles = $this->get_action_ids('oxarticles.oxid');
        $s_category_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $s_shop_id = $my_config->get_shop_id();
        \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->start_transaction();
        try {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_article_table = $this->get_view_name('oxarticles');
            // adding
            if (Registry::get_request()->get_request_escaped_parameter('all')) {
                $a_articles = $this->get_all($this->add_filter("select {$s_article_table}.oxid " . $this->get_query()));
            }
            if (is_array($a_articles)) {
                $s_o2c_view = $this->get_view_name('oxobject2category');
                $o_new = ox_new(\Oxid_Esales\Eshop\Application\Model\Object2Category::class);
                $s_prod_ids = '';
                foreach ($a_articles as $s_add) {
                    // check, if it's already in, then don't add it again
                    $s_select = sprintf('select 1 from %s as oxobject2category where oxobject2category.oxcatnid = :oxcatnid ' . ' and oxobject2category.oxobjectid = :oxobjectid', $s_o2c_view);
                    // We force reading from master to prevent issues with slow replications or open transactions
                    // (see ESDEV-3804).
                    if ($database->get_one($s_select, ['oxcatnid' => $s_category_id, 'oxobjectid' => $s_add])) {
                        continue;
                    }
                    $o_new->oxobject2category__oxid = new Field($o_new->set_id(md5($s_add . $s_category_id . $s_shop_id)));
                    $o_new->oxobject2category__oxobjectid = new Field($s_add);
                    $o_new->oxobject2category__oxcatnid = new Field($s_category_id);
                    $o_new->oxobject2category__oxtime = new Field(time());
                    $o_new->save();
                    if ($s_prod_ids) {
                        $s_prod_ids .= ',';
                    }
                    $s_prod_ids .= $database->quote($s_add);
                }
                // updating oxtime values
                $this->update_ox_time($s_prod_ids);
                $this->reset_art_seo_url($a_articles);
                $this->reset_counter('catArticle', $s_category_id);
            }
        } catch (Exception $exception) {
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->rollback_transaction();
            throw $exception;
        }
        \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->commit_transaction();
    }
    /**
     * Updates oxtime value for products
     *
     * @param string $sProdIds product ids: "id1", "id2", "id3"
     */
    protected function update_ox_time($s_prod_ids)
    {
        if ($s_prod_ids) {
            $s_o2c_view = $this->get_view_name('oxobject2category');
            $s_sql_shop_filter = $this->get_update_ox_time_query_shop_filter();
            $s_sql_where_shop_filter = $this->get_update_ox_time_sql_where_filter();
            $s_q = "update oxobject2category set oxtime = 0 where oxid in (\n                      select _tmp.oxid from (\n                          select oxobject2category.oxid from (\n                              select min(oxtime) as oxtime, oxobjectid from {$s_o2c_view}\n                              where oxobjectid in ( {$s_prod_ids} ) {$s_sql_shop_filter} group by oxobjectid\n                          ) as _subtmp\n                          left join oxobject2category on oxobject2category.oxtime = _subtmp.oxtime\n                           and oxobject2category.oxobjectid = _subtmp.oxobjectid\n                           {$s_sql_where_shop_filter}\n                      ) as _tmp\n                   ) {$s_sql_shop_filter}";
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->execute($s_q);
        }
    }
    /**
     * @return string
     */
    protected function get_update_ox_time_query_shop_filter()
    {
        return '';
    }
    /**
     * Return where with "true " as this allows to concat query condition
     * without knowing about other who changes this place (module or different edition).
     *
     * @return string
     */
    protected function get_update_ox_time_sql_where_filter()
    {
        return 'where true ';
    }
    /**
     * Removes article from category
     */
    public function remove_article(): void
    {
        $a_articles = $this->get_action_ids('oxarticles.oxid');
        $s_category_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_article_table = $this->get_view_name('oxarticles');
            $a_articles = $this->get_all($this->add_filter("select {$s_article_table}.oxid " . $this->get_query()));
        }
        // adding
        if (is_array($a_articles) && count($a_articles)) {
            $this->remove_category_articles($a_articles, $s_category_id);
        }
        $this->reset_art_seo_url($a_articles, $s_category_id);
        $this->reset_counter('catArticle', $s_category_id);
        //notify services
        $relation = ox_new(\Oxid_Esales\Eshop\Application\Model\Object2Category::class);
        $relation->set_category_id($s_category_id);
        Container_Facade::dispatch(new After_Model_Update_Event($relation));
    }
    /**
     * Delete articles from category (from oxobject2category).
     *
     * @param array  $articles
     * @param string $categoryID
     */
    protected function remove_category_articles($articles, $category_id)
    {
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $prod_ids = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($articles));
        $delete = 'delete from oxobject2category ';
        $where = $this->get_remove_category_articles_query_filter($category_id, $prod_ids);
        $s_q = $delete . $where;
        $db->execute($s_q);
        // updating oxtime values
        $this->update_ox_time($prod_ids);
    }
    /**
     * Form query filter to remove articles from category.
     *
     * @param string $categoryID
     * @param string $prodIds
     *
     * @return string
     */
    protected function get_remove_category_articles_query_filter($category_id, $prod_ids)
    {
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $where = 'where oxcatnid=' . $db->quote($category_id);
        $where_product_id_in = " oxobjectid in ( {$prod_ids} )";
        if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blVariantsSelection')) {
            $where_product_id_in = '( ' . $where_product_id_in . " OR oxobjectid in (\n                                        select oxid from oxarticles where oxparentid in ({$prod_ids})\n                                        )\n            )";
        }
        return $where . ' AND ' . $where_product_id_in;
    }
}