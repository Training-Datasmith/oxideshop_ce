<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

use Exception;
use Oxid_Esales\Eshop\Core\Generic_Import\Generic_Import;
use Oxid_Esales\Eshop\Core\Model\Base_Model;
use Oxid_Esales\Eshop\Core\Model\Multi_Language_Model;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
abstract class Import_Object
{
    /** @var string Database table name. */
    protected $table_name;
    /** @var array List of database fields, to which data should be imported. */
    protected $field_list;
    /** @var array List of database key fields (i.e. oxid). */
    protected $key_field_list;
    /** @var string Shop object name. */
    protected $shop_object_name;
    /**
     * Getter for _sTableName
     *
     * @return string
     */
    public function get_base_table_name()
    {
        return $this->table_name;
    }
    /**
     * setter for field list
     *
     * @param array $aFieldList fields to set
     */
    public function set_field_list($a_field_list): void
    {
        $this->field_list = $a_field_list;
    }
    /**
     * Basic access check for writing data, checks for same shopId, should be overridden if field oxshopid does not
     * exist.
     *
     * @param BaseModel $shopObject Loaded shop object.
     * @param array                                  $data       Fields to be written, null for default.
     *
     * @throws Exception on now access
     */
    public function check_write_access($shop_object, $data = null): void
    {
        if ($shop_object->is_derived()) {
            throw new Exception(Generic_Import::ERROR_USER_NO_RIGHTS);
        }
    }
    /**
     * Basic access check for creating new objects
     *
     * @param array $data fields to be written
     *
     * @throws Exception on now access
     */
    public function check_create_access($data)
    {
    }
    /**
     * Insert or Update a Row into database.
     *
     * @param array $data Assoc. array with field names, values what should be stored in this table.
     *
     * @return string|false
     */
    public function import($data)
    {
        return $this->save_object($data, false);
    }
    /**
     * Used for the RR implementation.
     *
     * @return array
     */
    public function get_right_fields()
    {
        $access_right_fields = [];
        if (!$this->field_list) {
            $this->get_field_list();
        }
        foreach ($this->field_list as $field) {
            $access_right_fields[] = strtolower($this->table_name . '__' . $field);
        }
        return $access_right_fields;
    }
    /**
     * Returns the predefined field list.
     *
     * @return array
     */
    public function get_field_list()
    {
        $shop_object = $this->create_shop_object();
        $view_name = $shop_object->get_view_name();
        $fields = str_ireplace("`{$view_name}`.", '', strtoupper((string) $shop_object->get_select_fields()));
        $fields = str_ireplace([' ', '`'], ['', ''], $fields);
        $this->field_list = explode(',', $fields);
        return $this->field_list;
    }
    /**
     * Returns the keylist array.
     *
     * @return array
     */
    public function get_key_fields()
    {
        return $this->key_field_list;
    }
    /**
     * Getter for _sShopObjectName.
     *
     * @return string
     */
    protected function get_shop_object_name()
    {
        return $this->shop_object_name;
    }
    /**
     * Returns table or View name.
     *
     * @return string
     */
    protected function get_table_name()
    {
        $shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        return $table_view_name_generator->get_view_name($this->table_name, -1, $shop_id);
    }
    /**
     * Issued before saving an object. can modify aData for saving.
     *
     * @param BaseModel $shopObject        shop object
     * @param array                                  $data              data to prepare
     * @param bool                                   $allowCustomShopId if allow custom shop id
     *
     * @return array
     */
    protected function pre_assign_object($shop_object, array $data, $allow_custom_shop_id)
    {
        if (isset($data['OXSHOPID'])) {
            $data['OXSHOPID'] = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        }
        if (!isset($data['OXID'])) {
            $data['OXID'] = $this->get_oxid_from_key_fields($data);
        }
        // null values support
        foreach ($data as $key => $val) {
            if (!strlen((string) $val)) {
                // oxBase will quote it as string if db does not support null for this field
                $data[$key] = null;
            }
        }
        return $data;
    }
    /**
     * Prepares object for saving in shop.
     * Returns true if save can proceed further.
     *
     * @param BaseModel $shopObject Shop object.
     * @param array                                  $data       Data for importing.
     *
     * @return boolean
     */
    protected function pre_save_object($shop_object, $data)
    {
        return true;
    }
    /**
     * Saves object data.
     *
     * @param array $data              data for saving
     * @param bool  $allowCustomShopId allow custom shop id
     *
     * @return string|false
     */
    protected function save_object($data, $allow_custom_shop_id)
    {
        $shop_object = $this->create_shop_object();
        foreach ($data as $key => $value) {
            // change case to UPPER
            $uppercase_key = strtoupper((string) $key);
            if (!isset($data[$uppercase_key])) {
                unset($data[$key]);
                $data[$uppercase_key] = $value;
            }
        }
        if (method_exists($shop_object, 'setForceCoreTableUsage')) {
            $shop_object->set_force_core_table_usage(true);
        }
        $is_loaded = false;
        if ($data['OXID']) {
            $is_loaded = $shop_object->load($data['OXID']);
        }
        $data = $this->pre_assign_object($shop_object, $data, $allow_custom_shop_id);
        if ($is_loaded) {
            $this->check_write_access($shop_object, $data);
        } else {
            $this->check_create_access($data);
        }
        $shop_object->assign($data);
        if ($allow_custom_shop_id) {
            $shop_object->set_is_derived(false);
        }
        if ($this->pre_save_object($shop_object, $data)) {
            // store
            if ($shop_object->save()) {
                return $this->post_save_object($shop_object, $data);
            }
        }
        return false;
    }
    /**
     * Post saving hook. can finish transactions if needed or adjust related data.
     *
     * @param BaseModel $shopObject Shop object.
     * @param array                                  $data       Data to save.
     *
     * @return mixed data to return
     */
    protected function post_save_object($shop_object, $data)
    {
        // returning ID on success
        return $shop_object->get_id();
    }
    /**
     * Returns oxid of this data type from key fields.
     *
     * @param array $data Data for object.
     *
     * @return string
     */
    protected function get_oxid_from_key_fields(array $data)
    {
        if (!is_array($this->get_key_fields())) {
            return null;
        }
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $query_where_part = [];
        $all_keys_exists = true;
        foreach ($this->get_key_fields() as $key) {
            if (array_key_exists($key, $data)) {
                $query_where_part[] = $key . '=' . $database->quote($data[$key]);
            } else {
                $all_keys_exists = false;
            }
        }
        if ($all_keys_exists) {
            $query = 'SELECT OXID FROM ' . $this->get_table_name() . ' WHERE ' . implode(' AND ', $query_where_part);
            return $database->get_one($query);
        }
        return null;
    }
    /**
     * Checks if user is allowed to edit in this shop.
     *
     * @param int $shopId shop id
     *
     * @return bool
     */
    protected function is_allowed_to_edit($shop_id)
    {
        $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $user->load_admin_user();
        if ($user->oxuser__oxrights->value == 'malladmin' || $user->oxuser__oxrights->value == (int) $shop_id) {
            return true;
        }
        return false;
    }
    /**
     * Checks if id field is valid.
     *
     * @param string $id field check id
     *
     * @throws Exception
     */
    protected function check_id_field($id)
    {
        if (!isset($id) || !$id) {
            throw new Exception('ERROR: Articlenumber/ID missing!');
        }
        if (strlen($id) > 32) {
            throw new Exception('ERROR: Articlenumber/ID longer then allowed (32 chars max.)!');
        }
    }
    /**
     * @return BaseModel
     */
    protected function create_shop_object()
    {
        $object_name = $this->get_shop_object_name();
        if ($object_name) {
            $shop_object = ox_new($object_name);
            if ($shop_object instanceof Multi_Language_Model) {
                $shop_object->set_language(0);
                $shop_object->set_enable_multilang(false);
            }
        } else {
            $shop_object = ox_new(Base_Model::class);
            $shop_object->init($this->get_base_table_name());
        }
        return $shop_object;
    }
}