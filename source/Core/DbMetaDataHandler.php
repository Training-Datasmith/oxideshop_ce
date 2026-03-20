<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Model\Shop;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Class for handling database related operations
 */
class Db_Meta_Data_Handler extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     *
     * @var array
     */
    protected $_a_tables;
    /**
     *
     * @var int
     */
    protected $_i_current_max_lang_id;
    /**
     *
     * @var array Tables which should be skipped from resetting
     */
    protected $_a_skip_tables_on_reset = ['oxcountry'];
    /**
     * When creating views, always use those fields from core table.
     *
     * @var array
     */
    protected $force_original_fields = ['OXID'];
    /**
     *  Get table fields
     *
     * @param string $tableName table name
     *
     * @return array
     */
    public function get_fields($table_name)
    {
        $fields = [];
        $raw_fields = Database_Provider::get_db()->meta_columns($table_name);
        if (is_array($raw_fields)) {
            foreach ($raw_fields as $field) {
                $fields[$field->name] = "{$table_name}.{$field->name}";
            }
        }
        return $fields;
    }
    /**
     * Check if table exists
     *
     * @param string $tableName table name
     *
     * @return bool
     */
    public function table_exists($table_name)
    {
        $db = Database_Provider::get_db();
        $tables = $db->get_all('show tables like ' . $db->quote($table_name));
        return count($tables) > 0;
    }
    /**
     * Check if field exists in table
     *
     * @param string $fieldName field name
     * @param string $tableName table name
     *
     * @return bool
     */
    public function field_exists($field_name, $table_name)
    {
        $table_fields = $this->get_fields($table_name);
        $table_name = strtoupper($table_name);
        if (is_array($table_fields)) {
            $field_name = strtoupper($field_name);
            $table_fields = array_map(strtoupper(...), $table_fields);
            if (in_array("{$table_name}.{$field_name}", $table_fields)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Get the indices of a table
     *
     * @param string $tableName The name of the table for which we want the
     *
     * @return array The indices of the given table
     */
    public function get_indices($table_name)
    {
        if ($this->table_exists($table_name)) {
            return Database_Provider::get_db()->get_all("SHOW INDEX FROM {$table_name}");
        }
        return [];
    }
    /**
     * Check, if the table has an index with the given name
     *
     * @param string $indexName The name of the index we want to check
     * @param string $tableName The table to check for the index
     *
     * @return bool Has the table the given index?
     */
    public function has_index($index_name, $table_name)
    {
        $result = false;
        foreach ($this->get_indices($table_name) as $index) {
            if ($index_name === $index['Column_name']) {
                $result = true;
            }
        }
        return $result;
    }
    /**
     * Get the index of a given table by its name
     *
     * @param string $indexName The name of the index
     * @param string $tableName The name of the table from which we want the index
     *
     * @return null|array The index with the given name
     */
    public function get_index_by_name($index_name, $table_name)
    {
        $indices = $this->get_indices($table_name);
        $result = null;
        foreach ($indices as $index) {
            if ($index_name === $index['Column_name']) {
                $result = $index;
            }
        }
        return $result;
    }
    /**
     * Get all tables names from db. Views tables are not included in
     * this list.
     *
     * @return array
     */
    public function get_all_tables()
    {
        if (empty($this->_a_tables)) {
            $tables_names = Database_Provider::get_db()->get_col('show tables');
            foreach ($tables_names as $table_name) {
                if ($this->validate_table_name($table_name)) {
                    $this->_a_tables[] = $table_name;
                }
            }
        }
        return $this->_a_tables;
    }
    /**
     * return all DB tables for the language sets
     *
     * @param string $table table name to check
     *
     * @return array
     */
    public function get_all_multi_tables($table)
    {
        $m_l_tables = [];
        foreach (array_keys(Registry::get_lang()->get_language_ids()) as $lang_id) {
            $lang_table_name = get_lang_table_name($table, $lang_id);
            if ($table != $lang_table_name && !in_array($lang_table_name, $m_l_tables)) {
                $m_l_tables[] = $lang_table_name;
            }
        }
        return $m_l_tables;
    }
    /**
     * Get sql for new multi-language table set creation
     *
     * @param string $table core table name
     * @param string $lang  language id
     *
     * @return string
     */
    protected function get_create_table_set_sql($table, $lang)
    {
        $table_set = get_lang_table_name($table, $lang);
        $table_status = Database_Provider::get_db()->get_row('SHOW TABLE STATUS LIKE :tablename', ['tablename' => $table]);
        $table_status = array_values($table_status);
        return "CREATE TABLE `{$table_set}` (" . '`OXID` char(32) NOT NULL, ' . 'PRIMARY KEY (`OXID`)) ' . 'DEFAULT CHARACTER SET latin1 COLLATE latin1_general_ci ENGINE= ' . $table_status[1] . ' ' . "COMMENT='" . $table_status[17] . "'";
    }
    /**
     * Get sql for new multi-language field creation
     *
     * @param string $table     core table name
     * @param string $field     field name
     * @param string $newField  new field name
     * @param string $prevField previous field in table
     * @param string $tableSet  table to change (if not set take core table)
     *
     * @return string
     */
    public function get_add_field_sql($table, $field, $new_field, $prev_field, $table_set = null)
    {
        if (!$table_set) {
            $table_set = $table;
        }
        $table_sql = $this->get_table_creation_sql($table);
        // removing comments;
        $table_sql = preg_replace('/COMMENT \\\'.*?\\\'/', '', $table_sql);
        preg_match("/.*,\\s+(['`]?" . preg_quote($field, '/') . "['`]?\\s+[^,]+),.*/", (string) $table_sql, $match);
        $field_sql = $match[1] ?? '';
        $sql = '';
        if (!empty($field_sql)) {
            $field_sql = preg_replace('/' . preg_quote($field, '/') . '/', $new_field, $field_sql);
            $sql = "ALTER TABLE `{$table_set}` ADD " . $field_sql;
            if ($this->table_exists($table_set) && $this->field_exists($prev_field, $table_set)) {
                $sql .= " AFTER `{$prev_field}`";
            }
        }
        return $sql;
    }
    /**
     * Get sql for new multi-language field index creation
     *
     * @param string $table    core table name
     * @param string $field    field name
     * @param string $newField new field name
     * @param string $tableSet table to change (if not set take core table)
     *
     * @return string
     */
    public function get_add_field_index_sql($table, $field, $new_field, $table_set = null)
    {
        $table_sql = $this->get_table_creation_sql($table);
        preg_match_all("/([\\w]+\\s+)?\\bKEY\\s+(`[^`]+`)?\\s*\\([^)]+(\\(\\d++\\))*\\)/iU", $table_sql, $match);
        $index = $match[0];
        $using_table_set = $table_set ? true : false;
        if (!$table_set) {
            $table_set = $table;
        }
        $index_queries = [];
        $sql = [];
        if (count($index)) {
            foreach ($index as $key => $index_query) {
                if (preg_match("/\\([^)]*\\b" . $field . "\\b[^)]*\\)/i", $index_query)) {
                    //removing index name - new will be added automaticly
                    $index_query = preg_replace("/(.*\\bKEY\\s+)`[^`]+`/", '$1', $index_query);
                    if ($using_table_set) {
                        // replacing multiple fields to one (#3269)
                        $index_query = preg_replace("/\\([^\\)]+\\)+/", "(`{$new_field}`{$match[3][$key]})", (string) $index_query);
                    } else {
                        //replacing previous field name with new one
                        $index_query = preg_replace("/\\b" . $field . "\\b/", $new_field, (string) $index_query);
                    }
                    $index_queries[] = 'ADD ' . $index_query;
                }
            }
            if (count($index_queries)) {
                $sql = ["ALTER TABLE `{$table_set}` " . implode(', ', $index_queries)];
            }
        }
        return $sql;
    }
    /**
     * Get max language ID used in shop. For checking is used table "oxarticle"
     * field "oxtitle"
     *
     * @return int
     */
    public function get_current_max_lang_id()
    {
        if (isset($this->_i_current_max_lang_id)) {
            return $this->_i_current_max_lang_id;
        }
        $table = $table_set = 'oxarticles';
        $field = $field_set = 'oxtitle';
        $lang = 0;
        while ($this->table_exists($table_set) && $this->field_exists($field_set, $table_set)) {
            $lang++;
            $table_set = get_lang_table_name($table, $lang);
            $field_set = $field . '_' . $lang;
        }
        return $this->_i_current_max_lang_id = --$lang;
    }
    /**
     * Get next available language ID
     *
     * @return int
     */
    public function get_next_lang_id()
    {
        return $this->get_current_max_lang_id() + 1;
    }
    /**
     * Get table multi-language fields
     *
     * @param string $table table name
     *
     * @return array
     */
    public function get_multilang_fields($table)
    {
        $fields = $this->get_fields($table);
        $multi_lang_fields = [];
        foreach ($fields as $field) {
            if (preg_match("/({$table}\\.)?(?<field>.+)_1\$/", (string) $field, $matches)) {
                $multi_lang_fields[] = $matches['field'];
            }
        }
        return $multi_lang_fields;
    }
    /**
     * Get single language fields
     *
     * @param string $table table name
     * @param int    $lang  language id
     *
     * @return array
     */
    public function get_singlelang_fields($table, $lang)
    {
        $lang_table = get_lang_table_name($table, $lang);
        $base_fields = $this->get_fields($table);
        $lang_fields = $this->get_fields($lang_table);
        //Some fields (for example OXID) must be taken from core table.
        $lang_fields = $this->filter_core_fields($lang_fields);
        $fields = array_merge($base_fields, $lang_fields);
        $single_lang_fields = [];
        foreach ($fields as $field_name => $field) {
            if (preg_match("/(({$table}|{$lang_table})\\.)?(?<field>.+)_(?<lang>[0-9]+)\$/", (string) $field, $matches)) {
                if ($matches['lang'] == $lang) {
                    $single_lang_fields[$matches['field']] = $field;
                }
            } else {
                $single_lang_fields[$field_name] = $field;
            }
        }
        return $single_lang_fields;
    }
    /**
     * Add new multi-languages fields to table. Duplicates all multi-language
     * fields and fields indexes with next available language ID
     *
     * @param string $table table name
     */
    public function add_new_multilang_field($table): void
    {
        $new_lang = $this->get_next_lang_id();
        $this->ensure_multi_language_fields($table, $new_lang);
    }
    /**
     * Ensure, that all multi language fields of the given table are present.
     *
     * @param string $table The table we want to assure, that the multi language fields are present.
     */
    public function ensure_all_multi_language_fields($table): void
    {
        $max = $this->get_current_max_lang_id();
        for ($index = 1; $index <= $max; $index++) {
            $this->ensure_multi_language_fields($table, $index);
        }
    }
    /**
     * Resetting all multi-language fields with specific language id
     * to default value in selected table
     *
     * @param int    $langId    Language id
     * @param string $tableName Table name
     */
    public function reset_multilang_fields($lang_id, $table_name): void
    {
        $lang_id = (int) $lang_id;
        if ($lang_id === 0) {
            return;
        }
        $sql = [];
        $fields = $this->get_multilang_fields($table_name);
        if (is_array($fields) && count($fields) > 0) {
            foreach ($fields as $field_name) {
                $field_name = $field_name . '_' . $lang_id;
                if ($this->field_exists($field_name, $table_name)) {
                    //resetting field value to default
                    $sql[] = "UPDATE {$table_name} SET {$field_name} = DEFAULT;";
                }
            }
        }
        if (!empty($sql)) {
            $this->execute_sql($sql);
        }
    }
    /**
     * Add new language to database. Scans all tables and adds new
     * multi-language fields
     */
    public function add_new_lang_to_db(): void
    {
        //reset max count
        $this->_i_current_max_lang_id = null;
        $table = $this->get_all_tables();
        foreach ($table as $table_name) {
            $this->add_new_multilang_field($table_name);
        }
        //updating views
        $this->update_views();
    }
    /**
     * Resetting all multi-language fields with specific language id
     * to default value in all tables. Only if language ID > 0.
     *
     * @param int $langId Language id
     */
    public function reset_language($lang_id): void
    {
        if ((int) $lang_id === 0) {
            return;
        }
        $tables = $this->get_all_tables();
        // removing tables which does not requires reset
        foreach ($this->_a_skip_tables_on_reset as $skip_table) {
            if (($skip_id = array_search($skip_table, $tables)) !== false) {
                unset($tables[$skip_id]);
            }
        }
        foreach ($tables as $table_name) {
            $this->reset_multilang_fields($lang_id, $table_name);
        }
    }
    /**
     * Executes array of sql strings
     *
     * @param array $queries SQL query array
     */
    public function execute_sql($queries): void
    {
        $db = Database_Provider::get_db();
        if (is_array($queries) && !empty($queries)) {
            foreach ($queries as $query) {
                $query = trim((string) $query);
                if (!empty($query)) {
                    $db->execute($query);
                }
            }
        }
    }
    /**
     * Updates all views
     *
     * @param array|null $tables array of DB table name that can store different data per shop like oxArticle
     */
    public function update_views(?array $tables = null): bool
    {
        set_time_limit(0);
        Shop::disable_views();
        $db = Database_Provider::get_db();
        $config = Registry::get_config();
        $this->safe_guard_additional_multi_language_tables();
        $shops = $db->get_all('select * from oxshops');
        $tables = $tables ?: Container_Facade::get_parameter('oxid_esales.multi_shop_tables');
        $success = true;
        foreach ($shops as $shop_values) {
            $shop_id = $shop_values['OXID'];
            $shop = ox_new(Shop::class);
            $shop->load($shop_id);
            $shop->set_multi_shop_tables($tables);
            $mall_inherit = [];
            foreach ($tables as $table) {
                $mall_inherit[$table] = $config->get_shop_conf_var('blMallInherit_' . $table, $shop_id);
            }
            if (!$shop->generate_views(false, $mall_inherit) && $success) {
                $success = false;
            }
        }
        return $success;
    }
    /**
     * Make sure that e.g. OXID is always used from core table when creating views.
     * Otherwise we might have unwanted side effects from rows with OXIDs null in view tables.
     *
     * @param array $fields Language fields array we need to filter for core fields.
     *
     * @return array
     */
    protected function filter_core_fields($fields)
    {
        foreach ($this->force_original_fields as $fieldname) {
            if (array_key_exists($fieldname, $fields)) {
                unset($fields[$fieldname]);
            }
        }
        return $fields;
    }
    /**
     * Ensure that all *_set* tables for all tables in container parameter 'oxid_multilingual_tables'
     * are created.
     */
    protected function safe_guard_additional_multi_language_tables()
    {
        $max_lang = $this->get_current_max_lang_id();
        $multi_language_tables = Container_Facade::get_parameter('oxid_esales.multilingual_tables');
        if (!is_array($multi_language_tables) || empty($multi_language_tables)) {
            return;
            //nothing to do
        }
        foreach ($multi_language_tables as $table) {
            if ($this->table_exists($table)) {
                //We start with language id 1 and rely on that all fields for language 0 exists.
                //For language id 0 we have e.g. OXTITLE and logic here would expect it to
                //be OXTITLE_0, add that as new field, leading to incorrect data in views later on.
                for ($i = 1; $i <= $max_lang; $i++) {
                    $this->ensure_multi_language_fields($table, $i);
                }
            }
        }
    }
    /**
     * Make sure that all *_set* tables with all required multilanguage fields are created.
     *
     * @param string $table
     * @param int    $languageId
     */
    protected function ensure_multi_language_fields($table, $language_id)
    {
        $fields = $this->get_multilang_fields($table);
        $sql = [];
        $table_set = get_lang_table_name($table, $language_id);
        if (!$this->table_exists($table_set)) {
            $sql[] = $this->get_create_table_set_sql($table, $language_id);
        }
        if (is_array($fields) && count($fields) > 0) {
            foreach ($fields as $field) {
                $new_field_name = $field . '_' . $language_id;
                if ($language_id > 1) {
                    $previous_language = $language_id - 1;
                    $previous_field = $field . '_' . $previous_language;
                } else {
                    $previous_field = $field;
                }
                if (!$this->table_exists($table_set) || !$this->field_exists($new_field_name, $table_set)) {
                    //getting add field sql
                    $sql[] = $this->get_add_field_sql($table, $field, $new_field_name, $previous_field, $table_set);
                    //getting add index sql on added field
                    $sql = array_merge($sql, (array) $this->get_add_field_index_sql($table, $field, $new_field_name, $table_set));
                }
            }
        }
        $this->execute_sql($sql);
    }
    /**
     * Adds possibility to validate table names.
     *
     * @param string $tableName
     *
     * @return bool
     */
    protected function validate_table_name($table_name)
    {
        return true;
    }
    private function get_table_creation_sql(string $table_name): string
    {
        $result = Database_Provider::get_db()->get_row(sprintf('show create table %s', Database_Provider::get_db()->quote_identifier($table_name)));
        return array_values($result)[1];
    }
}