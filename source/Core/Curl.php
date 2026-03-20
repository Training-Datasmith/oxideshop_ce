<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * CURL request handler.
 * Handles CURL calls
 */
class Curl
{
    /** Curl option for setting the timeout of whole execution process. */
    public const EXECUTION_TIMEOUT_OPTION = 'CURLOPT_TIMEOUT';
    /** Curl option for setting the timeout for connect. */
    public const CONNECT_TIMEOUT_OPTION = 'CURLOPT_CONNECTTIMEOUT';
    /**
     * Curl instance.
     *
     * @var resource
     */
    protected $_r_curl;
    /**
     * URL to call
     *
     * @var string|null
     */
    protected $_s_url;
    /**
     * Query like "param1=value1&param2=values2.."
     *
     * @return string
     */
    protected $_s_query;
    /**
     * Set CURL method
     *
     * @return string
     */
    protected $_s_method = 'POST';
    /**
     * Parameter to be added to call url
     *
     * @var array|null
     */
    protected $_a_parameters;
    /**
     * Connection Charset.
     *
     * @var string
     */
    protected $_s_connection_charset = 'UTF-8';
    /**
     * Curl call header.
     *
     * @var array
     */
    protected $_a_header;
    /**
     * Host for header.
     *
     * @var string
     */
    protected $_s_host;
    /**
     * Curl Options
     *
     * @var array
     */
    protected $_a_options = ['CURLOPT_RETURNTRANSFER' => 1];
    /**
     * Request HTTP status call code.
     *
     * @var int|null
     */
    protected $_s_status_code;
    /**
     * Sets url to call
     *
     * @param string $url URL to call.
     */
    public function set_url($url): void
    {
        $this->_s_url = $url;
    }
    /**
     * Get url
     *
     * @return string
     */
    public function get_url()
    {
        if ($this->get_method() == 'GET' && $this->get_query()) {
            $this->_s_url = $this->_s_url . '?' . $this->get_query();
        }
        return $this->_s_url;
    }
    /**
     * Set query like "param1=value1&param2=values2.."
     *
     * @param string $query Request query.
     */
    public function set_query($query): void
    {
        $this->_s_query = $query;
    }
    /**
     * Builds query like "param1=value1&param2=values2.."
     *
     * @return string
     */
    public function get_query()
    {
        if (is_null($this->_s_query)) {
            $query = '';
            if ($params = $this->get_parameters()) {
                $params = $this->prepare_query_parameters($params);
                $query = http_build_query($params, '', '&');
            }
            $this->set_query($query);
        }
        return $this->_s_query;
    }
    /**
     * Sets parameters to be added to call url.
     *
     * @param array $parameters parameters
     */
    public function set_parameters($parameters): void
    {
        $this->set_query(null);
        $this->_a_parameters = $parameters;
    }
    /**
     * Return parameters to be added to call url.
     *
     * @return array
     */
    public function get_parameters()
    {
        return $this->_a_parameters;
    }
    /**
     * Sets host.
     *
     * @param string $host
     */
    public function set_host($host): void
    {
        $this->_s_host = $host;
    }
    /**
     * Returns host.
     *
     * @return string
     */
    public function get_host()
    {
        return $this->_s_host;
    }
    /**
     * Set header.
     *
     * @param array $header
     */
    public function set_header($header = null): void
    {
        if (is_null($header) && $this->get_method() == 'POST') {
            $host = $this->get_host();
            $header = [];
            $header[] = 'POST /cgi-bin/webscr HTTP/1.1';
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            if (isset($host)) {
                $header[] = 'Host: ' . $host;
            }
            $header[] = 'Connection: close';
        }
        $this->_a_header = $header;
    }
    /**
     * Forms header from host.
     *
     * @return array
     */
    public function get_header()
    {
        if (is_null($this->_a_header)) {
            $this->set_header();
        }
        return $this->_a_header;
    }
    /**
     * Set method to send (POST/GET)
     *
     * @param string $method method to send (POST/GET)
     */
    public function set_method($method): void
    {
        $this->_s_method = strtoupper($method);
    }
    /**
     * Return method to send
     *
     * @return string
     */
    public function get_method()
    {
        return $this->_s_method;
    }
    /**
     * Sets an option for a cURL transfer
     *
     * @param string $name  curl option name to set value to.
     * @param string $value curl option value to set.
     *
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException curl errors
     */
    public function set_option($name, $value): void
    {
        if (!str_starts_with($name, 'CURLOPT_') || !defined($constant = strtoupper($name))) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class);
            $lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
            $exception->set_message(sprintf($lang->translate_string('EXCEPTION_NOT_VALID_CURL_CONSTANT', $lang->get_tpl_language()), $name));
            throw $exception;
        }
        $this->_a_options[$name] = $value;
    }
    /**
     * Gets all options for a cURL transfer
     *
     * @return array
     */
    public function get_options()
    {
        return $this->_a_options;
    }
    /**
     * Executes curl call and returns response data as associative array.
     *
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException on curl errors
     */
    public function execute(): string
    {
        $this->set_options();
        $response = $this->execute_curl();
        $this->save_status_code();
        $curl_error_number = $this->get_error_number();
        $this->close();
        if ($curl_error_number) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class);
            $lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
            $exception->set_message(sprintf($lang->translate_string('EXCEPTION_CURL_ERROR', $lang->get_tpl_language()), $curl_error_number));
            throw $exception;
        }
        return $response;
    }
    /**
     * Set connection charset
     *
     * @param string $charset charset
     */
    public function set_connection_charset($charset): void
    {
        $this->_s_connection_charset = $charset;
    }
    /**
     * Return connection charset
     *
     * @return string
     */
    public function get_connection_charset()
    {
        return $this->_s_connection_charset;
    }
    /**
     * Return HTTP status code.
     *
     * @return int HTTP status code.
     */
    public function get_status_code()
    {
        return $this->_s_status_code;
    }
    /**
     * Sets resource
     *
     * @param resource $rCurl curl.
     */
    protected function set_resource($r_curl)
    {
        $this->_r_curl = $r_curl;
    }
    /**
     * Returns curl resource
     *
     * @return resource
     */
    protected function get_resource()
    {
        if (is_null($this->_r_curl)) {
            $this->set_resource(curl_init());
        }
        return $this->_r_curl;
    }
    /**
     * Set Curl Options
     */
    protected function set_options()
    {
        if (!is_null($this->get_header())) {
            $this->set_opt(CURLOPT_HTTPHEADER, $this->get_header());
        }
        $this->set_opt(CURLOPT_URL, $this->get_url());
        if ($this->get_method() == 'POST') {
            $this->set_opt(CURLOPT_POST, 1);
            $this->set_opt(CURLOPT_POSTFIELDS, $this->get_query());
        }
        $options = $this->get_options();
        if (count($options)) {
            foreach ($options as $name => $m_value) {
                $this->set_opt(constant($name), $m_value);
            }
        }
    }
    /**
     * Wrapper function to be mocked for testing.
     */
    protected function execute_curl(): string
    {
        return curl_exec($this->get_resource());
    }
    /**
     * Wrapper function to be mocked for testing.
     */
    protected function close()
    {
        $this->set_resource(null);
    }
    /**
     * Wrapper function to be mocked for testing.
     *
     * @param string $name  curl option name to set value to.
     * @param string $value curl option value to set.
     */
    protected function set_opt($name, $value)
    {
        curl_setopt($this->get_resource(), $name, $value);
    }
    /**
     * Check if curl has errors. Set error message if has.
     */
    protected function get_error_number(): int
    {
        return curl_errno($this->get_resource());
    }
    /**
     * Sets current request HTTP status code.
     */
    protected function save_status_code()
    {
        $this->_s_status_code = curl_getinfo($this->get_resource(), CURLINFO_HTTP_CODE);
    }
    /**
     * Decodes html entities.
     *
     * @param array $params Parameters.
     */
    protected function prepare_query_parameters($params): array
    {
        return array_map($this->html_decode(...), array_filter($params));
    }
    /**
     * Decode (if needed) html entity.
     *
     * @param mixed $mParam query
     *
     * @return string
     */
    protected function html_decode($m_param): array|string
    {
        if (is_array($m_param)) {
            return $this->prepare_query_parameters($m_param);
        }
        return html_entity_decode(stripslashes((string) $m_param), ENT_QUOTES, $this->get_connection_charset());
    }
}