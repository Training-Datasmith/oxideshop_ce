<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class controls article assignment to attributes
 */
class Actions_Article_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = Registry::get_config();
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_article_table = $this->get_view_name('oxarticles');
        $s_view_name = $this->get_view_name('oxobject2category');
        $s_sel_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_sel_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_sel_id) {
            $s_q_add = " from {$s_article_table} where 1 ";
            $s_q_add .= $my_config->get_config_param('blVariantsSelection') ? '' : " and {$s_article_table}.oxparentid = '' ";
        } else if ($s_synch_sel_id) {
            $bl_variants_selection_parameter = $my_config->get_config_param('blVariantsSelection');
            $s_sql_if_true = " ({$s_article_table}.oxid=oxobject2category.oxobjectid " . "or {$s_article_table}.oxparentid=oxobject2category.oxobjectid)";
            $s_sql_if_false = " {$s_article_table}.oxid=oxobject2category.oxobjectid ";
            $s_variant_selection = $bl_variants_selection_parameter ? $s_sql_if_true : $s_sql_if_false;
            $s_q_add = " from {$s_view_name} as oxobject2category left join {$s_article_table} on " . $s_variant_selection . ' where oxobject2category.oxcatnid = ' . $o_db->quote($s_sel_id) . ' ';
        }
        // #1513C/#1826C - skip references, to not existing articles
        $s_q_add .= " and {$s_article_table}.oxid IS NOT NULL ";
        // skipping self from list
        $s_q_add .= " and {$s_article_table}.oxid != " . $o_db->quote($s_synch_sel_id) . ' ';
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
        $s_q .= Registry::get_config()->get_config_param('blVariantsSelection') ? ' group by ' . $s_art_table . '.oxid ' : '';
        return $s_q;
    }
    /**
     * Removing article assignment
     */
    public function remove_action_article(): void
    {
        $s_action_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        //$sActionId = $this->getConfig()->getConfigParam( 'oxid' );
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_db->Execute('delete from oxobject2action ' . 'where oxactionid = :oxactionid' . ' and oxclass = "oxarticle"', ['oxactionid' => $s_action_id]);
    }
    /**
     * Set article assignment
     */
    public function set_action_article(): void
    {
        $s_article_id = Registry::get_request()->get_request_escaped_parameter('oxarticleid');
        $s_action_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_db->Execute('delete from oxobject2action ' . 'where oxactionid = :oxactionid' . ' and oxclass = "oxarticle"', ['oxactionid' => $s_action_id]);
        $o_object2promotion = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
        $o_object2promotion->init('oxobject2action');
        $o_object2promotion->oxobject2action__oxactionid = new \Oxid_Esales\Eshop\Core\Field($s_action_id);
        $o_object2promotion->oxobject2action__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_article_id);
        $o_object2promotion->oxobject2action__oxclass = new \Oxid_Esales\Eshop\Core\Field('oxarticle');
        $o_object2promotion->save();
    }
}