<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
/**
 * Class manages article attributes
 */
class Attribute_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxobject2attribute', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_article_table = $this->get_view_name('oxarticles');
        $s_o_cat_view = $this->get_view_name('oxobject2category');
        $s_o_attr_view = $this->get_view_name('oxobject2attribute');
        $s_del_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_del_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_del_id) {
            // performance
            $s_q_add = " from {$s_article_table} where 1 ";
            $s_q_add .= $my_config->get_config_param('blVariantsSelection') ? '' : " and {$s_article_table}.oxparentid = '' ";
        } elseif ($s_synch_del_id && $s_del_id != $s_synch_del_id) {
            // selected category ?
            $bl_variants_selection_parameter = $my_config->get_config_param('blVariantsSelection');
            $s_sql_if_true = " ( {$s_article_table}.oxid=oxobject2category.oxobjectid " . "or {$s_article_table}.oxparentid=oxobject2category.oxobjectid)";
            $s_sql_if_false = " {$s_article_table}.oxid=oxobject2category.oxobjectid ";
            $s_variant_selection_sql = $bl_variants_selection_parameter ? $s_sql_if_true : $s_sql_if_false;
            $s_q_add = " from {$s_o_cat_view} as oxobject2category left join {$s_article_table} on {$s_variant_selection_sql}" . ' where oxobject2category.oxcatnid = ' . $o_db->quote($s_del_id) . ' ';
        } else {
            $s_q_add = " from {$s_o_attr_view} left join {$s_article_table} " . "on {$s_article_table}.oxid={$s_o_attr_view}.oxobjectid " . "where {$s_o_attr_view}.oxattrid = " . $o_db->quote($s_del_id) . " and {$s_article_table}.oxid is not null ";
        }
        if ($s_synch_del_id && $s_synch_del_id != $s_del_id) {
            $s_q_add .= " and {$s_article_table}.oxid not in ( select {$s_o_attr_view}.oxobjectid from {$s_o_attr_view} " . "where {$s_o_attr_view}.oxattrid = " . $o_db->quote($s_synch_del_id) . ' ) ';
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
     * Removes article from Attribute list
     */
    public function remove_attr_article(): void
    {
        $a_chosen_cat = $this->get_action_ids('oxobject2attribute.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_o2attribute_view = $this->get_view_name('oxobject2attribute');
            $s_q = parent::add_filter("delete {$s_o2attribute_view}.* " . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_cat)) {
            $s_chosen_categories = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_cat));
            $s_q = 'delete from oxobject2attribute where oxobject2attribute.oxid in (' . $s_chosen_categories . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds article to Attribute list
     */
    public function add_attr_article(): void
    {
        $a_add_article = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_article_table = $this->get_view_name('oxarticles');
            $a_add_article = $this->get_all($this->add_filter("select {$s_article_table}.oxid " . $this->get_query()));
        }
        $o_attribute = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
        if ($o_attribute->load($sox_id) && is_array($a_add_article)) {
            foreach ($a_add_article as $s_add) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new_group->init('oxobject2attribute');
                $o_new_group->oxobject2attribute__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_add);
                $o_new_group->oxobject2attribute__oxattrid = new \Oxid_Esales\Eshop\Core\Field($o_attribute->oxattribute__oxid->value);
                $o_new_group->save();
                $this->on_article_add_to_attribute_list($s_add);
            }
        }
    }
    /**
     * Method used to overload.
     *
     * @param string $articleId
     */
    protected function on_article_add_to_attribute_list($article_id)
    {
    }
}