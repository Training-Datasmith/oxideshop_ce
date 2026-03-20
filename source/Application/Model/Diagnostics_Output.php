<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Diagnostic tool result outputer
 * Performs OutputKey check of shop files and generates report file.
 */
class Diagnostics_Output
{
    /**
     * result key
     *
     * @var string
     */
    protected $_s_output_key = 'diagnostic_tool_result';
    /**
     * Result file path
     *
     * @var string
     */
    protected $_s_output_file_name = 'diagnostic_tool_result.html';
    /**
     * Utils object
     *
     * @var mixed
     */
    protected $_o_utils;
    /**
     * Object constructor
     */
    public function __construct()
    {
        $this->_o_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
    }
    /**
     * OutputKey setter
     *
     * @param string $sOutputKey Output key.
     */
    public function set_output_key($s_output_key): void
    {
        if (!empty($s_output_key)) {
            $this->_s_output_key = $s_output_key;
        }
    }
    /**
     * OutputKey getter
     *
     * @return string
     */
    public function get_output_key()
    {
        return $this->_s_output_key;
    }
    /**
     * OutputFileName setter
     *
     * @param string $sOutputFileName Output file name.
     */
    public function set_output_file_name($s_output_file_name): void
    {
        if (!empty($s_output_file_name)) {
            $this->_s_output_file_name = $s_output_file_name;
        }
    }
    /**
     * OutputKey getter
     *
     * @return string
     */
    public function get_output_file_name()
    {
        return $this->_s_output_file_name;
    }
    /**
     * Stores result file in file cache
     *
     * @param string $sResult Result.
     */
    public function store_result($s_result): void
    {
        $this->_o_utils->to_file_cache($this->_s_output_key, $s_result);
    }
    /**
     * Reads exported result file contents
     *
     * @param string $sOutputKey Output key.
     *
     * @return string
     */
    public function read_result_file($s_output_key = null)
    {
        $s_current_key = empty($s_output_key) ? $this->_s_output_key : $s_output_key;
        return $this->_o_utils->from_file_cache($s_current_key);
    }
    /**
     * Sends generated file for download
     *
     * @param string $sOutputKey Output key.
     */
    public function download_result_file($s_output_key = null): void
    {
        $s_current_key = empty($s_output_key) ? $this->_s_output_key : $s_output_key;
        $this->_o_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        $content = $this->_o_utils->from_file_cache($s_current_key);
        $content_length = strlen((string) $content);
        $this->_o_utils->set_header('Pragma: public');
        $this->_o_utils->set_header('Expires: 0');
        $this->_o_utils->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0, private');
        $this->_o_utils->set_header('Content-Disposition: attachment;filename=' . $this->_s_output_file_name);
        $this->_o_utils->set_header('Content-Type:text/html;charset=utf-8');
        if ($content_length) {
            $this->_o_utils->set_header('Content-Length: ' . $content_length);
        }
        echo $content;
    }
}