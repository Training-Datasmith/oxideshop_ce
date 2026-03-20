<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use stdClass;
/**
 * Admin selectlist list manager.
 */
class Admin_List_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class;
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_s_list_type = 'oxlist';
    /**
     * List of objects (default null).
     *
     * @var \OxidEsales\Eshop\Core\Model\ListModel
     */
    protected $_o_list;
    /**
     * Position in list of objects (default 0).
     *
     * @var int
     */
    protected $_i_curr_list_pos = 0;
    /**
     * Size of object list (default 0).
     *
     * @var int
     */
    protected $_i_list_size = 0;
    /**
     * Array of SQL query conditions (default null).
     *
     * @var array
     */
    protected $_a_where;
    /**
     * Enable/disable sorting by DESC (SQL) (default false - disable).
     *
     * @var bool
     */
    protected $_bl_desc = false;
    /**
     * Set to true to enable multi language
     *
     * @var bool
     */
    protected $_bl_employ_multilanguage;
    /**
     * (default null).
     *
     * @var int
     */
    protected $_i_over_pos;
    /**
     * Viewable list size
     *
     * @var int
     */
    protected $_i_view_list_size = 0;
    /**
     * Viewable default list size (used in list_*.php views)
     *
     * @var int
     */
    protected $_i_def_view_list_size = 50;
    /**
     * List sorting array
     *
     * @var array
     */
    protected $_a_curr_sorting;
    /**
     * Default sorting field
     *
     * @var string
     */
    protected $_s_def_sort_field;
    /**
     * List filter array
     *
     * @var array
     */
    protected $_a_list_filter;
    /**
     * Returns sorting fields array
     *
     * @return array
     */
    public function get_list_sorting()
    {
        if ($this->_a_curr_sorting === null) {
            $this->_a_curr_sorting = Registry::get_request()->get_request_escaped_parameter('sort');
            if (!$this->_a_curr_sorting && $this->_s_def_sort_field && $base_object = $this->get_item_list_base_object()) {
                $this->_a_curr_sorting[$base_object->get_core_table_name()] = [$this->_s_def_sort_field => 'asc'];
            }
        }
        return $this->_a_curr_sorting;
    }
    /**
     * Returns list filter array
     *
     * @return array
     */
    public function get_list_filter()
    {
        if ($this->_a_list_filter === null) {
            $request = \Oxid_Esales\Eshop\Core\Registry::get_request();
            $filter = $request->get_request_parameter('where');
            $request->check_param_special_chars($filter);
            $this->_a_list_filter = $filter;
        }
        return $this->_a_list_filter;
    }
    /**
     * Viewable list size getter
     *
     * @return int
     */
    public function get_view_list_size()
    {
        if (!$this->_i_view_list_size) {
            $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            if ($profile = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('profile')) {
                if (isset($profile[1])) {
                    $config->set_config_param('iAdminListSize', (int) $profile[1]);
                }
            }
            $this->_i_view_list_size = (int) $config->get_config_param('iAdminListSize');
            if (!$this->_i_view_list_size) {
                $this->_i_view_list_size = 10;
                $config->set_config_param('iAdminListSize', $this->_i_view_list_size);
            }
        }
        return $this->_i_view_list_size;
    }
    /**
     * Viewable list size getter (used in list_*.php views)
     *
     * @return int
     */
    protected function get_user_def_list_size()
    {
        if (!$this->_i_view_list_size) {
            if (!$view_list_size = (int) Registry::get_request()->get_request_escaped_parameter('viewListSize')) {
                $view_list_size = $this->_i_def_view_list_size;
            }
            $this->_i_view_list_size = $view_list_size;
        }
        return $this->_i_view_list_size;
    }
    /** @inheritdoc */
    public function render()
    {
        $return = parent::render();
        // assign our list
        $this->_a_view_data['mylist'] = $this->get_item_list();
        // set navigation parameters
        $this->set_list_navigation_params();
        return $return;
    }
    /**
     * Deletes this entry from the database
     */
    public function delete_entry(): void
    {
        $delete = ox_new($this->_s_list_class);
        //disabling deletion for derived items
        if ($delete->is_derived()) {
            return;
        }
        $bl_delete = $delete->delete($this->get_edit_object_id());
        // #A - we must reset object ID
        if ($bl_delete && isset($_POST['oxid'])) {
            $_POST['oxid'] = -1;
        }
        $this->reset_content_cache();
        $this->init();
    }
    /**
     * Calculates list items count
     *
     * @param string $sql SQL query used co select list items
     */
    protected function calc_list_items_count($sql)
    {
        $string_modifier = Str::get_str();
        // count SQL
        $sql = $string_modifier->preg_replace('/select .* from/i', 'select count(*) from ', $sql);
        // removing order by
        $sql = $string_modifier->preg_replace('/order by .*$/i', '', $sql);
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        // con of list items which fits current search conditions
        $this->_i_list_size = \Oxid_Esales\Eshop\Core\Database_Provider::get_master()->get_one($sql);
        // set it into session that other frames know about size of DB
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('iArtCnt', $this->_i_list_size);
    }
    /**
     * Set current list position
     *
     * @param string $page jump page string
     */
    protected function set_current_list_position($page = null)
    {
        $admin_list_size = $this->get_view_list_size();
        $jump_to_page = $page ? (int) $page : (int) Registry::get_request()->get_request_escaped_parameter('lstrt') / $admin_list_size;
        $jump_to_page = $page && $jump_to_page ? $jump_to_page - 1 : $jump_to_page;
        $jump_to_page = $jump_to_page * $admin_list_size;
        if ($jump_to_page < 1) {
            $jump_to_page = 0;
        } elseif ($jump_to_page >= $this->_i_list_size) {
            $jump_to_page = floor($this->_i_list_size / $admin_list_size - 1) * $admin_list_size;
        }
        $this->_i_curr_list_pos = $this->_i_over_pos = (int) $jump_to_page;
    }
    /**
     * Adds order by to SQL query string.
     *
     * @param string $query sql string
     *
     * @return string
     */
    protected function prepare_order_by_query($query = null)
    {
        // sorting
        $sort_fields = $this->get_list_sorting();
        if (is_array($sort_fields) && count($sort_fields)) {
            // only add order by at full sql not for count(*)
            $query .= ' order by ';
            $add_separator = false;
            $list_item = $this->get_item_list_base_object();
            $language_id = $list_item->is_multilang() ? $list_item->get_language() : \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
            $descending = Registry::get_request()->get_request_escaped_parameter('adminorder');
            $descending = $descending !== null ? (bool) $descending : $this->_bl_desc;
            foreach ($sort_fields as $table => $field_data) {
                $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
                $table = $table ? $table_view_name_generator->get_view_name($table, $language_id) . '.' : '';
                foreach ($field_data as $column => $sort_directory) {
                    $field = $table . $column;
                    //add table name to column name if no table name found attached to column name
                    $query .= ($add_separator ? ', ' : '') . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_identifier($field);
                    //V oxActive field search always DESC
                    if ($descending || $column == 'oxactive' || strcasecmp((string) $sort_directory, 'desc') == 0) {
                        $query .= ' desc ';
                    }
                    $add_separator = true;
                }
            }
        }
        return $query;
    }
    /**
     * Builds and returns SQL query string.
     *
     * @param object $listObject list main object
     *
     * @return string
     */
    protected function build_select_string($list_object = null)
    {
        return $list_object !== null ? $list_object->build_select_string(null) : '';
    }
    /**
     * Prepares SQL where query according SQL condition array and attaches it to SQL end.
     * For each search value if german umlauts exist, adds them
     * and replaced by spec. char to query
     *
     * @param string $fieldValue Filters
     *
     * @return string
     */
    protected function process_filter($field_value)
    {
        $string_modifier = Str::get_str();
        //removing % symbols
        $field_value = $string_modifier->preg_replace('/^%|%$/', '', trim($field_value));
        return $string_modifier->preg_replace("/\\s+/", ' ', $field_value);
    }
    /**
     * Builds part of SQL query
     *
     * @param string $value         filter value
     * @param bool   $isSearchValue filter value type, true means surrount search key with '%'
     *
     * @return string
     */
    protected function build_filter($value, $is_search_value)
    {
        if ($is_search_value) {
            //is search string, using LIKE
            return ' like ' . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote('%' . $value . '%') . ' ';
        }
        //not search string, values must be equal
        return ' = ' . \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($value) . ' ';
    }
    /**
     * Checks if filter contains wildcards like %
     *
     * @param string $fieldValue filter value
     *
     * @return bool
     */
    protected function is_search_value($field_value)
    {
        return Str::get_str()->preg_match('/^%/', $field_value) && Str::get_str()->preg_match('/%$/', $field_value);
    }
    /**
     * Prepares SQL where query according SQL condition array and attaches it to SQL end.
     * For each search value if german umlauts exist, adds them
     * and replaced by spec. char to query
     *
     * @param array  $whereQuery SQL condition array
     * @param string $fullQuery  SQL query string
     *
     * @return string
     */
    protected function prepare_where_query($where_query, $full_query)
    {
        if (is_array($where_query) && count($where_query)) {
            $my_utils_string = \Oxid_Esales\Eshop\Core\Registry::get_utils_string();
            foreach ($where_query as $identifier_name => $field_value) {
                $field_value = trim((string) $field_value);
                //check if this is search string (contains % sign at beginning and end of string)
                $is_search_value = $this->is_search_value($field_value);
                //removing % symbols
                $field_value = $this->process_filter($field_value);
                if (strlen($field_value)) {
                    $values = explode(' ', $field_value);
                    //for each search field using AND action
                    $query_bool_action = ' and (';
                    foreach ($values as $value) {
                        // trying to search spec chars in search value
                        // if found, add cleaned search value to search sql
                        $uml = $my_utils_string->prepare_str_for_search($value);
                        if ($uml) {
                            $query_bool_action .= '(';
                        }
                        $quoted_identifier_name = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_identifier($identifier_name);
                        $full_query .= " {$query_bool_action} {$quoted_identifier_name} ";
                        //for search in same field for different values using AND
                        $query_bool_action = ' and ';
                        $full_query .= $this->build_filter($value, $is_search_value);
                        if ($uml) {
                            $full_query .= " or {$quoted_identifier_name} ";
                            $full_query .= $this->build_filter($uml, $is_search_value);
                            $full_query .= ')';
                            // end of OR section
                        }
                    }
                    // end for AND action
                    $full_query .= ' ) ';
                }
            }
        }
        return $full_query;
    }
    /**
     * Override this for individual search in admin.
     *
     * @param string $query SQL select to change
     *
     * @return string
     */
    protected function changeselect($query)
    {
        return $query;
    }
    /**
     * Builds and returns array of SQL WHERE conditions.
     *
     * @return array
     */
    public function build_where()
    {
        if ($this->_a_where === null && $list = $this->get_item_list()) {
            $this->_a_where = [];
            $filter = $this->get_list_filter();
            if (is_array($filter)) {
                $list_item = $this->get_item_list_base_object();
                $language_id = $list_item->is_multilang() ? $list_item->get_language() : \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
                $local_date_format = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sLocalDateFormat');
                foreach ($filter as $table => $filter_data) {
                    foreach ($filter_data as $name => $value) {
                        if ($value || '0' === (string) $value) {
                            $field = "{$table}__{$name}";
                            // if no table name attached to field name, add it
                            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
                            $name = $table ? $table_view_name_generator->get_view_name($table, $language_id) . ".{$name}" : $name;
                            // #M1260: if field is date
                            if ($local_date_format && $local_date_format != 'ISO' && isset($list_item->{$field})) {
                                $field_type = $list_item->{$field}->fldtype;
                                if ('datetime' == $field_type || 'date' == $field_type) {
                                    $value = $this->convert_to_db_date($value, $field_type);
                                }
                            }
                            $this->_a_where[$name] = "%{$value}%";
                        }
                    }
                }
            }
        }
        return $this->_a_where;
    }
    /**
     * Converts date/datetime values to DB scheme (#M1260)
     *
     * @param string $value     Field value
     * @param string $fieldType Field type
     *
     * @return string
     */
    protected function convert_to_db_date($value, $field_type)
    {
        $converted_object = new \Oxid_Esales\Eshop\Core\Field();
        $converted_object->set_value($value);
        if ($field_type == 'datetime') {
            if (strlen($value) == 10 || strlen($value) == 22 || strlen($value) == 19 && !stripos($value, 'm')) {
                \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->convert_db_date_time($converted_object, true);
            } else {
                if (strlen($value) > 10) {
                    return $this->convert_time($value);
                }
                return $this->convert_date($value);
            }
        } elseif ($field_type == 'date') {
            if (strlen($value) == 10) {
                \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->convert_db_date($converted_object, true);
            } else {
                return $this->convert_date($value);
            }
        }
        return $converted_object->value;
    }
    /**
     * Converter for date field search. If not full date will be searched.
     *
     * @param string $date searched date
     *
     * @return string
     */
    protected function convert_date($date)
    {
        // regexps to validate input
        $date_patterns = [
            "/^([0-9]{2})\\.([0-9]{4})/" => 'EUR2',
            // MM.YYYY
            "/^([0-9]{2})\\.([0-9]{2})/" => 'EUR1',
            // DD.MM
            "/^([0-9]{2})\\/([0-9]{4})/" => 'USA2',
            // MM.YYYY
            "/^([0-9]{2})\\/([0-9]{2})/" => 'USA1',
        ];
        // date/time formatting rules
        $date_formats = ['EUR1' => [2, 1], 'EUR2' => [2, 1], 'USA1' => [1, 2], 'USA2' => [2, 1]];
        // looking for date field
        $date_matches = [];
        $string_modifier = Str::get_str();
        foreach ($date_patterns as $pattern => $type) {
            if ($string_modifier->preg_match($pattern, $date, $date_matches)) {
                $date = $date_matches[$date_formats[$type][0]] . '-' . $date_matches[$date_formats[$type][1]];
                break;
            }
        }
        return $date;
    }
    /**
     * Converter for datetime field search. If not full time will be searched.
     *
     * @param string $fullDate searched date
     *
     * @return string
     */
    protected function convert_time($full_date)
    {
        $date = substr($full_date, 0, 10);
        $converted_object = new \Oxid_Esales\Eshop\Core\Field();
        $converted_object->set_value($date);
        \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->convert_db_date($converted_object, true);
        $string_modifier = Str::get_str();
        // looking for time field
        $time = substr($full_date, 11);
        if ($string_modifier->preg_match('/([0-9]{2}):([0-9]{2}) ([AP]{1}[M]{1})$/', $time, $time_matches)) {
            if ($time_matches[3] == 'PM') {
                $int_val = (int) $time_matches[1];
                if ($int_val < 13) {
                    $time = $int_val + 12 . ':' . $time_matches[2];
                }
            } else {
                $time = $time_matches[1] . ':' . $time_matches[2];
            }
        } elseif ($string_modifier->preg_match('/([0-9]{2}) ([AP]{1}[M]{1})$/', $time, $time_matches)) {
            if ($time_matches[2] == 'PM') {
                $int_val = (int) $time_matches[1];
                if ($int_val < 13) {
                    $time = $int_val + 12;
                }
            } else {
                $time = $time_matches[1];
            }
        } else {
            $time = str_replace('.', ':', $time);
        }
        return $converted_object->value . ' ' . $time;
    }
    /**
     * Set parameters needed for list navigation
     */
    protected function set_list_navigation_params()
    {
        // list navigation
        $show_navigation = false;
        $admin_list_size = $this->get_view_list_size();
        if ($this->_i_list_size > $admin_list_size) {
            // yes, we need to build the navigation object
            $page_navigation = new stdClass();
            $page_navigation->pages = round(($this->_i_list_size - 1) / $admin_list_size + 0.5, 0);
            $page_navigation->actpage = round($this->_i_curr_list_pos / $admin_list_size + 0.5, 0);
            $page_navigation->lastlink = ($page_navigation->pages - 1) * $admin_list_size;
            $page_navigation->nextlink = null;
            $page_navigation->backlink = null;
            $position = $this->_i_curr_list_pos + $admin_list_size;
            if ($position < $this->_i_list_size) {
                $page_navigation->nextlink = $position = $this->_i_curr_list_pos + $admin_list_size;
            }
            if ($this->_i_curr_list_pos - $admin_list_size >= 0) {
                $page_navigation->backlink = $position = $this->_i_curr_list_pos - $admin_list_size;
            }
            // calculating list start position
            $start = $page_navigation->actpage - 5;
            $start = $start <= 0 ? 1 : $start;
            // calculating list end position
            $end = $page_navigation->actpage + 5;
            $end = $end < $start + 10 ? $start + 10 : $end;
            $end = $end > $page_navigation->pages ? $page_navigation->pages : $end;
            // once again adjusting start pos ..
            $start = $end - 10 > 0 ? $end - 10 : $start;
            $start = $page_navigation->pages <= 11 ? 1 : $start;
            // navigation urls
            for ($i = $start; $i <= $end; $i++) {
                $page = new stdclass();
                $page->selected = 0;
                if ($i == $page_navigation->actpage) {
                    $page->selected = 1;
                }
                $page_navigation->change_page[$i] = $page;
            }
            $this->_a_view_data['pagenavi'] = $page_navigation;
            if (isset($this->_i_over_pos)) {
                $position = $this->_i_over_pos;
                $this->_i_over_pos = null;
            } else {
                $position = Registry::get_request()->get_request_escaped_parameter('lstrt');
            }
            if (!$position) {
                $position = 0;
            }
            $this->_a_view_data['lstrt'] = $position;
            $this->_a_view_data['listsize'] = $this->_i_list_size;
            $show_navigation = true;
        }
        // determine not used space in List
        $list_size_to_show = $this->_i_list_size - $this->_i_curr_list_pos;
        $admin_list_size = $this->get_view_list_size();
        $not_used = $admin_list_size - min($list_size_to_show, $admin_list_size);
        $space = $not_used * 15;
        if (!$show_navigation) {
            $space += 20;
        }
        $this->_a_view_data['iListFillsize'] = $space;
    }
    /**
     * Sets-up navigation parameters
     *
     * @param string $node active view id
     */
    protected function setup_navigation($node)
    {
        // navigation according to class
        if ($node) {
            $admin_navigation = $this->get_navigation();
            $object_id = $this->get_edit_object_id();
            if ($object_id == -1) {
                //on first call or when pressed creating new item button, resetting active tab
                $active_tab = $this->_i_def_edit;
            } else {
                // active tab
                $active_tab = Registry::get_request()->get_request_escaped_parameter('actedit');
                $active_tab = $active_tab ?: $this->_i_def_edit;
            }
            // tabs
            $this->_a_view_data['editnavi'] = $admin_navigation->get_tabs($node, $active_tab);
            // active tab
            $this->_a_view_data['actlocation'] = $admin_navigation->get_active_tab($node, $active_tab);
            // default tab
            $this->_a_view_data['default_edit'] = $admin_navigation->get_active_tab($node, $this->_i_def_edit);
            // assign active tab number
            $this->_a_view_data['actedit'] = $active_tab;
        }
    }
    /**
     * Returns items list
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_item_list()
    {
        if ($this->_o_list === null && $this->_s_list_class) {
            $this->_o_list = ox_new($this->_s_list_type);
            $this->_o_list->clear();
            $this->_o_list->init($this->_s_list_class);
            $where = $this->build_where();
            $list_object = $this->_o_list->get_base_object();
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('tabelle', $this->_s_list_class);
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $this->_a_view_data['listTable'] = $table_view_name_generator->get_view_name($list_object->get_core_table_name());
            \Oxid_Esales\Eshop\Core\Registry::get_config()->set_global_parameter('ListCoreTable', $list_object->get_core_table_name());
            if ($list_object->is_multilang()) {
                // is the object multilingual?
                /** @var \OxidEsales\Eshop\Core\Model\MultiLanguageModel $listObject */
                $list_object->set_language(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language());
                if (isset($this->_bl_employ_multilanguage)) {
                    $list_object->set_enable_multilang($this->_bl_employ_multilanguage);
                }
            }
            $query = $this->build_select_string($list_object);
            $query = $this->prepare_where_query($where, $query);
            $query = $this->prepare_order_by_query($query);
            $query = $this->changeselect($query);
            // calculates count of list items
            $this->calc_list_items_count($query);
            // setting current list position (page)
            $this->set_current_list_position(Registry::get_request()->get_request_escaped_parameter('jumppage'));
            // setting addition params for list: current list size
            $this->_o_list->set_sql_limit($this->_i_curr_list_pos, $this->get_view_list_size());
            $this->_o_list->select_string($query);
        }
        return $this->_o_list;
    }
    /**
     * Clear items list
     */
    public function clear_item_list(): void
    {
        $this->_o_list = null;
    }
    /**
     * Returns item list base object
     *
     * @return \OxidEsales\Eshop\Core\Model\BaseModel|null
     */
    public function get_item_list_base_object()
    {
        if ($items_list = $this->get_item_list()) {
            return $items_list->get_base_object();
        }
        return null;
    }
}