<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Str;
/**
 * Request represents an HTTP request.
 */
class Request
{
    /**
     * @deprecated use RequestInterface::get() instead
     *
     * Returns raw value of parameter stored in POST,GET.
     *
     * @param string $name         Name of parameter.
     * @param string $defaultValue Default value if no value provided.
     *
     * @return mixed
     */
    public function get_request_parameter($name, $default_value = null)
    {
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
        } else {
            $value = $default_value;
        }
        return $value;
    }
    /**
     * Returns escaped value of parameter stored in POST,GET.
     *
     * @param string $name         Name of parameter.
     * @param string $defaultValue Default value if no value provided.
     *
     * @return mixed
     */
    public function get_request_escaped_parameter($name, $default_value = null)
    {
        $value = $this->get_request_parameter($name, $default_value);
        // TODO: remove this after special chars concept implementation
        $is_admin = Registry::get_config()->is_admin() && Registry::get_session()->get_variable('blIsAdmin');
        if ($value !== null && !$is_admin) {
            $this->check_param_special_chars($value);
        }
        return $value;
    }
    /**
     * Returns request url, which was executed to render current page view
     *
     * @param string $sParams     Parameters to object
     * @param bool   $blReturnUrl If return url
     *
     * @return string
     */
    public function get_request_url($s_params = '', $bl_return_url = false): string|array
    {
        $request_url = '';
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
            if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) {
                $raw_request_url = $_SERVER['REQUEST_URI'];
            } else {
                $raw_request_url = $_SERVER['SCRIPT_URI'] ?? '';
            }
            // trying to resolve controller file name
            if ($raw_request_url && ($i_pos = stripos((string) $raw_request_url, '?')) !== false) {
                $string = Str::get_str();
                // formatting request url
                $request_url = 'index.php' . $string->substr($raw_request_url, $i_pos);
                // removing possible session id
                $request_url = $string->preg_replace('/(&|\?)(force_)?(admin_)?sid=[^&]*&?/', '$1', $request_url);
                $request_url = $string->preg_replace('/(&|\?)stoken=[^&]*&?/', '$1', $request_url);
                $request_url = $string->preg_replace('/&$/', '', $request_url);
                $request_url = str_replace('&', '&amp;', $request_url);
            }
        }
        return $request_url;
    }
    /**
     * Checks if passed parameter has special chars and replaces them.
     * Returns checked value.
     *
     * @param mixed $sValue value to process escaping
     * @param array $aRaw   keys of unescaped values
     *
     * @return mixed
     */
    public function check_param_special_chars(&$s_value, $a_raw = null)
    {
        if (is_object($s_value)) {
            return $s_value;
        }
        if (is_array($s_value)) {
            $new_value = [];
            foreach ($s_value as $s_key => $s_val) {
                $s_valid_key = $s_key;
                if (!$a_raw || !in_array($s_key, $a_raw)) {
                    $this->check_param_special_chars($s_valid_key);
                    $this->check_param_special_chars($s_val);
                    if ($s_valid_key != $s_key) {
                        unset($s_value[$s_key]);
                    }
                }
                $new_value[$s_valid_key] = $s_val;
            }
            $s_value = $new_value;
        } elseif (is_string($s_value)) {
            $s_value = str_replace(['&', '<', '>', '"', "'", chr(0), '\\', "\n", "\r"], ['&amp;', '&lt;', '&gt;', '&quot;', '&#039;', '', '&#092;', '&#10;', '&#13;'], $s_value);
        }
        return $s_value;
    }
}