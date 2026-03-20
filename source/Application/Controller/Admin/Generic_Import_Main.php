<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Symfony\Component\Filesystem\Path;
/**
 * Admin general export manager.
 */
class Generic_Import_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Export class name
     *
     * @var string
     */
    public $s_class_do = 'genImport_do';
    /**
     * Export ui class name
     *
     * @var string
     */
    public $s_class_main = 'genImport_main';
    /**
     * Csv file path
     *
     * @var string
     */
    protected $_s_csv_file_path;
    /**
     * Csv file field terminator
     *
     * @var string
     */
    protected $_s_string_terminator;
    /**
     * Csv file field encloser
     *
     * @var string
     */
    protected $_s_string_encloser;
    /**
     * Default Csv file field terminator
     *
     * @var string
     */
    protected $_s_default_string_terminator = ';';
    /**
     * Default Csv file field encloser
     *
     * @var string
     */
    protected $_s_default_string_encloser = '"';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'genimport_main';
    /** @inheritdoc */
    public function render()
    {
        $config = Registry::get_config();
        $generic_import = ox_new(\Oxid_Esales\Eshop\Core\Generic_Import\Generic_Import::class);
        $this->_s_csv_file_path = null;
        $navigation_step = Registry::get_request()->get_request_escaped_parameter('sNavStep');
        if (!$navigation_step) {
            $navigation_step = 1;
        } else {
            $navigation_step++;
        }
        $navigation_step = $this->check_errors($navigation_step);
        if ($navigation_step == 1) {
            $this->_a_view_data['sGiCsvFieldTerminator'] = \Oxid_Esales\Eshop\Core\Str::get_str()->htmlentities($this->get_csv_fields_terminator());
            $this->_a_view_data['sGiCsvFieldEncloser'] = \Oxid_Esales\Eshop\Core\Str::get_str()->htmlentities($this->get_csv_fields_encolser());
        }
        if ($navigation_step == 2) {
            $no_js_validator = ox_new(\Oxid_Esales\Eshop\Core\No_Js_Validator::class);
            //saving csv field terminator and encloser to config
            $terminator = Registry::get_request()->get_request_escaped_parameter('sGiCsvFieldTerminator');
            if ($terminator && !$no_js_validator->is_valid($terminator)) {
                $this->set_error_to_view($terminator);
            } else {
                $this->_s_string_terminator = $terminator;
                $config->save_shop_conf_var('str', 'sGiCsvFieldTerminator', $terminator);
            }
            $encloser = Registry::get_request()->get_request_escaped_parameter('sGiCsvFieldEncloser');
            if ($encloser && !$no_js_validator->is_valid($encloser)) {
                $this->set_error_to_view($encloser);
            } else {
                $this->_s_string_encloser = $encloser;
                $config->save_shop_conf_var('str', 'sGiCsvFieldEncloser', $encloser);
            }
            $type = Registry::get_request()->get_request_escaped_parameter('sType');
            $import_object = $generic_import->get_import_object($type);
            $this->_a_view_data['sType'] = $type;
            $this->_a_view_data['sImportTable'] = $import_object->get_base_table_name();
            $this->_a_view_data['aCsvFieldsList'] = $this->get_csv_fields_names();
            $this->_a_view_data['aDbFieldsList'] = $import_object->get_field_list();
        }
        if ($navigation_step == 3) {
            $csv_fields = Registry::get_request()->get_request_escaped_parameter('aCsvFields');
            $type = Registry::get_request()->get_request_escaped_parameter('sType');
            $generic_import = ox_new(\Oxid_Esales\Eshop\Core\Generic_Import\Generic_Import::class);
            $generic_import->set_import_type($type);
            $generic_import->set_csv_file_fields_order($csv_fields);
            $generic_import->set_csv_contains_header(\Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('blCsvContainsHeader'));
            $generic_import->import_file($this->get_uploaded_csv_file_path());
            $this->_a_view_data['iTotalRows'] = $generic_import->get_imported_row_count();
            //checking if errors occured during import
            $this->check_import_errors($generic_import);
            //deleting uploaded csv file from temp dir
            $this->delete_csv_file();
            //check if repeating import - then forsing first step
            if (Registry::get_request()->get_request_escaped_parameter('iRepeatImport')) {
                $this->_a_view_data['iRepeatImport'] = 1;
                $navigation_step = 1;
            }
        }
        if ($navigation_step == 1) {
            $this->_a_view_data['aImportTables'] = $generic_import->get_import_objects_list();
            asort($this->_a_view_data['aImportTables']);
            $this->reset_uploaded_csv_data();
        }
        $this->_a_view_data['sNavStep'] = $navigation_step;
        return parent::render();
    }
    /**
     * Deletes uploaded csv file from temp directory
     */
    protected function delete_csv_file()
    {
        $s_path = $this->get_uploaded_csv_file_path();
        if (is_file($s_path)) {
            @unlink($s_path);
        }
    }
    /**
     * Get columns names from CSV file header. If file has no header
     * returns default columns names Column 1, Column 2..
     *
     * @return array
     */
    protected function get_csv_fields_names()
    {
        $bl_csv_contains_header = Registry::get_request()->get_request_escaped_parameter('blContainsHeader');
        Registry::get_session()->set_variable('blCsvContainsHeader', $bl_csv_contains_header);
        $this->get_uploaded_csv_file_path();
        $a_first_row = $this->get_csv_first_row();
        if (!$bl_csv_contains_header) {
            $i_index = 1;
            foreach ($a_first_row as $s_value) {
                $a_csv_fields[$i_index] = 'Column ' . $i_index++;
            }
        } else {
            foreach ($a_first_row as $s_key => $s_value) {
                $a_first_row[$s_key] = \Oxid_Esales\Eshop\Core\Str::get_str()->htmlentities($s_value);
            }
            $a_csv_fields = $a_first_row;
        }
        return $a_csv_fields;
    }
    /**
     * Get first row from uploaded CSV file
     *
     * @return array
     */
    protected function get_csv_first_row()
    {
        $s_path = $this->get_uploaded_csv_file_path();
        $i_max_line_length = 8192;
        //getting first row
        if (($r_file = @fopen($s_path, 'r')) !== false) {
            $a_row = fgetcsv($r_file, $i_max_line_length, $this->get_csv_fields_terminator(), $this->get_csv_fields_encolser());
            fclose($r_file);
        }
        return $a_row;
    }
    /**
     * Resets CSV parameters stored in session
     */
    protected function reset_uploaded_csv_data()
    {
        $this->_s_csv_file_path = null;
        Registry::get_session()->set_variable('sCsvFilePath', null);
        Registry::get_session()->set_variable('blCsvContainsHeader', null);
    }
    /**
     * Checks current import navigation step errors.
     * Returns step id in which error occured.
     *
     * @param int $iNavStep Navigation step id
     *
     * @return int
     */
    protected function check_errors($i_nav_step)
    {
        if ($i_nav_step == 2) {
            if (!$this->get_uploaded_csv_file_path()) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
                $o_ex->set_message('GENIMPORT_ERRORUPLOADINGFILE');
                Registry::get_utils_view()->add_error_to_display($o_ex, false, true, 'genimport');
                return 1;
            }
        }
        if ($i_nav_step == 3) {
            $bl_is_empty = true;
            $a_csv_fields = Registry::get_request()->get_request_escaped_parameter('aCsvFields');
            foreach ($a_csv_fields as $s_value) {
                if ($s_value) {
                    $bl_is_empty = false;
                    break;
                }
            }
            if ($bl_is_empty) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
                $o_ex->set_message('GENIMPORT_ERRORASSIGNINGFIELDS');
                Registry::get_utils_view()->add_error_to_display($o_ex, false, true, 'genimport');
                return 2;
            }
        }
        return $i_nav_step;
    }
    /**
     * Checks if CSV file was uploaded. If uploaded - moves it to temp dir
     * and stores path to file in session. Return path to uploaded file.
     *
     * @return string
     */
    protected function get_uploaded_csv_file_path()
    {
        $this->_s_csv_file_path ??= Registry::get_session()->get_variable('sCsvFilePath');
        if ($this->_s_csv_file_path) {
            return $this->_s_csv_file_path;
        }
        $upload = Registry::get_config()->get_uploaded_file('csvfile');
        if (isset($upload['name']) && $upload['name']) {
            $this->_s_csv_file_path = Path::join(Container_Facade::get_parameter('oxid_esales.build_directory'), basename((string) $upload['tmp_name']));
            move_uploaded_file($upload['tmp_name'], $this->_s_csv_file_path);
            Registry::get_session()->set_variable('sCsvFilePath', $this->_s_csv_file_path);
            return $this->_s_csv_file_path;
        }
    }
    /**
     * Checks if any error occured during import and displays them
     *
     * @param object $oErpImport Import object
     */
    protected function check_import_errors($o_erp_import)
    {
        foreach ($o_erp_import->get_statistics() as $a_value) {
            if (!$a_value['r']) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
                $o_ex->set_message($a_value['m']);
                Registry::get_utils_view()->add_error_to_display($o_ex, false, true, 'genimport');
            }
        }
    }
    /**
     * Get csv field terminator symbol
     *
     * @return string
     */
    protected function get_csv_fields_terminator()
    {
        if ($this->_s_string_terminator === null) {
            $this->_s_string_terminator = $this->_s_default_string_terminator;
            if ($char = Registry::get_config()->get_config_param('sGiCsvFieldTerminator')) {
                $this->_s_string_terminator = $char;
            }
        }
        return $this->_s_string_terminator;
    }
    /**
     * Get csv field encloser symbol
     *
     * @return string
     */
    protected function get_csv_fields_encolser()
    {
        if ($this->_s_string_encloser === null) {
            $this->_s_string_encloser = $this->_s_default_string_encloser;
            if ($char = Registry::get_config()->get_config_param('sGiCsvFieldEncloser')) {
                $this->_s_string_encloser = $char;
            }
        }
        return $this->_s_string_encloser;
    }
    /**
     * @param string $invalidData
     */
    private function set_error_to_view($invalid_data): void
    {
        $error = ox_new(\Oxid_Esales\Eshop\Core\Display_Error::class);
        $error->set_format_parameters(htmlspecialchars($invalid_data));
        $error->set_message('SHOP_CONFIG_ERROR_INVALID_VALUE');
        Registry::get_utils_view()->add_error_to_display($error);
    }
}