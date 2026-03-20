<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Return_Type_Will_Change;
/**
 * List manager.
 * Collects list data (eg. from DB), performs list changes updating (to DB), etc.
 */
class List_Model extends \Oxid_Esales\Eshop\Core\Base implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * Array of objects (some object list).
     *
     * @var array $_aArray
     */
    protected $_a_array = [];
    /**
     * Save the state, that active element was unset
     * needed for proper foreach iterator functionality
     *
     * @var bool $_blRemovedActive
     */
    protected $_bl_removed_active = false;
    /**
     * Template object used for some methods before the list is built.
     *
     * @var BaseModel
     */
    private $_o_base_object;
    /**
     * Flag if array is ok or not
     */
    private bool $_bl_valid = true;
    /**
     * -----------------------------------------------------------------------------------------------------
     *
     * Implementation of SPL Array classes functions follows here
     *
     * -----------------------------------------------------------------------------------------------------
     */
    /**
     * implementation of abstract classes for ArrayAccess follow
     */
    /**
     * offsetExists for SPL
     *
     * @param mixed $offset SPL array offset
     *
     * @return boolean
     */
    #[Return_Type_Will_Change]
    public function offsetExists($offset)
    {
        return isset($this->_a_array[$offset]);
    }
    /**
     * offsetGet for SPL
     *
     * @param mixed $offset SPL array offset
     *
     * @return BaseModel
     */
    #[Return_Type_Will_Change]
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_a_array[$offset];
        }
        return false;
    }
    /**
     * offsetSet for SPL
     *
     * @param mixed     $offset SPL array offset
     * @param BaseModel $oBase  Array element
     */
    #[Return_Type_Will_Change]
    public function offsetSet($offset, $o_base): void
    {
        if (isset($offset)) {
            $this->_a_array[$offset] =& $o_base;
        } else {
            $s_long_field_name = $this->get_field_long_name('oxid');
            if (isset($o_base->{$s_long_field_name}->value)) {
                $s_oxid = $o_base->{$s_long_field_name}->value;
                $this->_a_array[$s_oxid] =& $o_base;
            } else {
                $this->_a_array[] =& $o_base;
            }
        }
    }
    /**
     * offsetUnset for SPL
     *
     * @param mixed $offset SPL array offset
     */
    #[Return_Type_Will_Change]
    public function offsetUnset($offset): void
    {
        if ((string) $offset === (string) $this->key()) {
            // #0002184: active element removed, next element will be prev / first
            $this->_bl_removed_active = true;
        }
        unset($this->_a_array[$offset]);
    }
    /**
     * Returns SPL array keys
     *
     * @return array
     */
    public function array_keys()
    {
        return array_keys($this->_a_array);
    }
    /**
     * rewind for SPL
     */
    #[Return_Type_Will_Change]
    public function rewind(): void
    {
        $this->_bl_removed_active = false;
        $this->_bl_valid = false !== reset($this->_a_array);
    }
    /**
     * current for SPL
     */
    #[Return_Type_Will_Change]
    public function current()
    {
        return current($this->_a_array);
    }
    /**
     * key for SPL
     *
     * @return mixed
     */
    #[Return_Type_Will_Change]
    public function key()
    {
        return key($this->_a_array);
    }
    /**
     * previous / first array element
     *
     * @return mixed
     */
    public function prev()
    {
        $o_var = prev($this->_a_array);
        if ($o_var === false) {
            // the first element, reset pointer
            $o_var = reset($this->_a_array);
        }
        $this->_bl_removed_active = false;
        return $o_var;
    }
    /**
     * next for SPL
     */
    #[Return_Type_Will_Change]
    public function next(): void
    {
        if ($this->_bl_removed_active === true && current($this->_a_array)) {
            $o_var = $this->prev();
        } else {
            $o_var = next($this->_a_array);
        }
        $this->_bl_valid = false !== $o_var;
    }
    /**
     * valid for SPL
     *
     * @return boolean
     */
    #[Return_Type_Will_Change]
    public function valid()
    {
        return $this->_bl_valid;
    }
    /**
     * count for SPL
     *
     * @return integer
     */
    #[Return_Type_Will_Change]
    public function count()
    {
        return count($this->_a_array);
    }
    /**
     * clears/destroys list contents
     */
    public function clear(): void
    {
        /*
                foreach ( $this->_aArray as $key => $sValue) {
                    unset( $this->_aArray[$key]);
                }
                reset( $this->_aArray);*/
        $this->_a_array = [];
    }
    /**
     * copies a given array over the objects internal array (something like old $myList->aList = $aArray)
     *
     * @param array $aArray array of list items
     */
    public function assign($a_array): void
    {
        $this->_a_array = $a_array;
    }
    /**
     * returns the array reversed, the internal array remains untouched
     *
     * @return array
     */
    public function reverse()
    {
        return array_reverse($this->_a_array);
    }
    /**
     * -----------------------------------------------------------------------------------------------------
     * SPL implementation end
     * -----------------------------------------------------------------------------------------------------
     */
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_s_objects_in_list_name = 'oxBase';
    /**
     * Core table name
     *
     * @var string
     */
    protected $_s_core_table;
    /**
     * @var string ShopID
     */
    protected $_s_shop_id;
    /**
     * @var array SQL Limit, 0 => Start, 1 => Records
     */
    protected $_a_sql_limit = [];
    /**
     * Class Constructor
     *
     * @param string $sObjectName Associated list item object type
     */
    public function __construct($s_object_name = null)
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $this->_a_sql_limit[0] = 0;
        $this->_a_sql_limit[1] = 0;
        $this->_s_shop_id = $my_config->get_shop_id();
        if ($s_object_name) {
            $this->init($s_object_name);
        }
    }
    /**
     * Backward compatibility method
     *
     * @param string $sName Variable name
     *
     * @return mixed
     */
    public function __get($s_name)
    {
        if ($s_name == 'aList') {
            return $this->_a_array;
        }
    }
    /**
     * Returns list items array
     *
     * @return array
     */
    public function get_array()
    {
        return $this->_a_array;
    }
    /**
     * Inits list table name and object name.
     *
     * @param string $sObjectName List item object type
     * @param string $sCoreTable  Db table name this list s selected from
     */
    public function init($s_object_name, $s_core_table = null): void
    {
        $this->_s_objects_in_list_name = $s_object_name;
        if ($s_core_table) {
            $this->_s_core_table = $s_core_table;
        }
    }
    /**
     * Initializes or returns existing list template object.
     *
     * @return BaseModel
     */
    public function get_base_object()
    {
        if (!$this->_o_base_object) {
            $this->_o_base_object = ox_new($this->_s_objects_in_list_name);
            $this->_o_base_object->set_in_list();
            $this->_o_base_object->init($this->_s_core_table);
        }
        return $this->_o_base_object;
    }
    /**
     * Sets base object for list.
     *
     * @param object $oObject Base object
     */
    public function set_base_object($o_object): void
    {
        $this->_o_base_object = $o_object;
    }
    /**
     * Convert results of the given select statement into objects and store them in _aArray.
     *
     * Developers are encouraged to use prepared statements like this:
     * $sql = 'SELECT * FROM `mytable` WHERE oxid = ?';
     * $parameters = ['MYOXID']
     * selectString($sql, $parameters)
     *
     * @param string $sql        SQL select statement or prepared statement
     * @param array  $parameters Parameters to be used in a prepared statement
     */
    public function select_string($sql, array $parameters = []): void
    {
        $this->clear();
        $database = Database_Provider::get_db();
        if ($this->_a_sql_limit[0] || $this->_a_sql_limit[1]) {
            $result = $database->select_limit($sql, $this->_a_sql_limit[1], max(0, $this->_a_sql_limit[0]), $parameters);
        } else {
            $result = $database->select($sql, $parameters);
        }
        if ($result != false && $result->count() > 0) {
            $o_saved = clone $this->get_base_object();
            while (!$result->EOF) {
                $o_list_object = clone $o_saved;
                $this->assign_element($o_list_object, $result->fields);
                $this->add($o_list_object);
                $result->fetch_row();
            }
        }
    }
    /**
     * Add an entry to object array.
     *
     * @param object $oObject Object to be added.
     */
    public function add($o_object): void
    {
        if ($o_object->get_id()) {
            $this->_a_array[$o_object->get_id()] = $o_object;
        } else {
            $this->_a_array[] = $o_object;
        }
    }
    /**
     * Assign data from array to list
     *
     * @param array $aData data for list
     */
    public function assign_array($a_data): void
    {
        $this->clear();
        if (count($a_data)) {
            $o_saved = clone $this->get_base_object();
            foreach ($a_data as $a_item) {
                $o_list_object = clone $o_saved;
                $this->assign_element($o_list_object, $a_item);
                if ($o_list_object->get_id()) {
                    $this->_a_array[$o_list_object->get_id()] = $o_list_object;
                } else {
                    $this->_a_array[] = $o_list_object;
                }
            }
        }
    }
    /**
     * Sets SQL Limit
     *
     * @param integer $iStart   Start e.g. limit Start,xxxx
     * @param integer $iRecords Nr of Records e.g. limit xxx,Records
     */
    public function set_sql_limit($i_start, $i_records): void
    {
        $this->_a_sql_limit[0] = $i_start;
        $this->_a_sql_limit[1] = $i_records;
    }
    /**
     * Function checks if there is at least one object in the list which has the given value in the given field
     *
     * @param mixed  $oVal       The searched value
     * @param string $sFieldName The name of the field, give "oxid" will access the classname__oxid field
     *
     * @return boolean
     */
    public function contains_field_value($o_val, $s_field_name)
    {
        $s_field_name = $this->get_field_long_name($s_field_name);
        foreach ($this->_a_array as $obj) {
            if ($obj->{$s_field_name}->value == $o_val) {
                return true;
            }
        }
        return false;
    }
    /**
     * Generic function for loading the list
     */
    public function get_list()
    {
        $o_list_object = $this->get_base_object();
        $s_field_list = $o_list_object->get_select_fields();
        $s_q = "select {$s_field_list} from " . $o_list_object->get_view_name();
        if ($s_active_snippet = $o_list_object->get_sql_active_snippet()) {
            $s_q .= " where {$s_active_snippet} ";
        }
        $this->select_string($s_q);
        return $this;
    }
    /**
     * Executes assign() method on list object. This method is called in loop in oxList::selectString().
     * It is if you want to execute any functionality on every list ELEMENT after it is fully loaded (assigned).
     *
     * @param BaseModel $oListObject List object (the one derived from BaseModel)
     * @param array     $aDbFields   An array holding db field values (normally the result of \OxidEsales\Eshop\Core\DatabaseProvider::Execute())
     */
    protected function assign_element($o_list_object, $a_db_fields)
    {
        $o_list_object->assign($a_db_fields);
    }
    /**
     * Returns field long name
     *
     * @param string $sFieldName Field name
     *
     * @return string
     */
    protected function get_field_long_name($s_field_name)
    {
        if ($this->_s_core_table) {
            return $this->_s_core_table . '__' . $s_field_name;
        }
        return $s_field_name;
    }
}