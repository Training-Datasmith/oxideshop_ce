<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Symfony\Component\Filesystem\Path;
/**
 * General export class.
 */
class Voucher_Serie_Export extends \Oxid_Esales\Eshop\Application\Controller\Admin\Voucher_Serie_Main
{
    /**
     * Export class name
     *
     * @var string
     */
    public $s_class_do = 'voucherserie_export';
    /**
     * Export file extension
     *
     * @var string
     */
    public $s_export_file_type = 'csv';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'voucherserie_export';
    /**
     * Number of records to export per tick
     *
     * @var int
     */
    public $i_export_per_tick = 1000;
    /**
     * Calls parent costructor and initializes $this->_sFilePath parameter
     */
    public function __construct()
    {
        parent::__construct();
        // export file name
        $this->s_export_file_name = $this->get_export_file_name();
        // set generic frame template
        $this->_s_file_path = $this->get_export_file_path();
    }
    /**
     * Returns export file download url
     *
     * @return string
     */
    public function get_download_url()
    {
        $my_config = Registry::get_config();
        Container_Facade::get_parameter('oxid_esales.shop_admin_url');
        $url = Container_Facade::get_parameter('oxid_esales.shop_admin_url') ?: Container_Facade::get_parameter('oxid_esales.shop_url') . $my_config->get_config_param('sAdminDir');
        $url = Registry::get_utils_url()->process_url($url . '/index.php');
        return $url . '&amp;cl=' . $this->s_class_do . '&amp;fnc=download';
    }
    /**
     * Return export file name
     *
     * @return string
     */
    protected function get_export_file_name()
    {
        $s_session_file_name = Registry::get_session()->get_variable('sExportFileName');
        if (!$s_session_file_name) {
            $session = Registry::get_session();
            $s_session_file_name = md5($session->get_id() . Registry::get_utils_object()->generate_u_id());
            Registry::get_session()->set_variable('sExportFileName', $s_session_file_name);
        }
        return $s_session_file_name;
    }
    /**
     * Return export file path
     *
     * @return string
     */
    protected function get_export_file_path()
    {
        return Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), 'export', $this->get_export_file_name());
    }
    /**
     * Performs Voucherserie export to export file.
     */
    public function download(): void
    {
        $o_utils = Registry::get_utils();
        $o_utils->set_header('Pragma: public');
        $o_utils->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        $o_utils->set_header('Expires: 0');
        $o_utils->set_header('Content-Disposition: attachment; filename=vouchers.csv');
        $o_utils->set_header('Content-Type: application/csv');
        $s_file = $this->get_export_file_path();
        if (file_exists($s_file) && is_readable($s_file)) {
            readfile($s_file);
        }
        $o_utils->show_message_and_exit('');
    }
    /**
     * Does Export
     */
    public function run(): void
    {
        $bl_continue = true;
        $this->fp_file = @fopen($this->_s_file_path, 'a');
        if (!isset($this->fp_file) || !$this->fp_file) {
            // we do have an error !
            $this->stop(ERR_FILEIO);
        } else {
            // file is open
            $i_start = Registry::get_request()->get_request_escaped_parameter('iStart');
            if (!$i_start) {
                ftruncate($this->fp_file, 0);
            }
            if (($i_exported_items = $this->export_vouchers($i_start)) === false) {
                // end reached
                $this->stop(ERR_SUCCESS);
                $bl_continue = false;
            }
            // make ticker continue
            $this->_a_view_data['refresh'] = 0;
            $this->_a_view_data['iStart'] = $i_start + $i_exported_items;
            $this->_a_view_data['iExpItems'] = $i_start + $i_exported_items;
            fclose($this->fp_file);
        }
    }
    /**
     * Writes voucher number information to export file and returns number of written records info
     *
     * @param int $iStart start exporting from
     *
     * @return int
     */
    public function export_vouchers($i_start)
    {
        $voucher_serie = $this->get_voucher_serie();
        if (!$voucher_serie) {
            return false;
        }
        $result_set = Database_Provider::get_db()->select_limit('select oxvouchernr from oxvouchers where oxvoucherserieid = :oxvoucherserieid', $this->i_export_per_tick, $i_start, ['oxvoucherserieid' => $voucher_serie->get_id()]);
        if ($result_set->EOF) {
            return false;
        }
        $exported_vouchers_count = 0;
        // writing header text
        if ($i_start == 0) {
            $this->write(Registry::get_lang()->translate_string('VOUCHERSERIE_MAIN_VOUCHERSTATISTICS', Registry::get_lang()->get_tpl_language(), true));
        }
        // writing vouchers..
        while (!$result_set->EOF) {
            $this->write(current($result_set->fields));
            $exported_vouchers_count++;
            $result_set->fetch_row();
        }
        return $exported_vouchers_count;
    }
    /**
     * writes one line into open export file
     *
     * @param string $sLine exported line
     */
    public function write($s_line): void
    {
        if ($s_line) {
            fwrite($this->fp_file, $s_line . "\n");
        }
    }
}