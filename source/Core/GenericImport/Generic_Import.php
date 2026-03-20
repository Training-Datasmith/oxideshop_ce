<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import;

use Exception;
use Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object;
/**
 * Class responsible for generic import functionality.
 */
class Generic_Import
{
    public const ERROR_USER_NO_RIGHTS = 'Not sufficient rights to perform operation!';
    public const ERROR_NO_INIT = 'Init not executed, Access denied!';
    /** @var array Import objects types. */
    protected $objects = ['A' => 'Article', 'K' => 'Category', 'H' => 'Vendor', 'C' => 'CrossSelling', 'Z' => 'Accessories2Article', 'T' => 'Article2Category', 'I' => 'Article2Action', 'P' => 'ScalePrice', 'U' => 'User', 'O' => 'Order', 'R' => 'OrderArticle', 'N' => 'Country', 'Y' => 'ArticleExtends'];
    /** @var string Imported data array. */
    protected $import_type;
    /** @var array Imported id array */
    protected $imported_ids = [];
    /** @var string Return message after import. */
    protected $return_message;
    /** @var string Csv file field terminator. */
    protected $default_string_terminator = ';';
    /** @var string Csv file field encloser. */
    protected $default_string_encloser = '"';
    /** @var bool CSV file contains header or not. */
    protected $csv_contains_header;
    /** @var string Import file location. */
    protected $import_file_path;
    /** @var bool */
    protected $is_initialized = false;
    /** @var int */
    protected $user_id;
    /** @var array */
    protected $statistics = [];
    /** @var bool Whether import was retried. */
    protected $retried = false;
    /** @var array CSV file fields array. */
    protected $csv_file_fields_order = [];
    /** @var int Maximum length of imported line. */
    protected $max_line_length = 8192;
    /**
     * Init parameters needed for import.
     * Creates Objects, checks Rights etc.
     *
     * @throws Exception
     *
     * @return boolean
     */
    public function init()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $user->load_admin_user();
        $rights = $user->get_field_data('oxrights');
        if ($rights === 'malladmin' || (int) $rights === (int) $config->get_shop_id()) {
            $this->is_initialized = true;
            $this->user_id = $user->get_id();
        } else {
            //user does not have sufficient rights for shop
            throw new Exception(self::ERROR_USER_NO_RIGHTS);
        }
        return $this->is_initialized;
    }
    /**
     * Get import object according import type.
     *
     * @param string $type Import object type.
     *
     * @return ImportObject
     */
    public function get_import_object($type)
    {
        $this->import_type = $type;
        $result = null;
        try {
            $import_type = $this->get_import_type();
            $result = $this->create_import_object($import_type);
        } catch (Exception) {
        }
        return $result;
    }
    /**
     * Set import object type prefix.
     *
     * @param string $type Import type prefix.
     */
    public function set_import_type($type): void
    {
        $this->import_type = $type;
    }
    /**
     * Set CSV file columns names.
     *
     * @param array $csvFields CSV fields.
     */
    public function set_csv_file_fields_order($csv_fields): void
    {
        $this->csv_file_fields_order = $csv_fields;
    }
    /**
     * Set if CSV file contains header row.
     *
     * @param bool $csvContainsHeader Whether imported file has a header row.
     */
    public function set_csv_contains_header($csv_contains_header): void
    {
        $this->csv_contains_header = $csv_contains_header;
    }
    /**
     * Main import method, whole import of all types via a given csv file is done here.
     *
     * @param string $importFilePath Full path of the CSV file.
     */
    public function import_file($import_file_path = null): string
    {
        $this->return_message = '';
        $this->import_file_path = $import_file_path;
        //init with given data
        try {
            $this->init();
        } catch (Exception $ex) {
            return $this->return_message = 'ERPGENIMPORT_ERROR_USER_NO_RIGHTS';
        }
        $file = fopen($this->import_file_path, 'r');
        if (is_resource($file)) {
            $data = [];
            while (($row = fgetcsv($file, $this->max_line_length, $this->get_csv_fields_terminator(), $this->get_csv_fields_encolser())) !== false) {
                $data[] = $this->csv_text_convert($row, false);
            }
            fclose($file);
            if ($this->csv_contains_header) {
                array_shift($data);
            }
            try {
                $this->import_data($data);
            } catch (Exception $ex) {
                echo $ex->get_message();
                $this->return_message = 'ERPGENIMPORT_ERROR_DURING_IMPORT';
            }
        } else {
            $this->return_message = 'ERPGENIMPORT_ERROR_WRONG_FILE';
        }
        return $this->return_message;
    }
    /**
     * Performs import action.
     */
    public function import_data(array $data): void
    {
        foreach ($data as $key => $row) {
            if ($row) {
                try {
                    $success = $this->import_one($row);
                    $error_message = '';
                } catch (Exception $e) {
                    $success = false;
                    $error_message = $e->get_message();
                }
                $this->statistics[$key] = ['r' => $success, 'm' => $error_message];
            }
        }
        $this->after_import($data);
    }
    /**
     * Returns statistics information about import.
     *
     * @return array
     */
    public function get_statistics()
    {
        return $this->statistics;
    }
    /**
     * Returns count of imported rows, total, during import.
     *
     * @return int $_iImportedRowCount
     */
    public function get_imported_row_count(): int
    {
        return count($this->imported_ids);
    }
    /**
     * Returns allowed for import objects list.
     */
    public function get_import_objects_list(): array
    {
        $import_objects = [];
        foreach ($this->objects as $s_key => $import_type) {
            $type = $this->create_import_object($import_type);
            $import_objects[$s_key] = $type->get_base_table_name();
        }
        return $import_objects;
    }
    /**
     * Main Import Handler, imports one row/call/object...
     * returns true if there were any data processed, and
     * master loop should run import again.
     *
     * after importing, fills $this->_aStatistics[$this->_iIdx] with array
     * of r=>(bool)result, m=>(string)error message
     *
     * @param array $data
     */
    protected function import_one($data): bool
    {
        $type = $this->get_import_type();
        $import_object = $this->create_import_object($type);
        $data = $this->map_fields($data);
        $this->check_access($import_object, true);
        $id = $import_object->import($data);
        if ($id) {
            $this->add_imported_id($id);
        }
        return (bool) $id;
    }
    /**
     * Performs after import actions.
     * If any error occurred during import tries to run import again and marks retried as true.
     * If after running import second time all of the records failed, stops.
     */
    protected function after_import(array $data)
    {
        $statistics = $this->get_statistics();
        $data_for_retry = [];
        foreach ($statistics as $key => $value) {
            if ($value['r'] == false) {
                $this->return_message .= 'File[' . $this->import_file_path . "] - dataset number: {$key} - Error: " . $value['m'] . ' ---<br> ' . PHP_EOL;
                $data_for_retry[$key] = $data[$key];
            }
        }
        if (!empty($data_for_retry) && (!$this->retried || count($data_for_retry) != count($data))) {
            $this->retried = true;
            $this->return_message = '';
            $this->import_data($data_for_retry);
        }
    }
    /**
     * Gets import object type according type prefix.
     *
     * @throws Exception if no such import type prefix
     *
     * @return string
     */
    protected function get_import_type()
    {
        $type = $this->import_type;
        if (strlen($type) != 1 || !array_key_exists($type, $this->objects)) {
            throw new Exception('Error unknown command: ' . $type);
        }
        return $this->objects[$type];
    }
    /** Adds true to $_aImportedIds where key is given.
     *
     * @param mixed $id - given key
     */
    protected function add_imported_id($id)
    {
        if (!array_key_exists($id, $this->imported_ids)) {
            $this->imported_ids[$id] = true;
        }
    }
    /**
     * Maps numeric array to assoc. Array
     *
     * @param array $data numeric indices
     *
     * @return array assoc. indices
     */
    protected function map_fields(array $data): array
    {
        $result = [];
        $index = 0;
        foreach ($this->csv_file_fields_order as $value) {
            if (!empty($value)) {
                if (strtolower((string) $data[$index]) == 'null') {
                    $result[$value] = null;
                } else {
                    $result[$value] = $data[$index];
                }
            }
            $index++;
        }
        return $result;
    }
    /**
     * Parses and replaces special chars.
     *
     * @param string $text input text
     * @param bool   $mode true = Text2CSV, false = CSV2Text
     *
     * @return string
     */
    protected function csv_text_convert($text, $mode): string|array
    {
        $search = [chr(13), chr(10), '\'', '"'];
        $replace = ['&#13;', '&#10;', '&#39;', '&#34;'];
        if ($mode) {
            return str_replace($search, $replace, $text);
        }
        return str_replace($replace, $search, $text);
    }
    /**
     * Set csv field terminator symbol.
     *
     * @return string
     */
    protected function get_csv_fields_terminator()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $field_terminator = $config->get_config_param('sGiCsvFieldTerminator');
        if (!$field_terminator) {
            $field_terminator = $config->get_config_param('sCSVSign');
        }
        if (!$field_terminator) {
            return $this->default_string_terminator;
        }
        return $field_terminator;
    }
    /**
     * Get csv field encloser symbol.
     *
     * @return string
     */
    protected function get_csv_fields_encolser()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($field_encloser = $config->get_config_param('sGiCsvFieldEncloser')) {
            return $field_encloser;
        }
        return $this->default_string_encloser;
    }
    /**
     * Checks if user has sufficient rights.
     *
     * @param ImportObject $importObject  Data type object
     * @param boolean      $isWriteAction Check for write permissions
     *
     * @throws Exception
     */
    protected function check_access($import_object, $is_write_action)
    {
        if (!$this->is_initialized) {
            throw new Exception(self::ERROR_NO_INIT);
        }
    }
    /**
     * Creates and returns import object.
     *
     * @param string $type Type name in objects dir.
     *
     * @return ImportObject
     */
    protected function create_import_object(string $type)
    {
        $class_name = __NAMESPACE__ . '\ImportObject\\' . $type;
        return ox_new($class_name);
    }
}