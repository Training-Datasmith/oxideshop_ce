<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages vendor assignment to articles
 */
class Vendor_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
        // field , table,       visible, multilanguage, ident
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
        // looking for table/view
        $s_art_table = $this->get_view_name('oxarticles');
        $s_o2c_view = $this->get_view_name('oxobject2category');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_vendor_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_vendor_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // vendor selected or not ?
        if (!$s_vendor_id) {
            $s_q_add = ' from ' . $s_art_table . ' where ' . $s_art_table . '.oxshopid="' . $o_config->get_shop_id() . '" and 1 ';
            $s_q_add .= $o_config->get_config_param('blVariantsSelection') ? '' : " and {$s_art_table}.oxparentid = '' and {$s_art_table}.oxvendorid != " . $o_db->quote($s_synch_vendor_id);
        } else {
            // selected category ?
            if ($s_synch_vendor_id && $s_synch_vendor_id != $s_vendor_id) {
                $s_q_add = " from {$s_o2c_view} left join {$s_art_table} on ";
                $s_q_add .= $o_config->get_config_param('blVariantsSelection') ? " ( {$s_art_table}.oxid = {$s_o2c_view}.oxobjectid or {$s_art_table}.oxparentid = oxobject2category.oxobjectid )" : " {$s_art_table}.oxid = {$s_o2c_view}.oxobjectid ";
                $s_q_add .= 'where ' . $s_art_table . '.oxshopid="' . $o_config->get_shop_id() . '" and ' . $s_o2c_view . '.oxcatnid = ' . $o_db->quote($s_vendor_id) . ' and ' . $s_art_table . '.oxvendorid != ' . $o_db->quote($s_synch_vendor_id);
            } else {
                $s_q_add = " from {$s_art_table} where {$s_art_table}.oxvendorid = " . $o_db->quote($s_vendor_id);
            }
            $s_q_add .= $o_config->get_config_param('blVariantsSelection') ? '' : " and {$s_art_table}.oxparentid = '' ";
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
        $s_q .= \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blVariantsSelection') ? ' group by ' . $s_art_table . '.oxid ' : '';
        return $s_q;
    }
    /**
     * Removes article from Vendor
     */
    public function remove_vendor(): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $a_remove_art = $this->get_action_ids('oxarticles.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_art_table = $this->get_view_name('oxarticles');
            $a_remove_art = $this->get_all($this->add_filter("select {$s_art_table}.oxid " . $this->get_query()));
        }
        if (is_array($a_remove_art)) {
            $s_select = 'update oxarticles set oxvendorid = null where ' . $this->on_vendor_action_article_update_conditions($a_remove_art);
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_select);
            $this->reset_counter('vendorArticle', Registry::get_request()->get_request_escaped_parameter('oxid'));
            $this->on_vendor_action(Registry::get_request()->get_request_escaped_parameter('oxid'));
        }
    }
    /**
     * Adds article to Vendor config
     */
    public function add_vendor(): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $a_add_article = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_art_table = $this->get_view_name('oxarticles');
            $a_add_article = $this->get_all($this->add_filter("select {$s_art_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_add_article)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_select = 'update oxarticles set oxvendorid = ' . $o_db->quote($sox_id) . ' where ' . $this->on_vendor_action_article_update_conditions($a_add_article);
            $o_db->Execute($s_select);
            $this->reset_counter('vendorArticle', $sox_id);
            $this->on_vendor_action($sox_id);
        }
    }
    /**
     * Condition for updating oxarticles on add / remove vendor actions.
     *
     * @param array $articleIds
     *
     * @return string
     */
    protected function on_vendor_action_article_update_conditions($article_ids)
    {
        return 'oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($article_ids)) . ')';
    }
    /**
     * Additional actions on vendor add/remove.
     *
     * @param string $vendorOxid
     */
    protected function on_vendor_action($vendor_oxid)
    {
    }
}