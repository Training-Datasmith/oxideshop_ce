<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Model;

/**
 * Defining triggered action type.
 */
DEFINE('ACTION_NA', 0);
DEFINE('ACTION_DELETE', 1);
DEFINE('ACTION_INSERT', 2);
DEFINE('ACTION_UPDATE', 3);
DEFINE('ACTION_UPDATE_STOCK', 4);
use Exception;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Core\Exception\Database_Exception;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\After_Model_Delete_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\After_Model_Insert_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\After_Model_Update_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\Before_Model_Delete_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\Before_Model_Update_Event;
use Ox_Object_Exception;
/**
 * Class BaseModel
 * @package OxidEsales\EshopCommunity\Core\Model
 */
#[\Allow_Dynamic_Properties]
class Base_Model extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Unique object ID. Normally representing record oxid field value
     *
     * @var string
     */
    protected $_s_oxid;
    /**
     * ID of running shop session (default null).
     *
     * @var int
     */
    protected $_i_shop_id;
    /**
     * Whether instance
     *
     * @var bool
     */
    protected $_bl_is_simply_clonable = true;
    /**
     * Name of current class.
     *
     * @var string
     */
    protected $_s_class_name = 'oxbase';
    /**
     * Core database table name. $sCoreTable could be only original data table name and not view name.
     *
     * @var string
     */
    protected $_s_core_table;
    /**
     * Current view name where object record is supposed to be SELECTED from.
     *
     * @var string
     */
    protected $_s_view_table;
    /**
     * Field name list
     *
     * @var array
     */
    protected $_a_field_names = ['oxid' => 0];
    /**
     * Cache key. Assigned to object depending on active view. Is used for object caching identification in lazy loading mechanism.
     *
     * @var string
     */
    protected $_s_cache_key;
    /**
     * Set $_blUseLazyLoading to true if you want to load only actually used fields not full objet, depending on views.
     *
     * @var bool
     */
    protected $_bl_use_lazy_loading = false;
    /**
     * Field name array of ignored fields when doing record update() (eg. oxarticles__oxtime)
     *
     * @var array
     */
    protected $_a_skip_save_fields = ['oxtimestamp'];
    /**
     * Enable skip save fields usage
     *
     * @var bool
     */
    protected $_bl_use_skip_save_fields = true;
    /**
     * SQL query string for searching in DB (??)
     *
     * @var string
     */
    protected $_s_exist_key = 'oxid';
    /**
     * $_blIsDerived is set to true if object instance originally belongs to another shop (oxshopid is not curretn shop)
     * Use $this->isDerived() to access the value of this variable;
     *
     * @var bool
     */
    protected $_bl_is_derived;
    /**
     * Disables field cache when set to true.
     * This variable is used in case when lazy loading is performed on one object
     * in order to force other class instances to use lady loading as well.
     * So far this functionality is used in lists, when first element is lazy loaded, we must lazy load others as well
     * as otherwise we will get empty objects on the first load.
     *
     * @var bool
     */
    protected static $_bl_disable_field_caching = [];
    /**
     * Marks that current object is managed by SEO
     *
     * @var bool
     */
    protected $_bl_is_seo_object = false;
    /**
     * Flag allowing seo update for certain objects
     *
     * @var bool
     */
    protected $_bl_update_seo = true;
    /**
     * Read only for object
     *
     * @var bool
     */
    protected $_bl_read_only = false;
    /**
     * Indicates if the item is list element
     *
     * @var bool
     */
    protected $_bl_is_in_list = false;
    /**
     * Marks if object was loaded from db or not yet.
     *
     * @var bool
     */
    protected $_is_loaded = false;
    /**
     * store objects atributes values
     *
     * @var array
     */
    protected $_a_inner_lazy_cache;
    /**
     * Marker that multilanguage is OFF
     *
     * @var bool
     */
    protected $_bl_employ_multilanguage = false;
    /**
     * Getting use skip fields or not
     *
     * @return bool
     */
    public function get_use_skip_save_fields()
    {
        return $this->_bl_use_skip_save_fields;
    }
    /**
     * Setting use skip fields or not
     *
     * @param bool $useSkipSaveFields - true or false
     */
    public function set_use_skip_save_fields($use_skip_save_fields): void
    {
        $this->_bl_use_skip_save_fields = $use_skip_save_fields;
    }
    /**
     * Class constructor, sets active shop.
     */
    public function __construct()
    {
        // set active shop
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $this->_s_cache_key = $this->get_view_name();
        $this->add_skipped_save_fields_for_mapping();
        $this->disable_lazy_loading_for_caching();
        if ($this->_bl_use_lazy_loading) {
            $this->_s_cache_key .= $my_config->get_active_view()->get_class_key();
        } else {
            $this->_s_cache_key .= 'allviews';
        }
        //do not cache for admin?
        if ($this->is_admin()) {
            $this->_s_cache_key = null;
        }
        $this->set_shop_id($my_config->get_shop_id());
    }
    /**
     * Magic setter. If using lazy loading, adds setted field to fields array
     *
     * @param string $fieldName  name value
     * @param mixed  $fieldValue value
     */
    public function __set($field_name, $field_value)
    {
        $this->{$field_name} = $field_value;
        if ($this->_bl_use_lazy_loading && str_starts_with($field_name, $this->_s_core_table . '__')) {
            $prepared_field_name = str_replace($this->_s_core_table . '__', '', $field_name);
            if ($prepared_field_name !== 'oxnid' && (!isset($this->_a_field_names[$prepared_field_name]) || !$this->_a_field_names[$prepared_field_name])) {
                $all_fields_list = $this->get_all_fields(true);
                if (isset($all_fields_list[strtolower($prepared_field_name)])) {
                    $field_status = $this->get_field_status($prepared_field_name);
                    $this->add_field($prepared_field_name, $field_status);
                }
            }
        }
    }
    /**
     * Magic getter for older versions and template variables
     *
     * @param string $variableName variable name
     *
     * @return mixed
     */
    public function __get($variable_name)
    {
        switch ($variable_name) {
            case 'blIsDerived':
                return $this->is_derived();
            case 'sOXID':
                return $this->get_id();
            case 'blReadOnly':
                return $this->is_read_only();
        }
        // implementing lazy loading fields
        // This part of the code is slow and normally is called before field cache is built.
        // Make sure it is not called after first page is loaded and cache data is fully built.
        if ($this->_bl_use_lazy_loading && stripos($variable_name, $this->_s_core_table . '__') === 0) {
            if ($this->get_id()) {
                //lazy load it
                $field_name = str_replace($this->_s_core_table . '__', '', $variable_name);
                $cache_field_name = strtoupper($field_name);
                $field_status = $this->get_field_status($field_name);
                $view_name = $this->get_getter_view_name();
                $id = $this->get_id();
                try {
                    if ($this->_a_inner_lazy_cache === null) {
                        $database = Database_Provider::get_db();
                        $query = 'SELECT * FROM ' . $view_name . ' WHERE `oxid` = :oxid';
                        $query_result = $database->select($query, ['oxid' => $id]);
                        if ($query_result && $query_result->count()) {
                            $this->_a_inner_lazy_cache = array_change_key_case($query_result->fields, CASE_UPPER);
                            if (array_key_exists($cache_field_name, $this->_a_inner_lazy_cache)) {
                                $field_value = $this->_a_inner_lazy_cache[$cache_field_name];
                            } else {
                                return null;
                            }
                        } else {
                            return null;
                        }
                    } elseif (array_key_exists($cache_field_name, $this->_a_inner_lazy_cache)) {
                        $field_value = $this->_a_inner_lazy_cache[$cache_field_name];
                    } else {
                        return null;
                    }
                    $this->add_field($field_name, $field_status);
                    $this->set_field_data($field_name, $field_value);
                    //save names to cache for next loading
                    if ($this->_s_cache_key) {
                        $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
                        $cache_key = 'fieldnames_' . $this->_s_core_table . '_' . $this->_s_cache_key;
                        $field_names = $my_utils->from_file_cache($cache_key);
                        $field_names[$field_name] = $field_status;
                        $my_utils->to_file_cache($cache_key, $field_names);
                    }
                } catch (Exception) {
                    return null;
                }
                //do not use field cache for this page
                //as if we use it for lists then objects are loaded empty instead of lazy loading.
                self::$_bl_disable_field_caching[static::class] = true;
            }
            \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->reset_instance_cache(static::class);
        }
        //returns stdClass implementing __toString() method due to uknown scenario where this var should be used.
        if (!$this->is_property_loaded($variable_name)) {
            $this->{$variable_name} = null;
        }
        return $this->{$variable_name};
    }
    /**
     * Get view name for magic getter
     *
     * @return string
     */
    protected function get_getter_view_name()
    {
        return $this->get_view_name();
    }
    /**
     * Magic isset() handler. Workaround for detecting if protected properties are set.
     *
     * @param mixed $variableName Supplied class variable
     *
     * @return bool
     */
    public function __isset($variable_name)
    {
        if ($this->is_property_loaded($variable_name)) {
            return true;
        }
        return $this->is_property_field($variable_name);
    }
    /**
     * Magic function invoked on object cloning. Basically takes care about cloning properly DB fields.
     */
    public function __clone()
    {
        if (!$this->_bl_is_simply_clonable) {
            foreach ($this->_a_field_names as $field_name => $field_value) {
                $field_long_name = $this->get_field_long_name($field_name);
                if (is_object($this->{$field_long_name})) {
                    $this->{$field_long_name} = clone $this->{$field_long_name};
                }
            }
        }
    }
    /**
     * Clone this object - similar to Copy Constructor.
     *
     * @param object $object Object to copy
     */
    public function ox_clone($object): void
    {
        $class_variables = get_object_vars($object);
        foreach ($class_variables as $name => $value) {
            if (is_object($object->{$name})) {
                $this->{$name} = clone $object->{$name};
            } else {
                $this->{$name} = $object->{$name};
            }
        }
    }
    /**
     * Returns update seo flag
     *
     * @return boolean
     */
    public function get_update_seo()
    {
        return $this->_bl_update_seo;
    }
    /**
     * Sets update seo flag
     *
     * @param boolean $updateSeo
     */
    public function set_update_seo($update_seo): void
    {
        $this->_bl_update_seo = $update_seo;
    }
    /**
     * Checks whether certain field has changed, and sets update seo flag if needed.
     * It can only set the value to false, so it allows for multiple calls to the method,
     * and if atleast one requires seo update, other checks won't override that.
     *
     * @param string $fieldName Field name that will be checked
     */
    protected function set_update_seo_on_field_change($field_name)
    {
        if ($this->get_id() && in_array($field_name, $this->get_field_names())) {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $table_name = $this->get_core_table_name();
            $title = $database->get_one("select `{$field_name}` from `{$table_name}` where `oxid` = :oxid", ['oxid' => $this->get_id()]);
            $field_value = "{$table_name}__{$field_name}";
            $current_time = $this->{$field_value}->value;
            if ($title == $current_time) {
                $this->set_update_seo(false);
            }
        }
    }
    /**
     * Sets the names to main and view tables, loads metadata of each table.
     *
     * @param string $tableName      Name of DB object table
     * @param bool   $forceAllFields Forces initialisation of all fields overriding lazy loading functionality
     */
    public function init($table_name = null, $force_all_fields = false): void
    {
        if ($table_name) {
            $this->_s_core_table = $table_name;
        }
        // reset view table
        $this->_s_view_table = false;
        if (count($this->_a_field_names) <= 1) {
            $this->init_data_structure($force_all_fields);
        }
    }
    /**
     * Assigns DB field values to object fields. Returns true on success.
     *
     * @param array $dbRecord Associative data values array
     */
    public function assign($db_record): void
    {
        if (!is_array($db_record)) {
            return;
        }
        foreach ($db_record as $name => $value) {
            $this->set_field_data($name, $value);
        }
        $oxid_field = $this->get_field_long_name('oxid');
        if ($this->{$oxid_field} instanceof Field) {
            $this->_s_oxid = $this->{$oxid_field}->value;
        }
    }
    /**
     * Returns object class name
     *
     * @return string
     */
    public function get_class_name()
    {
        return $this->_s_class_name;
    }
    /**
     * Return object core table name
     *
     * @return string
     */
    public function get_core_table_name()
    {
        return $this->_s_core_table;
    }
    /**
     * Returns unique object id.
     *
     * @return string
     */
    public function get_id()
    {
        return $this->_s_oxid;
    }
    /**
     * Sets unique object id
     *
     * @param string $oxid Record ID
     *
     * @return string
     */
    public function set_id($oxid = null)
    {
        if ($oxid) {
            $this->_s_oxid = $oxid;
        } else if ($this->get_core_table_name() == 'oxobject2category') {
            $object_id = $this->oxobject2category__oxobjectid;
            $category_id = $this->oxobject2category__oxcatnid;
            $shop_id = $this->oxobject2category__oxshopid;
            $this->_s_oxid = md5($object_id . $category_id . $shop_id);
        } else {
            $this->_s_oxid = \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->generate_uid();
        }
        $id_field_name = $this->get_core_table_name() . '__oxid';
        $this->{$id_field_name} = new Field($this->_s_oxid, Field::T_RAW);
        return $this->_s_oxid;
    }
    /**
     * Sets original object shop ID
     *
     * @param int $shopId New shop ID
     */
    public function set_shop_id($shop_id): void
    {
        $this->_i_shop_id = $shop_id;
    }
    /**
     * Return original object shop id
     *
     * @return int
     */
    public function get_shop_id()
    {
        return $this->_i_shop_id;
    }
    /**
     * Returns main table data is actually selected from (could be a view name as well)
     *
     * @param bool $forceCoreTableUsage (optional) use core views
     *
     * @return string
     */
    public function get_view_name($force_core_table_usage = null)
    {
        if (!$this->_s_view_table || $force_core_table_usage !== null) {
            if ($force_core_table_usage === true) {
                return $this->get_core_table_name();
            }
            $force_core_table_usage = $this->check_if_core_table_needed($force_core_table_usage);
            if ($force_core_table_usage !== null && $force_core_table_usage) {
                $shop_id = -1;
            } else {
                $shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
            }
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $view_name = $table_view_name_generator->get_view_name($this->get_core_table_name(), $this->_bl_employ_multilanguage == false ? -1 : $this->get_language(), $shop_id);
            if ($force_core_table_usage !== null) {
                return $view_name;
            }
            $this->_s_view_table = $view_name;
        }
        return $this->_s_view_table;
    }
    /**
     * Additional check if core table name should be returned in getViewName
     *
     * @param mixed $forceCoreTableUsage
     * @return mixed
     */
    protected function check_if_core_table_needed($force_core_table_usage)
    {
        return $force_core_table_usage;
    }
    /**
     * Lazy loading cache key modifier.
     *
     * @param string $cacheKey Cache  key
     * @param bool   $override Marker to force override cache key
     */
    public function modify_cache_key($cache_key, $override = false): void
    {
        if ($override) {
            $this->_s_cache_key = $cache_key;
        } else {
            $this->_s_cache_key .= $cache_key;
        }
    }
    /**
     * Disables lazy loading mechanism and init object fully
     */
    public function disable_lazy_loading(): void
    {
        $this->_bl_use_lazy_loading = false;
        $this->init_data_structure(true);
    }
    /**
     * Returns true in case the item represented by this object is derived from parent shop
     *
     * @return bool
     */
    public function is_derived()
    {
        return $this->_bl_is_derived;
    }
    /**
     * Returns true in case the item represented by this object is derived from parent shop
     *
     * @param bool $value if derived
     */
    public function set_is_derived($value): void
    {
        $this->_bl_is_derived = $value;
    }
    /**
     * Returns true, if object has multi language fields (if object is derived from oxi18n class).
     * In oxBase it is always returns false, as oxBase treats all fields as non multi language.
     *
     * @return bool
     */
    public function is_multi_lang()
    {
        return false;
    }
    /**
     * Loads object data from DB (object data ID is passed to method). Returns
     * true on success.
     * could throw oxObjectException F ?
     *
     * @param string $oxid Object ID
     *
     * @return bool
     */
    public function load($oxid)
    {
        //getting at least one field before lazy loading the object
        $this->add_field('oxid', 0);
        $query = $this->build_select_string([$this->get_view_name() . '.oxid' => $oxid]);
        $this->_is_loaded = $this->assign_record($query);
        return $this->_is_loaded;
    }
    /**
     * Returns object "loaded" state
     *
     * @return bool
     */
    public function is_loaded()
    {
        return $this->_is_loaded;
    }
    /**
     * Builds and returns SQL query string.
     *
     * @param null|array $whereCondition SQL select WHERE conditions array (default false)
     *
     * @return string
     */
    public function build_select_string($where_condition = null)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $get = $this->get_select_fields();
        $query = "select {$get} from " . $this->get_view_name() . ' where 1 ';
        if ($where_condition) {
            reset($where_condition);
            foreach ($where_condition as $name => $value) {
                $query .= ' and ' . $name . ' = ' . $database->quote($value ?? '');
            }
        }
        return $query;
    }
    /**
     * Performs SQL query, assigns record field values to object. Returns true on success.
     *
     * @param string $select SQL statement
     *
     * @return bool
     * @deprecated method will be removed from the public API in v7.0 use QueryBuilderFactoryInterface and assign()
     * @see BaseModel::assign()
     * @see \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
     */
    protected function assign_record($select)
    {
        $record = $this->get_record_by_query($select);
        if ($record != false && $record->count() > 0) {
            $this->assign($record->fields);
            return true;
        }
        return false;
    }
    /**
     * Get record
     *
     * @param string $query
     *
     * @return mixed
     * @deprecated method will be removed from the public API in v7.0 use QueryBuilderFactoryInterface
     * @see \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
     */
    protected function get_record_by_query($query)
    {
        return Database_Provider::get_db()->select($query);
    }
    /**
     * Gets field data
     *
     * @param string $fieldName name (eg. 'oxtitle') of a data field to get
     *
     * @return mixed value of a data field
     */
    public function get_field_data($field_name)
    {
        return ($field = $this->get_field_if_available($field_name)) ? $field->value : null;
    }
    /**
     * Gets raw field data
     *
     * @param string $fieldName name (eg. 'oxtitle') of a data field to get
     *
     * @return mixed value of a data field
     *
     */
    public function get_raw_field_data($field_name)
    {
        return ($field = $this->get_field_if_available($field_name)) ? $field->raw_value : null;
    }
    private function get_field_if_available($field_name): ?Field
    {
        $long_field_name = $this->get_field_long_name($field_name);
        return $this->{$long_field_name} instanceof Field ? $this->{$long_field_name} : null;
    }
    /**
     * Function builds the field list used in select.
     *
     * @param bool $forceCoreTableUsage (optional) use core views
     *
     * @return string
     */
    public function get_select_fields($force_core_table_usage = null)
    {
        $select_fields = [];
        $view_name = $this->get_view_name($force_core_table_usage);
        foreach ($this->_a_field_names as $key => $field) {
            if ($view_name) {
                $select_fields[] = "`{$view_name}`.`{$key}`";
            } else {
                $select_fields[] = ".`{$key}`";
            }
        }
        return implode(', ', $select_fields);
    }
    /**
     * Delete this object from the database, returns true if entry was deleted.
     *
     * @param string $oxid Object ID(default null)
     *
     * @return bool
     */
    public function delete($oxid = null)
    {
        $oxid = $oxid ?: $this->get_id();
        if (!$oxid || !$this->allow_derived_delete()) {
            return false;
        }
        Container_Facade::dispatch(new Before_Model_Delete_Event($this));
        $this->remove_element2shop_relations($oxid);
        $database = Database_Provider::get_db();
        $core_table = $this->get_core_table_name();
        $delete_query = "delete from {$core_table} where oxid = :oxid";
        $affected_rows = $database->execute($delete_query, ['oxid' => $oxid]);
        if ($bl_delete = (bool) $affected_rows) {
            $this->on_change(ACTION_DELETE, $oxid);
        }
        return $bl_delete;
    }
    /**
     * Removes relevant mapping data for selected object if it is a multishop inheritable table
     *
     * @param string $oxid Object ID
     */
    protected function remove_element2shop_relations($oxid)
    {
    }
    /**
     * Save this Object to database, insert or update as needed.
     *
     * @throws Exception
     *
     * @return string|bool
     */
    public function save()
    {
        if (!is_array($this->_a_field_names)) {
            return false;
        }
        // #739A - should be executed here because of date/time formatting feature
        if ($this->is_admin() && !\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blSkipFormatConversion')) {
            foreach ($this->_a_field_names as $name => $value) {
                $long_name = $this->get_field_long_name($name);
                if (isset($this->{$long_name}->fldtype) && $this->{$long_name}->fldtype == 'datetime') {
                    \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->convert_db_date_time($this->{$long_name}, true);
                } elseif (isset($this->{$long_name}->fldtype) && $this->{$long_name}->fldtype == 'timestamp') {
                    \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->convert_db_timestamp($this->{$long_name}, true);
                } elseif (isset($this->{$long_name}->fldtype) && $this->{$long_name}->fldtype == 'date') {
                    \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->convert_db_date($this->{$long_name}, true);
                }
            }
        }
        $return = false;
        $action = null;
        $response = null;
        /** We must check on the master database, if an entry exists, so we switch to master connection.*/
        \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        if ($this->exists()) {
            //only update if derived update is allowed
            if ($this->allow_derived_update()) {
                $response = $this->update();
                $action = ACTION_UPDATE;
            }
        } else {
            $response = $this->insert();
            $action = ACTION_INSERT;
            Container_Facade::dispatch(new After_Model_Insert_Event($this));
        }
        $this->on_change($action);
        if ($response) {
            return $this->get_id();
        }
        return $return;
    }
    /**
     * Checks if derived update is allowed (calls \OxidEsales\Eshop\Core\Model\BaseModel::isDerived)
     *
     * @return bool
     */
    public function allow_derived_update()
    {
        return !$this->is_derived();
    }
    /**
     * Checks if derived delete is allowed (calls \OxidEsales\Eshop\Core\Model\BaseModel::isDerived)
     *
     * @return bool
     */
    public function allow_derived_delete()
    {
        return !$this->is_derived();
    }
    /**
     * Checks if this object exists, returns true on success.
     *
     * @param string $oxid Object ID(default null)
     *
     * @return bool
     */
    public function exists($oxid = null)
    {
        if (!$oxid) {
            $oxid = $this->get_id();
        }
        if (!$oxid) {
            return false;
        }
        $view_name = $this->get_core_table_name();
        $database = Database_Provider::get_db();
        $query = "select {$this->_s_exist_key} from {$view_name} where {$this->_s_exist_key} = :oxid";
        return (bool) $database->get_one($query, ['oxid' => $oxid]);
    }
    /**
     * Returns SQL select string with checks if items are available
     *
     * @param bool $forceCoreTable forces core table usage (optional)
     *
     * @return string
     */
    public function get_sql_active_snippet($force_core_table = null)
    {
        $query = '';
        $table_name = $this->get_view_name($force_core_table);
        // has 'active' field ?
        if (isset($this->_a_field_names['oxactive'])) {
            $query = " {$table_name}.oxactive = 1 ";
        }
        // has 'activefrom'/'activeto' fields ?
        if (isset($this->_a_field_names['oxactivefrom']) && isset($this->_a_field_names['oxactiveto'])) {
            return $this->add_sql_active_range_snippet($query, $table_name);
        }
        return $query;
    }
    /**
     * This function is triggered before the record is updated.
     * If you make any update to the database record manually you should also call beforeUpdate() from your script.
     *
     * @param string $oxid Object ID(default null). Pass the ID in case object is not loaded.
     */
    public function before_update($oxid = null): void
    {
        Container_Facade::dispatch(new Before_Model_Update_Event($this));
    }
    /**
     * This function is triggered whenever the object is saved or deleted.
     * onChange() is triggered after saving the changes in Save() method, after deleting the instance from the database.
     * If you make any change to the database record manually you should also call onChange() from your script.
     *
     * @param int    $action Action identifier.
     * @param string $oxid   Object ID(default null). Pass the ID in case object is not loaded.
     */
    public function on_change($action = null, $oxid = null): void
    {
        if (ACTION_DELETE == $action) {
            Container_Facade::dispatch(new After_Model_Delete_Event($this));
        } else {
            Container_Facade::dispatch(new After_Model_Update_Event($this));
        }
    }
    /**
     * Sets item as list element
     */
    public function set_in_list(): void
    {
        $this->_bl_is_in_list = true;
    }
    /**
     * Checks if this instance is one of oxList elements.
     *
     * @return bool
     */
    protected function is_in_list()
    {
        return $this->_bl_is_in_list;
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
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        return $table_view_name_generator->get_view_name($table, -1, $shop_id);
    }
    /**
     * Returns meta field or simple array of all object fields.
     * This method is slow and normally is called before field cache is built.
     * Make sure it is not called after first page is loaded and cache data is fully built (until tmp dir is cleaned).
     *
     * @param string $table             Table name
     * @param bool $returnSimpleArray Set $returnSimple to true when you need simple array
     *                                (meta data array is returned otherwise)
     *
     * @return array
     */
    protected function get_table_fields($table, $return_simple_array = false)
    {
        $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        $cache_key = $table . '_allfields_' . $return_simple_array;
        $meta_fields = $my_utils->from_file_cache($cache_key);
        if ($meta_fields) {
            return $meta_fields;
        }
        $meta_fields = \Oxid_Esales\Eshop\Core\Database_Provider::get_instance()->get_table_description($table);
        if (!$return_simple_array) {
            $my_utils->to_file_cache($cache_key, $meta_fields);
            return $meta_fields;
        }
        //returning simple array
        $result = [];
        if (is_array($meta_fields)) {
            foreach ($meta_fields as $value_object) {
                $result[strtolower((string) $value_object->name)] = 0;
            }
        }
        $my_utils->to_file_cache($cache_key, $result);
        return $result;
    }
    /**
     * Returns meta field or simple array of all object fields.
     * This method is slow and normally is called before field cache is built.
     * Make sure it is not called after first page is loaded and cache data is fully built (until tmp dir is cleaned).
     *
     * @param bool $returnSimple Set $blReturnSimple to true when you need simple array
     *                           (meta data array is returned otherwise)
     *
     * @return array
     * @see \OxidEsales\Eshop\Core\Model\BaseModel::_getTableFields()
     *
     */
    protected function get_all_fields($return_simple = false)
    {
        if (!$this->get_core_table_name()) {
            return [];
        }
        return $this->get_table_fields($this->get_core_table_name(), $return_simple);
    }
    /**
     * Initializes object data structure.
     * Either by trying to load from cache or by calling $this->_getNonCachedFieldNames
     *
     * @param bool $forceFullStructure Set to true if you want to load full structure in any case.
     */
    protected function init_data_structure($force_full_structure = false)
    {
        $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        //get field names from cache
        $field_names_list = null;
        $full_cache_key = 'fieldnames_' . $this->get_core_table_name() . '_' . $this->_s_cache_key;
        if ($this->_s_cache_key && !$this->is_disabled_field_cache()) {
            $field_names_list = $my_utils->from_file_cache($full_cache_key);
        }
        if (!$field_names_list) {
            $field_names_list = $this->get_non_cached_field_names($force_full_structure);
            if ($this->_s_cache_key && !$this->is_disabled_field_cache()) {
                $my_utils->to_file_cache($full_cache_key, $field_names_list);
            }
        }
        if ($field_names_list !== false) {
            foreach ($field_names_list as $field => $status) {
                $this->add_field($field, $status);
            }
        }
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
     * @return array|bool
     */
    protected function get_non_cached_field_names($force_full_structure = false)
    {
        //T2008-02-22
        //so if this method is executed on cached version we see it when profiling
        start_profile('!__CACHABLE__!');
        //case 1. (admin)
        if ($this->is_admin()) {
            $meta_fields = $this->get_all_fields();
            foreach ($meta_fields as $one_field) {
                if ($one_field->max_length == -1) {
                    $one_field->max_length = 10;
                    // double or float
                }
                if ($one_field->type == 'datetime') {
                    $one_field->max_length = 20;
                }
                $this->add_field($one_field->name, $this->get_field_status($one_field->name), $one_field->type, $one_field->max_length);
            }
            stop_profile('!__CACHABLE__!');
            return false;
        }
        //case 2. (just get all fields)
        if ($force_full_structure || !$this->_bl_use_lazy_loading) {
            $meta_fields = $this->get_all_fields(true);
            stop_profile('!__CACHABLE__!');
            return $meta_fields;
        }
        //case 3. (get only oxid field, so we can fetch the rest of the fields over lazy loading mechanism)
        stop_profile('!__CACHABLE__!');
        return ['oxid' => 0];
    }
    /**
     * Returns _aFieldName[] value. 0 means - non multi language, 1 - multi language field.
     * But this is defined only in derived oxi18n class.
     * In oxBase it is always 0, as oxBase treats all fields as non multi language.
     *
     * @param string $fieldName Field name
     *
     * @return int
     */
    protected function get_field_status($field_name)
    {
        return 0;
    }
    /**
     * Adds additional field to meta structure
     *
     * @param string $fieldName   Field name
     * @param int    $fieldStatus Field name status. In derived classes it indicates multi language status.
     * @param string $type        Field type
     * @param string $length      Field Length
     */
    protected function add_field($field_name, $field_status, $type = null, $length = null)
    {
        //preparation
        $field_name = strtolower($field_name);
        //adding field names element
        $this->_a_field_names[$field_name] = $field_status;
        //already set?
        $field_long_name = $this->get_field_long_name($field_name);
        if ($this->is_property_loaded($field_long_name)) {
            return;
        }
        //defining the field
        $field = false;
        if (isset($type)) {
            $field = new Field();
            $field->fldtype = $type;
            //T2008-01-29
            //can't clone as the fields are objects and are not fully cloned
            $this->_bl_is_simply_clonable = false;
        }
        if (isset($length)) {
            if (!$field) {
                $field = new Field();
            }
            $field->fldmax_length = $length;
            $this->_bl_is_simply_clonable = false;
        }
        $this->{$field_long_name} = $field;
    }
    /**
     * Returns long field name in "<table>__<field_name>" format.
     *
     * @param string $fieldName Short field name
     *
     * @return string
     */
    protected function get_field_long_name($field_name)
    {
        //trying to avoid strpos call as often as possible
        $core_table_name = $this->get_core_table_name();
        if ($field_name[2] == $core_table_name[2] && str_starts_with($field_name, $core_table_name . '__')) {
            return $field_name;
        }
        return $core_table_name . '__' . strtolower($field_name);
    }
    /**
     * Sets data field value
     *
     * @param string $fieldName  Index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $fieldValue Value of data field
     * @param int    $dataType   Field type
     */
    protected function set_field_data($field_name, $field_value, $data_type = Field::T_TEXT)
    {
        $long_field_name = $this->get_field_long_name($field_name);
        //$sLongFieldName = $this->_sCoreTable . "__" . strtolower($sFieldName);
        // doing this because in lazy loaded lists on first load it would be harmful
        // to have the fields initialised without setting values
        // situation: only first article is loaded fully for "select oxid from oxarticles"
        //if ($this->_blUseLazyLoading && !isset($this->$sLongFieldName))
        //    return;
        //in non lazy loading case we just add a field and do not care about it more
        if (!$this->_bl_use_lazy_loading && !$this->is_property_loaded($long_field_name)) {
            $fields_list = $this->get_all_fields(true);
            if (isset($fields_list[strtolower($field_name)])) {
                $this->add_field($field_name, $this->get_field_status($field_name));
            }
        }
        // if we have a double field we replace "," with "." in case somebody enters it in european format
        $is_property_loaded = $this->is_property_loaded($long_field_name);
        if ($is_property_loaded && isset($this->{$long_field_name}->fldtype) && $this->{$long_field_name}->fldtype == 'double') {
            $field_value = str_replace(',', '.', $field_value);
        }
        // isset is REQUIRED here not to use getter
        if ($is_property_loaded && is_object($this->{$long_field_name})) {
            $this->{$long_field_name}->set_value($field_value, $data_type);
        } else {
            $this->{$long_field_name} = new Field($field_value, $data_type);
        }
    }
    /**
     * check if db field can be null
     *
     * @param string $fieldName db field name
     *
     * @return bool
     */
    protected function can_field_be_null($field_name)
    {
        $meta_data = $this->get_all_fields();
        foreach ($meta_data as $meta_info) {
            if (strcasecmp((string) $meta_info->name, $field_name) == 0) {
                return !$meta_info->not_null;
            }
        }
        return false;
    }
    /**
     * returns default field value
     *
     * @param string $fieldName db field name
     *
     * @return mixed
     */
    protected function get_field_default_value($field_name)
    {
        $meta_data = $this->get_all_fields();
        foreach ($meta_data as $meta_info) {
            if (strcasecmp((string) $meta_info->name, $field_name) == 0) {
                return property_exists($meta_info, 'default_value') ? $meta_info->default_value : null;
            }
        }
        return false;
    }
    /**
     * returns quoted field value for using in update statement
     *
     * @param string $fieldName name of field
     * @param Field  $field     field object
     *
     * @return string
     */
    protected function get_update_field_value($field_name, $field)
    {
        $field_value = null;
        if ($field instanceof Field) {
            $field_value = $field->get_raw_value();
        } elseif (isset($field->value)) {
            $field_value = $field->value;
        }
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        //Check if this field value is null AND it can be null according if not returning default value
        if (null === $field_value) {
            if ($this->can_field_be_null($field_name)) {
                return 'null';
            }
            if ($field_value = $this->get_field_default_value($field_name)) {
                return $database->quote($field_value);
            }
        }
        return $database->quote($field_value ?? '');
    }
    /**
     * Get object fields sql part used for updates or inserts:
     * return e.g.  fldName1 = 'value1',fldName2 = 'value2'...
     *
     * @param bool $useSkipSaveFields forces usage of skip save fields array (default is true)
     *
     * @return string
     */
    protected function get_update_fields($use_skip_save_fields = true)
    {
        $query = '';
        $separator = '';
        foreach (array_keys($this->_a_field_names) as $one_field_name) {
            $long_name = $this->get_field_long_name($one_field_name);
            $field = $this->{$long_name};
            if (!$this->check_field_can_be_updated($one_field_name)) {
                continue;
            }
            if (!$use_skip_save_fields || $use_skip_save_fields && !in_array(strtolower((string) $one_field_name), $this->_a_skip_save_fields)) {
                $query .= $separator . $one_field_name . ' = ' . $this->get_update_field_value($one_field_name, $field);
                $separator = ',';
            }
        }
        return $query;
    }
    /**
     * If needed, check if field can be updated
     *
     * @param string $fieldName
     *
     * @return bool
     */
    protected function check_field_can_be_updated($field_name)
    {
        return true;
    }
    /**
     * Update this Object into the database, this function only works on
     * the main table, it will not save any dependent tables, which might
     * be loaded through oxList.
     *
     * @throws oxObjectException Throws on failure inserting
     * @throws DatabaseException On database errors
     *
     * @return bool Will always return true. On failure an exception is thrown.
     */
    protected function update()
    {
        //do not allow derived item update
        if (!$this->allow_derived_update()) {
            return false;
        }
        if (!$this->get_id()) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Object_Exception::class);
            $exception->set_message('EXCEPTION_OBJECT_OXIDNOTSET');
            $exception->set_object($this);
            throw $exception;
        }
        $core_table_name = $this->get_core_table_name();
        $id_key = \Oxid_Esales\Eshop\Core\Registry::get_utils()->get_arr_fld_name($core_table_name . '.oxid');
        $this->{$id_key} = new Field($this->get_id(), Field::T_RAW);
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $update_query = "update {$core_table_name} set " . $this->get_update_fields() . " where {$core_table_name}.oxid = " . $database->quote($this->get_id());
        $this->before_update();
        $this->execute_database_query($update_query);
        return true;
    }
    /**
     * Execute a query on the database.
     *
     * @param string $query The command to execute on the database.
     * @param array  $params Parameters to fill the querry
     *
     * @return int The number of affected rows.
     */
    protected function execute_database_query($query, $params = [])
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        return $database->execute($query, $params);
    }
    /**
     * Insert this Object into the database, this function only works
     * on the main table, it will not save any dependent tables, which
     * might be loaded through oxlist.
     *
     * @return bool
     */
    protected function insert()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        // let's get a new ID
        if (!$this->get_id()) {
            $this->set_id();
        }
        $id_key = $my_utils->get_arr_fld_name($this->get_core_table_name() . '.oxid');
        $this->{$id_key} = new Field($this->get_id(), Field::T_RAW);
        $insert_sql = "Insert into {$this->get_core_table_name()} set ";
        $shop_id_field = $my_utils->get_arr_fld_name($this->get_core_table_name() . '.oxshopid');
        if ($this->is_property_loaded($shop_id_field) && (!$this->is_property_field($shop_id_field) || !$this->{$shop_id_field}->value)) {
            $this->{$shop_id_field} = new Field($my_config->get_shop_id(), Field::T_RAW);
        }
        $insert_sql .= $this->get_update_fields($this->get_use_skip_save_fields());
        return $result = (bool) $this->execute_database_query($insert_sql);
    }
    /**
     * Checks if current class disables field caching.
     * This method is primary used in unit tests.
     *
     * @return bool
     */
    protected function is_disabled_field_cache()
    {
        $class = static::class;
        if (isset(self::$_bl_disable_field_caching[$class]) && self::$_bl_disable_field_caching[$class]) {
            return true;
        }
        return false;
    }
    /**
     * Add additional fields to skipped save fields
     */
    protected function add_skipped_save_fields_for_mapping()
    {
    }
    /**
     * Disable lazy loading if cache is enabled
     */
    protected function disable_lazy_loading_for_caching()
    {
    }
    /**
     * Checks if object ID's first two chars are 'o' and 'x'. Returns true or false
     *
     * @return bool
     */
    public function is_ox()
    {
        $oxid = $this->get_id();
        if (is_string($oxid) && $oxid[0] == 'o' && $oxid[1] == 'x') {
            return true;
        }
        return false;
    }
    /**
     * Is object readonly
     *
     * @return bool
     */
    public function is_read_only()
    {
        return $this->_bl_read_only;
    }
    /**
     * Set object readonly
     *
     * @param bool $readOnly readonly flag
     */
    public function set_read_only($read_only): void
    {
        $this->_bl_read_only = $read_only;
    }
    /**
     * Returns array with object field names
     *
     * @return array
     */
    public function get_field_names()
    {
        $field_names = $this->_a_field_names;
        if (!$this->is_admin() && $this->_bl_use_lazy_loading) {
            $field_names = $this->get_non_cached_field_names(true);
        }
        return array_keys($field_names);
    }
    /**
     * Adds additional field name to meta structure
     *
     * @param string $name Field name
     */
    public function add_field_name($name): void
    {
        //preparation
        $name = strtolower($name);
        $this->_a_field_names[$name] = 0;
    }
    /**
     * Returns -1, means object is not multi language
     *
     * @return int
     */
    public function get_language()
    {
        return -1;
    }
    /**
     * Returns true if the property is loaded.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function is_property_loaded($name)
    {
        return property_exists($this, $name) && isset($this->{$name});
    }
    /**
     * adds and activefrom/activeto to the query
     *
     * @param string $query
     * @param string $tableName
     *
     * @return string
     */
    protected function add_sql_active_range_snippet($query, $table_name)
    {
        $date_obj = \Oxid_Esales\Eshop\Core\Registry::get_utils_date();
        $seconds_to_round_for_query_cache = $this->get_seconds_to_round_for_query_cache();
        $database_formatted_date = $date_obj->get_rounded_request_date_db_formatted($seconds_to_round_for_query_cache);
        $query = $query ? " {$query} or " : '';
        return " ( {$query} ( {$table_name}.oxactivefrom < '{$database_formatted_date}' and {$table_name}.oxactiveto > '{$database_formatted_date}' ) ) ";
        // phpcs:ignore
    }
    /**
     *  Return a number of seconds used to define a interval for rounding timestamps
     *  e.g. this method returns the value 60 then it means timestamps should be rounded to full minutes
     *  so the query may get an cache hit because it can be stable for an interval of one minute
     *
     *  it is a own method to allow overriding in child classes
     *  @return int the amount of seconds
     */
    protected function get_seconds_to_round_for_query_cache()
    {
        //set default value cache time to 60 seconds
        //because active from setting is based on minutes
        return 60;
    }
    /**
     * Returns true if the property is a Field.
     *
     * @param  string $name
     */
    private function is_property_field($name): bool
    {
        return $this->{$name} instanceof Field;
    }
}