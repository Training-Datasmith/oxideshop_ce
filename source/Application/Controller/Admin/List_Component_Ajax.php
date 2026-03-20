<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\After_Admin_Ajax_Request_Processed_Event;
/**
 * AJAX call processor class
 */
class List_Component_Ajax extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Possible sort keys
     *
     * @var array
     */
    protected $_a_pos_dir = ['asc', 'desc'];
    /**
     * Array of DB table columns which are loaded from DB
     *
     * @var array
     */
    protected $_a_columns = [];
    /**
     * Default limit of DB entries to load from DB
     *
     * @var int
     */
    protected $_i_sql_limit = 2500;
    /**
     * Ajax container name
     *
     * @var string
     */
    protected $_s_container;
    /**
     * If true extended column selection will be build
     * (currently checks if variants must be shown in lists and column name is "oxtitle")
     *
     * @var bool
     */
    protected $_bl_allow_ext_columns = false;
    /**
     * Gets columns array.
     *
     * @return array
     */
    public function get_columns()
    {
        return $this->_a_columns;
    }
    /**
     * Sets columns array.
     *
     * @param array $aColumns columns array
     */
    public function set_columns($a_columns): void
    {
        $this->_a_columns = $a_columns;
    }
    /**
     * Required data fields are returned by indexes/position in _aColumns array. This method
     * translates "table_name.col_name" into index definition and fetches request data according
     * to it. This is usefull while using AJAX across versions.
     *
     * @param string $sId "table_name.col_name"
     *
     * @return array
     */
    protected function get_action_ids($s_id)
    {
        $a_columns = $this->get_col_names();
        foreach ($a_columns as $i_pos => $a_col) {
            if (isset($a_col[4]) && $a_col[4] == 1 && $s_id == $a_col[1] . '.' . $a_col[0]) {
                return Registry::get_request()->get_request_escaped_parameter('_' . $i_pos);
            }
        }
    }
    /**
     * AJAX container name setter
     *
     * @param string $sName name of container
     */
    public function set_name($s_name): void
    {
        $this->_s_container = $s_name;
    }
    /**
     * Empty function, developer should override this method according requirements
     *
     * @return string
     */
    protected function get_query()
    {
        return '';
    }
    /**
     * Return fully formatted query for data loading
     *
     * @param string $sQ part of initial query
     *
     * @return string
     */
    protected function get_data_query($s_q)
    {
        return 'select ' . $this->get_query_cols() . $s_q;
    }
    /**
     * Return fully formatted query for data records count
     *
     * @param string $sQ part of initial query
     *
     * @return string
     */
    protected function get_count_query($s_q)
    {
        return 'select count( * ) ' . $s_q;
    }
    /**
     * AJAX call processor function
     *
     * @param string $function name of action to execute (optional)
     */
    public function process_request($function = null): void
    {
        if ($function) {
            $this->{$function}();
            Container_Facade::dispatch(new After_Admin_Ajax_Request_Processed_Event());
        } else {
            $s_q_add = $this->get_query();
            // formatting SQL queries
            $s_q = $this->get_data_query($s_q_add);
            $s_count_q = $this->get_count_query($s_q_add);
            $this->output_response($this->get_data($s_count_q, $s_q));
        }
    }
    /**
     * Returns column id to sort
     *
     * @return int
     */
    protected function get_sort_col()
    {
        $a_visible_names = $this->get_visible_col_names();
        $i_col = Registry::get_request()->get_request_escaped_parameter('sort');
        $i_col = $i_col ? (int) str_replace('_', '', $i_col) : 0;
        return !isset($a_visible_names[$i_col]) ? 0 : $i_col;
    }
    /**
     * Returns array of cotainer DB cols which must be loaded. If id is not
     * passed - all possible containers cols will be returned
     *
     * @param string $sId container id (optional)
     *
     * @return array
     */
    protected function get_col_names($s_id = null)
    {
        if ($s_id === null) {
            $s_id = Registry::get_request()->get_request_escaped_parameter('cmpid');
        }
        if ($s_id && isset($this->_a_columns[$s_id])) {
            return $this->_a_columns[$s_id];
        }
        return $this->_a_columns;
    }
    /**
     * Returns array of identifiers which are used as identifiers for specific actions
     * in AJAX and further in this processor class
     *
     * @return array
     */
    protected function get_ident_col_names()
    {
        $a_col_names = $this->get_col_names();
        $a_cols = [];
        foreach ($a_col_names as $i_key => $a_col) {
            // ident ?
            if ($a_col[4]) {
                $a_cols[$i_key] = $a_col;
            }
        }
        return $a_cols;
    }
    /**
     * Returns array of col names which are requested by AJAX call and will be fetched from DB
     *
     * @return array
     */
    protected function get_visible_col_names()
    {
        $a_col_names = $this->get_col_names();
        $a_user_cols = Registry::get_request()->get_request_escaped_parameter('aCols');
        $a_visible_cols = [];
        // user defined some cols to load ?
        if (is_array($a_user_cols)) {
            foreach ($a_user_cols as $s_col) {
                $i_col = (int) str_replace('_', '', $s_col);
                if (isset($a_col_names[$i_col]) && !$a_col_names[$i_col][4]) {
                    $a_visible_cols[$i_col] = $a_col_names[$i_col];
                }
            }
        }
        // no user defined valid cols ? setting defauls ..
        if (!count($a_visible_cols)) {
            foreach ($a_col_names as $s_name => $a_col) {
                // visible ?
                if ($a_col[1] && !$a_col_names[$s_name][4]) {
                    $a_visible_cols[$s_name] = $a_col;
                }
            }
        }
        return $a_visible_cols;
    }
    /**
     * Formats and returns chunk of SQL query string with definition of
     * fields to load from DB
     *
     * @return string
     */
    protected function get_query_cols()
    {
        $s_q = $this->build_cols_query($this->get_visible_col_names(), false) . ', ';
        $s_q .= $this->build_cols_query($this->get_ident_col_names());
        return " {$s_q} ";
    }
    /**
     * Builds column selection query
     *
     * @param array $aIdentCols  columns
     * @param bool  $blIdentCols if true, means ident columns part is build
     *
     * @return string
     */
    protected function build_cols_query($a_ident_cols, $bl_ident_cols = true)
    {
        $s_q = '';
        foreach ($a_ident_cols as $i_cnt => $a_col) {
            if ($s_q) {
                $s_q .= ', ';
            }
            $s_view_table = $this->get_view_name($a_col[1]);
            if (!$bl_ident_cols && $this->is_extended_column($a_col[0])) {
                $s_q .= $this->get_extended_col_query($s_view_table, $a_col[0], $i_cnt);
            } else {
                $s_q .= $s_view_table . '.' . $a_col[0] . ' as _' . $i_cnt;
            }
        }
        return $s_q;
    }
    /**
     * Checks if current column is extended
     * (currently checks if variants must be shown in lists and column name is "oxtitle")
     *
     * @param string $sColumn column name
     *
     * @return bool
     */
    protected function is_extended_column($s_column)
    {
        $bl_variants_selection_parameter = Registry::get_config()->get_config_param('blVariantsSelection');
        return $this->_bl_allow_ext_columns && $bl_variants_selection_parameter && $s_column == 'oxtitle';
    }
    /**
     * Returns extended query part for given view/column combination
     * (if variants must be shown in lists and column name is "oxtitle")
     *
     * @param string $sViewTable view name
     * @param string $sColumn    column name
     * @param int    $iCnt       column count
     *
     * @return string
     */
    protected function get_extended_col_query($s_view_table, $s_column, $i_cnt)
    {
        // multilanguage
        $s_var_select = "{$s_view_table}.oxvarselect";
        return " IF( {$s_view_table}.{$s_column} != '', {$s_view_table}.{$s_column}, CONCAT((select oxart.{$s_column} " . "from {$s_view_table} as oxart " . "where oxart.oxid = {$s_view_table}.oxparentid),', ',{$s_var_select})) as _{$i_cnt}";
    }
    /**
     * Formats and returns part of SQL query for sorting
     *
     * @return string
     */
    protected function get_sorting()
    {
        return ' order by _' . $this->get_sort_col() . ' ' . $this->get_sort_dir() . ' ';
    }
    /**
     * Returns part of SQL query for limiting number of entries from DB
     *
     * @param int $iStart start position
     *
     * @return string
     */
    protected function get_limit($i_start)
    {
        $i_limit = (int) Registry::get_request()->get_request_escaped_parameter('results');
        $i_limit = $i_limit ?: $this->_i_sql_limit;
        return " limit {$i_start}, {$i_limit} ";
    }
    /**
     * Returns part of SQL query for filtering DB data
     *
     * @return string
     */
    protected function get_filter()
    {
        $s_q = '';
        $a_filter = Registry::get_request()->get_request_escaped_parameter('aFilter');
        if (is_array($a_filter) && count($a_filter)) {
            $a_cols = $this->get_visible_col_names();
            $o_db = Database_Provider::get_db();
            $o_str = Str::get_str();
            foreach ($a_filter as $s_col => $s_value) {
                // skipping empty filters
                if ($s_value === '') {
                    continue;
                }
                $i_col = (int) str_replace('_', '', $s_col);
                if (isset($a_cols[$i_col])) {
                    if ($s_q) {
                        $s_q .= ' and ';
                    }
                    // escaping special characters
                    $s_value = str_replace(['%', '_'], ['\%', '\_'], $s_value);
                    // possibility to search in the middle ..
                    $s_value = $o_str->preg_replace('/^\*/', '%', $s_value);
                    $s_q .= $this->get_view_name($a_cols[$i_col][1]) . '.' . $a_cols[$i_col][0];
                    $s_q .= ' like ' . $o_db->Quote('%' . $s_value . '%') . ' ';
                }
            }
        }
        return $s_q;
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
        if ($s_q && $s_filter = $this->get_filter()) {
            $s_q .= (stristr($s_q, 'where') === false ? 'where' : ' and ') . $s_filter;
        }
        return $s_q;
    }
    /**
     * Returns DB records as plain indexed array
     *
     * @param string $sQ SQL query
     *
     * @return array
     */
    protected function get_all($s_q)
    {
        return Database_Provider::get_db()->get_col($s_q);
    }
    /**
     * Checks user input and returns SQL sorting direction key
     *
     * @return string
     */
    protected function get_sort_dir()
    {
        $s_dir = Registry::get_request()->get_request_escaped_parameter('dir');
        if (!in_array($s_dir, $this->_a_pos_dir)) {
            return $this->_a_pos_dir[0];
        }
        return $s_dir;
    }
    /**
     * Returns position from where data must be loaded
     *
     * @return int
     */
    protected function get_start_index()
    {
        return (int) Registry::get_request()->get_request_escaped_parameter('startIndex');
    }
    /**
     * Returns amount of records which can be found according to passed SQL query
     *
     * @param string $sQ SQL query
     *
     * @return int
     */
    protected function get_total_count($s_q)
    {
        // TODO: implement caching here
        // we can cache total count ...
        // $sCountCacheKey = md5( $sQ );
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (int) Database_Provider::get_master()->get_one($s_q);
    }
    /**
     * Returns array with DB records
     *
     * @param string $sQ SQL query
     *
     * @return array
     */
    protected function get_data_fields($s_q)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return Database_Provider::get_master()->get_all($s_q);
    }
    /**
     * Outputs JSON encoded data
     *
     * @param array $aData data to output
     */
    protected function output_response($a_data)
    {
        $this->output(json_encode($a_data));
    }
    /**
     * Echoes given string
     *
     * @param string $sOut string to echo
     */
    protected function output($s_out)
    {
        echo $s_out;
    }
    /**
     * Return the view name of the given table if a view exists, otherwise the table name itself
     *
     * @param string $sTable table name
     *
     * @return string
     */
    protected function get_view_name($s_table)
    {
        return ox_new(Table_View_Name_Generator::class)->get_view_name($s_table, Registry::get_request()->get_request_escaped_parameter('editlanguage'));
    }
    /**
     * Formats data array which later will be processed by _outputResponse method
     *
     * @param string $sCountQ count query
     * @param string $sQ      data load query
     *
     * @return array
     */
    protected function get_data($s_count_q, $s_q)
    {
        $s_q = $this->add_filter($s_q);
        $s_count_q = $this->add_filter($s_count_q);
        $a_response['startIndex'] = $i_start = $this->get_start_index();
        $a_response['sort'] = '_' . $this->get_sort_col();
        $a_response['dir'] = $this->get_sort_dir();
        $debug = Container_Facade::get_parameter('oxid_esales.debug_mode');
        if ($debug) {
            $a_response['countsql'] = $s_count_q;
        }
        $a_response['records'] = [];
        // skip further execution if no records were found ...
        if ($i_total = $this->get_total_count($s_count_q)) {
            $s_q .= $this->get_sorting();
            $s_q .= $this->get_limit($i_start);
            if ($debug) {
                $a_response['datasql'] = $s_q;
            }
            $a_response['records'] = $this->get_data_fields($s_q);
        }
        $a_response['totalRecords'] = $i_total;
        return $a_response;
    }
    /**
     * Marks article seo url as expired
     *
     * @param array $aArtIds article id's
     * @param array $aCatIds ids if categories, which must be removed from oxseo
     */
    public function reset_art_seo_url($a_art_ids, $a_cat_ids = null): void
    {
        if (empty($a_art_ids)) {
            return;
        }
        if (!is_array($a_art_ids)) {
            $a_art_ids = [$a_art_ids];
        }
        $s_shop_id = Registry::get_config()->get_shop_id();
        foreach ($a_art_ids as $s_art_id) {
            Registry::get_seo_encoder()->mark_as_expired($s_art_id, $s_shop_id, 1, null, "oxtype='oxarticle'");
        }
    }
    /**
     * Reset output cache
     */
    public function reset_content_cache(): void
    {
        $bl_delete_cache_on_logout = Registry::get_config()->get_config_param('blClearCacheOnLogout');
        if (!$bl_delete_cache_on_logout) {
            $this->reset_caches();
            Registry::get_utils()->ox_reset_file_cache();
        }
    }
    /**
     * Resets counters values from cache. Resets price category articles, category articles,
     * vendor articles, manufacturer articles count.
     *
     * @param string $sCounterType counter type
     * @param string $sValue       reset value
     */
    public function reset_counter($s_counter_type, $s_value = null): void
    {
        $bl_delete_cache_on_logout = Registry::get_config()->get_config_param('blClearCacheOnLogout');
        if (!$bl_delete_cache_on_logout) {
            $my_utils_count = Registry::get_utils_count();
            switch ($s_counter_type) {
                case 'priceCatArticle':
                    $my_utils_count->reset_price_cat_article_count($s_value);
                    break;
                case 'catArticle':
                    $my_utils_count->reset_cat_article_count($s_value);
                    break;
                case 'vendorArticle':
                    $my_utils_count->reset_vendor_article_count($s_value);
                    break;
                case 'manufacturerArticle':
                    $my_utils_count->reset_manufacturer_article_count($s_value);
                    break;
            }
            $this->reset_content_cache();
        }
    }
    /**
     * Resets output caches
     */
    protected function reset_caches()
    {
    }
}