<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Ox_Admin_List;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * user list "view" class.
 */
class List_Review extends \Oxid_Esales\Eshop\Application\Controller\Admin\Article_List
{
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_s_list_type = 'oxlist';
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxreview';
    /**
     * Viewable list size getter
     *
     * @return int
     */
    public function get_view_list_size()
    {
        return $this->get_user_def_list_size();
    }
    /** @inheritdoc */
    public function render()
    {
        Ox_Admin_List::render();
        $this->_a_view_data['menustructure'] = $this->get_navigation()->get_dom_xml()->document_element->child_nodes;
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $this->_a_view_data['articleListTable'] = $table_view_name_generator->get_view_name('oxarticles');
        return 'list_review';
    }
    /**
     * Returns select query string
     *
     * @param object $oObject list item object
     *
     * @return string
     */
    protected function build_select_string($o_object = null)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_art_table = $table_view_name_generator->get_view_name('oxarticles', $this->_i_edit_lang);
        $s_q = "select oxreviews.oxid, oxreviews.oxcreate, oxreviews.oxtext, oxreviews.oxobjectid, {$s_art_table}.oxparentid, {$s_art_table}.oxtitle as oxtitle, {$s_art_table}.oxvarselect as oxvarselect, oxparentarticles.oxtitle as parenttitle, ";
        $s_q .= "concat( {$s_art_table}.oxtitle, if(isnull(oxparentarticles.oxtitle), '', oxparentarticles.oxtitle), {$s_art_table}.oxvarselect) as arttitle from oxreviews ";
        $s_q .= "left join {$s_art_table} as {$s_art_table} on {$s_art_table}.oxid=oxreviews.oxobjectid and 'oxarticle' = oxreviews.oxtype ";
        $s_q .= "left join {$s_art_table} as oxparentarticles on oxparentarticles.oxid = {$s_art_table}.oxparentid ";
        $s_q .= "where 1 and oxreviews.oxlang = '{$this->_i_edit_lang}' ";
        //removing parent id checking from sql
        $s_str = "/\\s+and\\s+" . $s_art_table . "\\.oxparentid\\s*=\\s*''/";
        $s_q = Str::get_str()->preg_replace($s_str, ' ', $s_q);
        return " {$s_q} and {$s_art_table}.oxid is not null ";
    }
    /**
     * Adds filtering conditions to query string
     *
     * @param array  $aWhere filter conditions
     * @param string $sSql   query string
     *
     * @return string
     */
    protected function prepare_where_query($a_where, $s_sql)
    {
        $s_sql = parent::prepare_where_query($a_where, $s_sql);
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_art_table = $table_view_name_generator->get_view_name('oxarticles', $this->_i_edit_lang);
        $s_art_title_field = "{$s_art_table}.oxtitle";
        // if searching in article title field, updating sql for this case
        if (isset($this->_a_where[$s_art_title_field]) && $this->_a_where[$s_art_title_field]) {
            $s_sql_for_title = " (CONCAT( {$s_art_table}.oxtitle, if(isnull(oxparentarticles.oxtitle), '', oxparentarticles.oxtitle), {$s_art_table}.oxvarselect)) ";
            $s_sql = Str::get_str()->preg_replace("/{$s_art_table}\\.oxtitle\\s+like/", "{$s_sql_for_title} like", $s_sql);
        }
        return $s_sql;
    }
}