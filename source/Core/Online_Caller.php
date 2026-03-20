<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Exception;
use Oxid_Esales\Eshop_Community\Core\Exception\Standard_Exception;
/**
 * Class oxOnlineCaller makes call to given URL which is taken from child classes and sends request parameter.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
abstract class Online_Caller
{
    public const ALLOWED_HTTP_FAILED_CALLS_COUNT = 4;
    /** Amount of seconds for curl execution timeout. */
    public const CURL_EXECUTION_TIMEOUT = 5;
    /** Amount of seconds for curl connect timeout. */
    public const CURL_CONNECT_TIMEOUT = 3;
    /**
     * @var \OxidEsales\Eshop\Core\Curl
     */
    private $_o_curl;
    /**
     * @var \OxidEsales\Eshop\Core\OnlineServerEmailBuilder
     */
    private $_o_email_builder;
    /**
     * @var \OxidEsales\Eshop\Core\SimpleXml
     */
    private $_o_simple_xml;
    /**
     * Gets XML document name.
     *
     * @return string XML document tag name.
     */
    abstract protected function get_xml_document_name();
    /**
     * Gets service url.
     *
     * @return string Web service url.
     */
    abstract protected function get_service_url();
    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Core\Curl                     $oCurl         Sends request to OXID servers.
     * @param \OxidEsales\Eshop\Core\OnlineServerEmailBuilder $oEmailBuilder Forms email when OXID servers are unreachable.
     * @param \OxidEsales\Eshop\Core\SimpleXml                $oSimpleXml    Forms XML from Request for sending to OXID servers.
     */
    public function __construct(\Oxid_Esales\Eshop\Core\Curl $o_curl, \Oxid_Esales\Eshop\Core\Online_Server_Email_Builder $o_email_builder, \Oxid_Esales\Eshop\Core\Simple_Xml $o_simple_xml)
    {
        $this->_o_curl = $o_curl;
        $this->_o_email_builder = $o_email_builder;
        $this->_o_simple_xml = $o_simple_xml;
    }
    /**
     * Makes curl call with given parameters to given url.
     *
     * @param \OxidEsales\Eshop\Core\OnlineRequest $oRequest Information set in Request object will be sent to OXID servers.
     *
     * @return null|string In XML format.
     */
    public function call(\Oxid_Esales\Eshop\Core\Online_Request $o_request)
    {
        $s_output_xml = null;
        $i_failed_calls_count = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_system_config_parameter('iFailedOnlineCallsCount');
        try {
            $s_xml = $this->form_xml_request($o_request);
            $s_output_xml = $this->execute_curl_call($this->get_service_url(), $s_xml);
            $status_code = $this->get_curl()->get_status_code();
            if ($status_code != 200) {
                /** @var \OxidEsales\Eshop\Core\Exception\StandardException $oException */
                $o_exception = new Standard_Exception('cUrl call to ' . $this->get_curl()->get_url() . ' failed with HTTP status ' . $status_code);
                throw $o_exception;
            }
            $this->reset_failed_calls_count($i_failed_calls_count);
        } catch (Exception $o_ex) {
            if ($i_failed_calls_count > self::ALLOWED_HTTP_FAILED_CALLS_COUNT) {
                \Oxid_Esales\Eshop\Core\Registry::get_logger()->error($o_ex->get_message(), [$o_ex]);
                $s_xml = $this->form_email($o_request);
                $this->send_email($s_xml);
                $this->reset_failed_calls_count($i_failed_calls_count);
            } else {
                $this->increase_failed_calls_count($i_failed_calls_count);
            }
        }
        return $s_output_xml;
    }
    /**
     * Forms email.
     *
     * @param \OxidEsales\Eshop\Core\OnlineRequest $oRequest Request object from which email should be formed.
     *
     * @return string
     */
    protected function form_email($o_request)
    {
        return $this->form_xml_request($o_request);
    }
    /**
     * Forms XML request.
     *
     * @param \OxidEsales\Eshop\Core\OnlineRequest $oRequest Request object from which server request should be formed.
     *
     * @return string
     */
    protected function form_xml_request($o_request)
    {
        return $this->get_simple_xml()->object_to_xml($o_request, $this->get_xml_document_name());
    }
    /**
     * Gets simple XML.
     *
     * @return \OxidEsales\Eshop\Core\SimpleXml
     */
    protected function get_simple_xml()
    {
        return $this->_o_simple_xml;
    }
    /**
     * Gets curl.
     *
     * @return \OxidEsales\Eshop\Core\Curl
     */
    protected function get_curl()
    {
        return $this->_o_curl;
    }
    /**
     * Gets email builder.
     *
     * @return \OxidEsales\Eshop\Core\OnlineServerEmailBuilder
     */
    protected function get_email_builder()
    {
        return $this->_o_email_builder;
    }
    /**
     * Executes CURL call with given parameters.
     *
     * @param string $sUrl Server address to call to.
     * @param string $sXml Data to send. Currently OXID servers only accept XML formatted data.
     *
     * @return string
     */
    private function execute_curl_call($s_url, $s_xml)
    {
        $o_curl = $this->get_curl();
        $o_curl->set_method('POST');
        $o_curl->set_url($s_url);
        $o_curl->set_query(http_build_query(['xmlRequest' => $s_xml]));
        $o_curl->set_option(\Oxid_Esales\Eshop\Core\Curl::EXECUTION_TIMEOUT_OPTION, static::CURL_EXECUTION_TIMEOUT);
        return $o_curl->execute();
    }
    /**
     * Sends an email with server information.
     *
     * @param string $sBody Mail content.
     */
    private function send_email($s_body): void
    {
        $o_email = $this->get_email_builder()->build($s_body);
        $o_email->send();
    }
    /**
     * Resets config parameter iFailedOnlineCallsCount if it's bigger than 0.
     *
     * @param int $iFailedOnlineCallsCount Amount of calls which previously failed.
     */
    private function reset_failed_calls_count($i_failed_online_calls_count): void
    {
        if ($i_failed_online_calls_count > 0) {
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_system_config_parameter('int', 'iFailedOnlineCallsCount', 0);
        }
    }
    /**
     * increases failed calls count.
     *
     * @param int $iFailedOnlineCallsCount Amount of calls which previously failed.
     */
    private function increase_failed_calls_count($i_failed_online_calls_count): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config()->save_system_config_parameter('int', 'iFailedOnlineCallsCount', ++$i_failed_online_calls_count);
    }
}