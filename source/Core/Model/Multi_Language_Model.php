<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Ox_Object_Exception;
/**
 * Class handling multilanguage data fields
 */
class Multi_Language_Model extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Name of class.
     *
     * @var string
     */
    protected $_s_class_name = 'oxI18n';
    /**
     * Active object language.
     *
     * @var int
     */
    protected $_i_language;
    /**
     * Sometimes you need to deal with all fields not only with active
     * language, then set to false (default is true).
     *
     * @var bool
     */
    protected $_bl_employ_multilanguage = true;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        //T2008-02-22
        //lets try to differentiate cache keys for oxI18n and oxBase
        //in order not to load cached structure for the instances of oxbase classe called on same table
        if ($this->_s_cache_key) {
            $this->_s_cache_key .= '_i18n';
        }
    }
    /**
     * Sets object language.
     *
     * @param string $lang string (default null)
     */
    public function set_language($lang = null): void
    {
        $this->_i_language = (int) $lang;
        // reset
        $this->_s_view_table = false;
    }
    /**
     * Returns object language
     *
     * @return int
     */
    public function get_language()
    {
        if ($this->_i_language === null) {
            $this->_i_language = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        }
        return $this->_i_language;
    }
    /**
     * Object multilanguage mode setter (set true to enable multilang mode).
     * This setter affects init() method so it should be called before init() is executed
     *
     * @param bool $employMultilanguage New $this->_blEmployMultilanguage value
     */
    public function set_enable_multilang($employ_multilanguage): void
    {
        if ($this->_bl_employ_multilanguage != $employ_multilanguage) {
            $this->_bl_employ_multilanguage = $employ_multilanguage;
            if (!$employ_multilanguage) {
                //#63T
                $this->modify_cache_key('_nonml');
            }
            // reset
            $this->_s_view_table = false;
            if (count($this->_a_field_names) > 1) {
                $this->init_data_structure();
            }
        }
    }
    /**
     * Checks if this field is multlingual
     * (returns false if language = 0)
     *
     * @param string $fieldName Field name
     *
     * @return bool
     */
    public function is_multilingual_field($field_name)
    {
        $field_name = strtolower($field_name);
        if (isset($this->_a_field_names[$field_name])) {
            return (bool) $this->_a_field_names[$field_name];
        }
        //not inited field yet
        //and note that this is should be called only in first call after tmp dir is empty
        start_profile('!__CACHABLE2__!');
        $is_multilang = (bool) $this->get_field_status($field_name);
        stop_profile('!__CACHABLE2__!');
        return $is_multilang;
    }
    /**
     * Returns true, if object has multilanguage fields.
     * In oxi18n it is always returns true.
     *
     * @return bool
     */
    public function is_multilang()
    {
        return true;
    }
    /**
     * Loads object data from DB in passed language, returns true on success.
     *
     * @param integer $language Load this language compatible data
     * @param string  $oxid     object ID
     *
     * @return bool
     */
    public function load_in_lang($language, $oxid)
    {
        // set new lang to this object
        $this->set_language($language);
        // reset
        $this->_s_view_table = false;
        return $this->load($oxid);
    }
    /**
     * Lazy loading cache key modifier.
     *
     * @param string $cacheKey kache  key
     * @param bool   $override marker to force override cache key
     */
    public function modify_cache_key($cache_key, $override = false): void
    {
        if ($override) {
            $this->_s_cache_key = $cache_key . '|i18n';
        } else {
            $this->_s_cache_key .= $cache_key;
        }
        if (!$cache_key) {
            $this->_s_cache_key = null;
        }
    }
    /**
     * Returns an array of languages in which object multilanguage
     * fields are already setted
     *
     * @return array
     */
    public function get_available_in_langs()
    {
        $languages = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_names();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $obj_fields = $this->get_table_fields($table_view_name_generator->get_view_name($this->_s_core_table, -1, -1), true);
        $multi_lang_fields = [];
        //selecting all object multilang fields
        foreach ($obj_fields as $key => $value) {
            //skipping oxactive field
            if (preg_match('/^oxactive(_(\d{1,2}))?$/', $key)) {
                continue;
            }
            $field_lang = $this->get_field_lang($key);
            //checking, if field is multilanguage
            if ($this->is_multilingual_field($key) || $field_lang > 0) {
                $new_key = preg_replace('/_(\d{1,2})$/', '', $key);
                $multi_lang_fields[$new_key][] = (int) $field_lang;
            }
        }
        // if no multilanguage fields, return default languages array
        if (count($multi_lang_fields) < 1) {
            return $languages;
        }
        // select from non-multilanguage core view (all ml tables joined to one)
        $db = Database_Provider::get_db();
        $query = 'select * from ' . $table_view_name_generator->get_view_name($this->_s_core_table, -1, -1) . ' where oxid = :oxid';
        $rs = $db->get_all($query, ['oxid' => $this->get_id()]);
        $not_in_lang = $languages;
        // checks if object field data is not empty in all available languages
        // and formats not available in languages array
        if (isset($rs[0]) && is_array($rs[0]) && count($rs[0])) {
            $rs[0] = array_change_key_case($rs[0], CASE_UPPER);
            foreach ($multi_lang_fields as $field_id => $multi_lang_ids) {
                foreach ($multi_lang_ids as $multi_lang_id) {
                    $field_name = $multi_lang_id == 0 ? $field_id : $field_id . '_' . $multi_lang_id;
                    if ($rs[0][strtoupper($field_name)]) {
                        unset($not_in_lang[$multi_lang_id]);
                        continue;
                    }
                }
            }
        }
        return array_diff($languages, $not_in_lang);
    }
    /**
     * Returns _aFieldName[] value. 0 means - non multilanguage, 1 - multilanguage field.
     * This method is slow, so we should make sure it is called only when tmp dir is cleaned (and then the results are cached).
     *
     * @param string $fieldName Field name
     *
     * @return int
     */
    protected function get_field_status($field_name)
    {
        $all_field = $this->get_all_fields(true);
        if (isset($all_field[strtolower($field_name) . '_1'])) {
            return 1;
        }
        return 0;
    }
    /**
     * Returns the list of fields. This function is slower and its result is normally cached.
     * Basically we have 3 separate cases here:
     *  1. We are in admin so we need extended info for all fields (name, field length and field type)
     *  2. Object is not lazy loaded so we will return all data fields as simple array, as we need only names
     *  3. Object is lazy loaded so we will return empty array as all fields are loaded on request (in __get()).
     *
     * @param bool $forceFullStructure Whether to force loading of full data structure
     *
     * @return array
     */
    protected function get_non_cached_field_names($force_full_structure = false)
    {
        //Tomas
        //TODO: this place could be optimized. please check what we can do.
        $fields = parent::get_non_cached_field_names($force_full_structure);
        if (!$this->_bl_employ_multilanguage) {
            return $fields;
        }
        //lets do some pointer manipulation
        if ($fields) {
            //non admin fields
            $working_fields =& $fields;
        } else {
            //most likely admin fields so we remove another language
            $working_fields =& $this->_a_field_names;
        }
        //we have an array of fields, lets remove multilanguage fields
        foreach ($working_fields as $name => $val) {
            if ($this->get_field_lang($name)) {
                unset($working_fields[$name]);
            } else {
                $working_fields[$name] = $this->get_field_status($name);
            }
        }
        return $working_fields;
    }
    /**
     * Gets multilanguage field language. In case of oxtitle_2 it will return 2. 0 is returned if language ending is not defined.
     *
     * @param string $fieldName Field name
     *
     * @return bool
     */
    protected function get_field_lang($field_name)
    {
        if (!str_contains($field_name, '_')) {
            return 0;
        }
        if (preg_match('/_(\d{1,2})$/', $field_name, $regs)) {
            return $regs[1];
        }
        return 0;
    }
    /**
     * Returns DB field name for update.
     *
     * @param string $field Field name
     *
     * @return string
     */
    public function get_update_sql_field_name($field)
    {
        $lang = $this->get_language();
        if ($lang && $this->_bl_employ_multilanguage && $this->is_multilingual_field($field)) {
            $field .= '_' . $lang;
        }
        return $field;
    }
    /**
     * Checks whether certain field has changed, and sets update seo flag if needed.
     * It can only set the value to false, so it allows for multiple calls to the method,
     * and if atleast one requires seo update, other checks won't override that.
     * Will try to get multilang table name for relevant field check.
     *
     * @param string $field Field name that will be checked
     */
    protected function set_update_seo_on_field_change($field)
    {
        parent::set_update_seo_on_field_change($this->get_update_sql_field_name($field));
    }
    /**
     * return update fields SQL part
     *
     * @param string $table             table name to be updated
     * @param bool   $useSkipSaveFields use skip save fields array?
     *
     * @return string
     */
    protected function get_update_fields_for_table($table, $use_skip_save_fields = true)
    {
        $core_table = $this->get_core_table_name();
        $skip_multilingual = false;
        $skip_core_fields = false;
        if ($table != $core_table) {
            $skip_core_fields = true;
        }
        if ($this->_bl_employ_multilanguage) {
            if ($table != get_lang_table_name($core_table, $this->get_language())) {
                $skip_multilingual = true;
            }
        }
        $sql = '';
        $sep = false;
        foreach (array_keys($this->_a_field_names) as $key) {
            $key_lowercase = strtolower((string) $key);
            if ($key_lowercase != 'oxid') {
                if ($this->_bl_employ_multilanguage) {
                    if ($skip_multilingual && $this->is_multilingual_field($key)) {
                        continue;
                    }
                    if ($skip_core_fields && !$this->is_multilingual_field($key)) {
                        continue;
                    }
                } else {
                    // need to explicitly check field language
                    $field_lang = $this->get_field_lang($key);
                    if ($field_lang) {
                        if ($table != get_lang_table_name($core_table, $field_lang)) {
                            continue;
                        }
                    } elseif ($skip_core_fields) {
                        continue;
                    }
                }
            }
            if (!$this->check_field_can_be_updated($key)) {
                continue;
            }
            $long_name = $this->get_field_long_name($key);
            $field = $this->{$long_name};
            if (!$use_skip_save_fields || $use_skip_save_fields && !in_array($key_lowercase, $this->_a_skip_save_fields)) {
                $key = $this->get_update_sql_field_name($key);
                $sql .= ($sep ? ',' : '') . $key . ' = ' . $this->get_update_field_value($key, $field);
                $sep = true;
            }
        }
        return $sql;
    }
    /**
     * Get object fields sql part for base table
     * used for updates or inserts:
     * return e.g.  fldName1 = 'value1',fldName2 = 'value2'...
     *
     * @param bool $useSkipSaveFields forces usage of skip save fields array (default is true)
     *
     * @return string
     */
    protected function get_update_fields($use_skip_save_fields = true)
    {
        return $this->get_update_fields_for_table($this->get_core_table_name(), $use_skip_save_fields);
    }
    /**
     * Update this Object into the database, this function only works on
     * the main table, it will not save any dependend tables, which might
     * be loaded through oxlist (with exception of the active language set
     * table, which will be updated).
     *
     * @throws oxObjectException Throws on failure inserting
     *
     * @return bool
     */
    protected function update()
    {
        $ret = parent::update();
        if ($ret) {
            //also update multilang table if it is separate
            $update_tables = [];
            if ($this->_bl_employ_multilanguage) {
                $core_table = $this->get_core_table_name();
                $lang_table = get_lang_table_name($core_table, $this->get_language());
                if ($core_table != $lang_table) {
                    $update_tables[] = $lang_table;
                }
            } else {
                $update_tables = $this->get_language_set_tables();
            }
            foreach ($update_tables as $lang_table) {
                $insert_sql = "insert into {$lang_table} set " . $this->get_update_fields_for_table($lang_table, $this->get_use_skip_save_fields()) . ' on duplicate key update ' . $this->get_update_fields_for_table($lang_table);
                $this->execute_database_query($insert_sql);
            }
        }
        // currently only multilanguage objects are SEO
        // if current object is managed by SEO and SEO is ON
        if ($ret && $this->_bl_is_seo_object && $this->get_update_seo() && $this->is_admin()) {
            // marks all object db entries as expired
            \Oxid_Esales\Eshop\Core\Registry::get_seo_encoder()->mark_as_expired($this->get_id(), null, 1, $this->get_language());
        }
        return $ret;
    }
    /**
     * Return all DB tables for the language sets
     *
     * @param string $coreTableName core table name [optional]
     *
     * @return array
     */
    protected function get_language_set_tables($core_table_name = null)
    {
        $core_table_name = $core_table_name ?: $this->get_core_table_name();
        return ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class)->get_all_multi_tables($core_table_name);
    }
    /**
     * Insert this Object into the database, this function only works
     * on the main table, it will not save any dependend tables, which
     * might be loaded through oxlist.
     *
     * @return bool
     */
    protected function insert()
    {
        $result = parent::insert();
        if ($result) {
            //also insert to multilang tables if it is separate
            foreach ($this->get_language_set_tables() as $table) {
                $sql = "insert into {$table} set " . $this->get_update_fields_for_table($table, $this->get_use_skip_save_fields());
                $result = $result && (bool) $this->execute_database_query($sql);
            }
        }
        return $result;
    }
    /**
     * Returns actual object view or table name
     *
     * @param string $table  Original table name
     * @param int    $shopId Shop ID
     *
     * @return string
     */
    protected function get_object_view_name($table, $shop_id = null)
    {
        if (!$this->_bl_employ_multilanguage) {
            return parent::get_object_view_name($table, $shop_id);
        }
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        return $table_view_name_generator->get_view_name($table, $this->get_language(), $shop_id);
    }
    /**
     * Returns meta field or simple array of all object fields.
     * This method is slow and normally is called before field cache is built.
     * Make sure it is not called after first page is loaded and cache data is fully built (until tmp dir is cleaned).
     *
     * @param bool $returnSimple Set $returnSimple to true when you need simple array (meta data array is returned otherwise)
     *
     * @see \OxidEsales\Eshop\Core\Model\BaseModel::_getTableFields()
     *
     * @return array
     */
    protected function get_all_fields($return_simple = false)
    {
        if ($this->_bl_employ_multilanguage) {
            return parent::get_all_fields($return_simple);
        }
        $view_name = $this->get_view_name();
        if (!$view_name) {
            return [];
        }
        return $this->get_table_fields($view_name, $return_simple);
    }
    /**
     * Adds additional field to meta structure. Skips language fields
     *
     * @param string $name   Field name
     * @param string $status Field status (0-non multilang field, 1-multilang field)
     * @param string $type   Field type
     * @param string $length Field Length
     */
    protected function add_field($name, $status, $type = null, $length = null)
    {
        if ($this->_bl_employ_multilanguage && $this->get_field_lang($name)) {
            return;
        }
        return parent::add_field($name, $status, $type, $length);
    }
    /**
     * check if db field can be null
     * for multilingual fields it checks only the base fields as they may be
     * coming from outer join views, so oxbase would return that they always
     * support null (while in reality updates to their lang set table with null
     * would fail)
     *
     * @param string $fieldName db field name
     *
     * @return bool
     */
    protected function can_field_be_null($field_name)
    {
        $field_name = preg_replace('/_\d{1,2}$/', '', $field_name);
        return parent::can_field_be_null($field_name);
    }
    /**
     * Delete this object from the database, returns true on success.
     *
     * @param string $oxid Object ID(default null)
     *
     * @return bool
     */
    public function delete($oxid = null)
    {
        $deleted = parent::delete($oxid);
        if ($deleted) {
            $db = Database_Provider::get_db();
            //delete the record
            foreach ($this->get_language_set_tables() as $set_tbl) {
                $db->execute("delete from {$set_tbl} where oxid = :oxid", ['oxid' => $oxid]);
            }
        }
        return $deleted;
    }
}