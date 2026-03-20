<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Str;
/**
 * class for output processing
 */
class Output extends \Oxid_Esales\Eshop\Core\Base
{
    public const OUTPUT_FORMAT_HTML = 'html';
    public const OUTPUT_FORMAT_JSON = 'json';
    /**
     * Keels search engine status
     *
     * @var bool
     */
    protected $_bl_search_engine = false;
    /**
     * page charset
     *
     * @var string
     */
    protected $_s_charset;
    /**
     * output format (html(default)/json)
     *
     * @var string
     */
    protected $_s_output_format = self::OUTPUT_FORMAT_HTML;
    /**
     * output buffer (e.g. for json)
     *
     * @var array
     */
    protected $_a_buffer = [];
    /**
     * Class constructor. Sets search engine mode according to client info
     */
    public function __construct()
    {
        $this->set_is_search_engine(\Oxid_Esales\Eshop\Core\Registry::get_utils()->is_search_engine());
    }
    /**
     * Search engine mode setter
     *
     * @param bool $blOn search engine mode
     */
    public function set_is_search_engine($bl_on): void
    {
        $this->_bl_search_engine = $bl_on;
    }
    /**
     * function for front-end (normaly HTML) output processing
     * This function is called from index.php
     *
     * @param string $sValue     value
     * @param string $sClassName classname
     *
     * @return string
     */
    public function process($s_value, $s_class_name)
    {
        return $s_value;
    }
    /**
     * Add a version tag to a html page
     *
     * @param string $sOutput htmlheader
     *
     * @return string
     */
    final public function add_version_tags($s_output)
    {
        // DISPLAY IT
        $s_edition = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_full_edition();
        $s_cur_year = date('Y');
        // Replacing only once per page
        $s_search = '</head>';
        $s_replace = "</head>\n  <!-- OXID eShop {$s_edition}, Shopping Cart System (c) OXID eSales AG 2003 - {$s_cur_year} - https://www.oxid-esales.com -->";
        $s_output = ltrim($s_output);
        if (($pos = stripos($s_output, $s_search)) !== false) {
            return substr_replace($s_output, $s_replace, $pos, strlen($s_search));
        }
        return $s_output;
    }
    /**
     * Abstract function for template tag processing
     * This function is called from index.php
     *
     * @param array  $aViewData  viewarray
     * @param string $sClassName classname
     *
     * @return array
     */
    public function process_view_array($a_view_data, $s_class_name)
    {
        return $a_view_data;
    }
    /**
     * This function is called from index.php
     *
     * @param object $oEmail email object
     */
    public function process_email(&$o_email): void
    {
        // #669 PHP5 claims that you cant pas full this but should instead pass reference what is anyway a much better idea
        // removed "return" as by reference you dont need any return
    }
    /**
     * set page charset
     *
     * @param string $sCharset charset to send with headers
     */
    public function set_charset($s_charset): void
    {
        $this->_s_charset = $s_charset;
    }
    /**
     * set page output format
     *
     * @param string $sFormat html or json
     */
    public function set_output_format($s_format): void
    {
        $this->_s_output_format = $s_format;
    }
    /**
     * output data
     *
     * @param string $sName  output name (used in json mode)
     * @param string $output output text/data
     */
    public function output($s_name, $output): void
    {
        switch ($this->_s_output_format) {
            case self::OUTPUT_FORMAT_JSON:
                $this->_a_buffer[$s_name] = $output;
                break;
            case self::OUTPUT_FORMAT_HTML:
            default:
                echo $output;
                break;
        }
    }
    /**
     * flush pending output
     */
    public function flush_output(): void
    {
        switch ($this->_s_output_format) {
            case self::OUTPUT_FORMAT_JSON:
                echo Str::get_str()->json_encode($this->_a_buffer);
                break;
            case self::OUTPUT_FORMAT_HTML:
            default:
                break;
        }
    }
    /**
     * send page headers (content type, charset)
     */
    public function send_headers(): void
    {
        match ($this->_s_output_format) {
            self::OUTPUT_FORMAT_JSON => \Oxid_Esales\Eshop\Core\Registry::get_utils()->set_header('Content-Type: application/json; charset=' . $this->_s_charset),
            default => \Oxid_Esales\Eshop\Core\Registry::get_utils()->set_header('Content-Type: text/html; charset=' . $this->_s_charset),
        };
    }
    /**
     * Forms Shop mode name.
     *
     * @return string
     */
    protected function get_shop_mode()
    {
        return '';
    }
}