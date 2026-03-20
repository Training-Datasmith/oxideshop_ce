<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Dom_Document;
use Exception;
use Soap_Client;
use Soap_Fault;
use stdClass;
/**
 * Online VAT id checker class.
 */
class Online_Vat_Id_Check extends \Oxid_Esales\Eshop\Core\Company_Vat_In_Checker
{
    /**
     * Keeps service check state
     *
     * @var bool
     */
    protected $_bl_service_is_on;
    /**
     * VAT check results cache
     *
     * @var array
     */
    protected static $_a_vat_check_cache = [];
    /**
     * How many times to retry check if server is busy
     */
    public const BUSY_RETRY_CNT = 1;
    /**
     * How much to wait between retries (in micro seconds)
     */
    public const BUSY_RETRY_WAITUSEC = 500000;
    /**
     * Wsdl url
     *
     * @var string
     */
    protected $_s_wsdl = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
    /**
     * Validates VAT.
     *
     * @param \OxidEsales\Eshop\Application\Model\CompanyVatIn $oVatIn Company VAT identification number object.
     *
     * @return bool
     */
    public function validate(\Oxid_Esales\Eshop\Application\Model\Company_Vat_In $o_vat_in)
    {
        $o_check_vat = new stdClass();
        $o_check_vat->country_code = $o_vat_in->get_country_code();
        $o_check_vat->vat_number = $o_vat_in->get_numbers();
        $bl_result = $this->check_online($o_check_vat);
        if (!$bl_result) {
            $this->set_error('ID_NOT_VALID');
        }
        return $bl_result;
    }
    /**
     * Catches soap warning which is usually thrown due to service problems.
     * Return true and allows to continue process
     *
     * @param int    $iErrNo   error type number
     * @param string $sErrStr  error message
     * @param string $sErrFile error file
     * @param int    $iErrLine error line
     */
    public function catch_warning($i_err_no, $s_err_str, $s_err_file, $i_err_line): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_logger()->warning($s_err_str, ['file' => $s_err_file, 'line' => $i_err_line, 'code' => $i_err_no]);
    }
    /**
     * Checks if VAT check can be performed:
     *  - if SoapClient class exists;
     *  - if service returns any output;
     *  - if output, returned by service, is valid.
     *
     * @return bool
     */
    protected function is_service_available()
    {
        if ($this->_bl_service_is_on === null) {
            $this->_bl_service_is_on = class_exists('SoapClient') ? true : false;
            if ($this->_bl_service_is_on) {
                $r_fp = @fopen($this->get_wsdl_url(), 'r');
                $this->_bl_service_is_on = $r_fp !== false;
                if ($this->_bl_service_is_on) {
                    $s_wsdl = '';
                    while (!feof($r_fp)) {
                        $s_wsdl .= fread($r_fp, 8192);
                    }
                    fclose($r_fp);
                    // validating wsdl file
                    try {
                        $o_dom_document = new Dom_Document();
                        $o_dom_document->load_xml($s_wsdl);
                    } catch (Exception) {
                        // invalid xml
                        $this->_bl_service_is_on = false;
                    }
                }
            }
        }
        return $this->_bl_service_is_on;
    }
    /**
     * Checks online if USt.ID number is valid.
     * Returns true on success. On error sets error value.
     *
     * @param object $oCheckVat vat object
     *
     * @return bool
     */
    protected function check_online($o_check_vat)
    {
        //Default D3 Source: https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl
        $a_retry_errors = ['SERVER_BUSY', 'GLOBAL_MAX_CONCURRENT_REQ', 'MS_MAX_CONCURRENT_REQ', 'SERVICE_UNAVAILABLE', 'MS_UNAVAILABLE', 'TIMEOUT'];
        if ($this->is_service_available()) {
            $i_try_more_cnt = self::BUSY_RETRY_CNT;
            //T2009-07-02
            //how long socket should wait for server RESPONSE
            ini_set('default_socket_timeout', 5);
            // setting local error handler to catch possible soap errors
            set_error_handler($this->catch_warning(...), E_WARNING);
            do {
                try {
                    //connection_timeout = how long we should wait to CONNECT to wsdl server
                    $o_soap_client = new Soap_Client($this->get_wsdl_url(), ['connection_timeout' => 5]);
                    $this->set_error('');
                    $o_res = $o_soap_client->check_vat($o_check_vat);
                    $i_try_more_cnt = 0;
                } catch (Soap_Fault $e) {
                    $this->set_error($e->faultstring);
                    if (in_array($this->get_error(), $a_retry_errors)) {
                        usleep(self::BUSY_RETRY_WAITUSEC);
                    } else {
                        $i_try_more_cnt = 0;
                    }
                }
            } while (0 < $i_try_more_cnt--);
            // restoring previous error handler
            restore_error_handler();
            return (bool) $o_res->valid;
        }
        $this->set_error('SERVICE_UNREACHABLE');
        return false;
    }
    /**
     * Returns wsdl url
     *
     * @return string
     */
    public function get_wsdl_url()
    {
        // overriding wsdl url
        if ($s_wsdl = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sVatIdCheckInterfaceWsdl')) {
            $this->_s_wsdl = $s_wsdl;
        }
        return $this->_s_wsdl;
    }
}