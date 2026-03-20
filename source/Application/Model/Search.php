<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Implements search
 */
#[\Allow_Dynamic_Properties]
class Search extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Active language id
     *
     * @var int
     */
    protected $_i_language = 0;
    /**
     * Class constructor. Executes search lenguage setter
     */
    public function __construct()
    {
        $this->set_language();
    }
    /**
     * Search language setter. If no param is passed, will be taken default shop language
     *
     * @param string $iLanguage string (default null)
     */
    public function set_language($i_language = null): void
    {
        if (!isset($i_language)) {
            $this->_i_language = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        } else {
            $this->_i_language = $i_language;
        }
    }
    /**
     * Returns a list of articles according to search parameters. Returns matched
     *
     * @param string|false $sSearchParamForQuery       query parameter
     * @param string|false $sInitialSearchCat          initial category to seearch in
     * @param string|false $sInitialSearchVendor       initial vendor to seearch for
     * @param string|false $sInitialSearchManufacturer initial Manufacturer to seearch for
     * @param string|false $sSortBy                    sort by
     *
     * @return ArticleList
     */
    public function get_search_articles($s_search_param_for_query = false, $s_initial_search_cat = false, $s_initial_search_vendor = false, $s_initial_search_manufacturer = false, $s_sort_by = false)
    {
        // sets active page
        $this->i_act_page = (int) Registry::get_request()->get_request_escaped_parameter('pgNr');
        $this->i_act_page = $this->i_act_page < 0 ? 0 : $this->i_act_page;
        // load only articles which we show on screen
        //setting default values to avoid possible errors showing article list
        $i_nrof_cat_articles = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
        $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
        $o_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_art_list->set_sql_limit($i_nrof_cat_articles * $this->i_act_page, $i_nrof_cat_articles);
        $s_select = $this->get_search_select($s_search_param_for_query, $s_initial_search_cat, $s_initial_search_vendor, $s_initial_search_manufacturer, $s_sort_by);
        if ($s_select) {
            $o_art_list->select_string($s_select);
        }
        return $o_art_list;
    }
    /**
     * Returns the amount of articles according to search parameters.
     *
     * @param string|bool $sSearchParamForQuery       query parameter
     * @param string|bool $sInitialSearchCat          initial category to seearch in
     * @param string|bool $sInitialSearchVendor       initial vendor to seearch for
     * @param string|bool $sInitialSearchManufacturer initial Manufacturer to seearch for
     *
     * @return int
     */
    public function get_search_article_count($s_search_param_for_query = false, $s_initial_search_cat = false, $s_initial_search_vendor = false, $s_initial_search_manufacturer = false)
    {
        $i_cnt = 0;
        $s_select = $this->get_search_select($s_search_param_for_query, $s_initial_search_cat, $s_initial_search_vendor, $s_initial_search_manufacturer, false);
        if ($s_select) {
            $s_partial = substr($s_select, strpos($s_select, ' from '));
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_select = 'select count( ' . $table_view_name_generator->get_view_name('oxarticles', $this->_i_language) . ".oxid ) {$s_partial} ";
            $i_cnt = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_select);
        }
        return $i_cnt;
    }
    /**
     * Returns the appropriate SQL select for a search according to search parameters
     *
     * @param string|false $sSearchParamForQuery       query parameter
     * @param string|false $sInitialSearchCat          initial category to search in
     * @param string|false $sInitialSearchVendor       initial vendor to search for
     * @param string|false $sInitialSearchManufacturer initial Manufacturer to search for
     * @param string|false $sSortBy                    sort by
     *
     * @return string
     */
    protected function get_search_select($s_search_param_for_query = false, $s_initial_search_cat = false, $s_initial_search_vendor = false, $s_initial_search_manufacturer = false, $s_sort_by = false)
    {
        if (!$s_search_param_for_query && !$s_initial_search_cat && !$s_initial_search_vendor && !$s_initial_search_manufacturer) {
            //no search string
            return null;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // performance
        if ($s_initial_search_cat) {
            // lets search this category - is no such category - skip all other code
            $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            $s_cat_table = $o_category->get_view_name();
            $s_q = "select 1 from {$s_cat_table} \n                where {$s_cat_table}.oxid = :oxid ";
            $s_q .= 'and ' . $o_category->get_sql_active_snippet();
            $params = ['oxid' => $s_initial_search_cat];
            if (!$o_db->get_one($s_q, $params)) {
                return;
            }
        }
        // performance:
        if ($s_initial_search_vendor) {
            // lets search this vendor - if no such vendor - skip all other code
            $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
            $s_vnd_table = $o_vendor->get_view_name();
            $s_q = "select 1 from {$s_vnd_table} \n                where {$s_vnd_table}.oxid = :oxid ";
            $s_q .= 'and ' . $o_vendor->get_sql_active_snippet();
            $params = ['oxid' => $s_initial_search_vendor];
            if (!$o_db->get_one($s_q, $params)) {
                return;
            }
        }
        // performance:
        if ($s_initial_search_manufacturer) {
            // lets search this Manufacturer - if no such Manufacturer - skip all other code
            $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
            $s_man_table = $o_manufacturer->get_view_name();
            $s_q = "select 1 from {$s_man_table} \n                where {$s_man_table}.oxid = :oxid ";
            $s_q .= 'and ' . $o_manufacturer->get_sql_active_snippet();
            $params = ['oxid' => $s_initial_search_manufacturer];
            if (!$o_db->get_one($s_q, $params)) {
                return;
            }
        }
        $s_where = null;
        if ($s_search_param_for_query) {
            $s_where = $this->get_where($s_search_param_for_query);
        }
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $o_article->get_view_name();
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        $s_select_fields = $o_article->get_select_fields();
        // longdesc field now is kept on different table
        $s_desc_join = $this->get_description_join($s_article_table);
        //select articles
        $s_select = "select {$s_select_fields}, {$s_article_table}.oxtimestamp from {$s_article_table} {$s_desc_join} where ";
        // must be additional conditions in select if searching in category
        if ($s_initial_search_cat) {
            $s_cat_view = $table_view_name_generator->get_view_name('oxcategories', $this->_i_language);
            $s_initial_search_cat_quoted = $o_db->quote($s_initial_search_cat);
            $s_select_cat = "select oxid from {$s_cat_view} where oxid = {$s_initial_search_cat_quoted} and (oxpricefrom != '0' or oxpriceto != 0)";
            if ($o_db->get_one($s_select_cat)) {
                $s_select = "select {$s_select_fields}, {$s_article_table}.oxtimestamp from {$s_article_table} {$s_desc_join} " . "where {$s_article_table}.oxid in ( select {$s_article_table}.oxid as id from {$s_article_table}, {$s_o2c_view} as oxobject2category, {$s_cat_view} as oxcategories " . "where (oxobject2category.oxcatnid={$s_initial_search_cat_quoted} and oxobject2category.oxobjectid={$s_article_table}.oxid) or (oxcategories.oxid={$s_initial_search_cat_quoted} and {$s_article_table}.oxprice >= oxcategories.oxpricefrom and\n                            {$s_article_table}.oxprice <= oxcategories.oxpriceto )) and ";
            } else {
                $s_select = "select {$s_select_fields} from {$s_o2c_view} as\n                            oxobject2category, {$s_article_table} {$s_desc_join} where oxobject2category.oxcatnid={$s_initial_search_cat_quoted} and\n                            oxobject2category.oxobjectid={$s_article_table}.oxid and ";
            }
        }
        $s_select .= $o_article->get_sql_active_snippet();
        $s_select .= " and {$s_article_table}.oxparentid = '' and {$s_article_table}.oxissearch = 1 ";
        if ($s_initial_search_vendor) {
            $s_select .= " and {$s_article_table}.oxvendorid = " . $o_db->quote($s_initial_search_vendor) . ' ';
        }
        if ($s_initial_search_manufacturer) {
            $s_select .= " and {$s_article_table}.oxmanufacturerid = " . $o_db->quote($s_initial_search_manufacturer) . ' ';
        }
        $s_select .= $s_where;
        if ($s_sort_by) {
            $s_select .= " order by {$s_sort_by} ";
        }
        return $s_select;
    }
    /**
     * Forms and returns SQL query string for search in DB.
     *
     * @param string $sSearchString searching string
     *
     * @return string
     */
    protected function get_where($s_search_string)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $bl_sep = false;
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles', $this->_i_language);
        $a_search_cols = $my_config->get_config_param('aSearchCols');
        if (!(is_array($a_search_cols) && count($a_search_cols))) {
            return '';
        }
        $s_search_sep = $my_config->get_config_param('blSearchUseAND') ? 'and ' : 'or ';
        $a_search = explode(' ', $s_search_string);
        $s_search = ' and ( ';
        $my_utils_string = \Oxid_Esales\Eshop\Core\Registry::get_utils_string();
        foreach ($a_search as $s_search_string) {
            if (!strlen($s_search_string)) {
                continue;
            }
            if ($bl_sep) {
                $s_search .= $s_search_sep;
            }
            $bl_sep2 = false;
            $s_search .= '( ';
            foreach ($a_search_cols as $s_field) {
                if ($bl_sep2) {
                    $s_search .= ' or ';
                }
                // as long description now is on different table table must differ
                $s_search_field = $this->get_search_field($s_article_table, $s_field);
                $s_search .= " {$s_search_field} like " . $o_db->quote("%{$s_search_string}%");
                // special chars ?
                if ($s_uml = $my_utils_string->prepare_str_for_search($s_search_string)) {
                    $s_search .= " or {$s_search_field} like " . $o_db->quote("%{$s_uml}%");
                }
                $bl_sep2 = true;
            }
            $s_search .= ' ) ';
            $bl_sep = true;
        }
        return $s_search . ' ) ';
    }
    /**
     * Get description join. Needed in case of searching for data in table oxartextends or its views.
     *
     * @param string $table
     *
     * @return string
     */
    protected function get_description_join($table)
    {
        $description_join = '';
        $search_columns = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aSearchCols');
        if (is_array($search_columns) && in_array('oxlongdesc', $search_columns)) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $view_name = $table_view_name_generator->get_view_name('oxartextends', $this->_i_language);
            $description_join = " LEFT JOIN {$view_name} ON {$table}.oxid={$view_name}.oxid ";
        }
        return $description_join;
    }
    /**
     * Get search field name.
     * Needed in case of searching for data in table oxartextends or its views.
     *
     * @param string $table
     * @param string $field Chose table depending on field.
     *
     * @return string
     */
    protected function get_search_field($table, $field)
    {
        if ($field == 'oxlongdesc') {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            return $table_view_name_generator->get_view_name('oxartextends', $this->_i_language) . ".{$field}";
        }
        return "{$table}.{$field}";
    }
}