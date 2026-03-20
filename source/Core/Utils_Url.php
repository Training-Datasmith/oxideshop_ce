<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * URL utility class
 */
class Utils_Url extends \Oxid_Esales\Eshop\Core\Base
{
    public const PARAMETER_SEPARATOR = '&amp;';
    /**
     * Additional url parameters which should be appended to seo/std urls.
     *
     * @var array
     */
    protected $_a_add_url_params;
    /**
     * Current shop hosts array.
     *
     * @var array
     */
    protected $_a_hosts;
    /**
     * Returns core parameters which must be added to each url.
     *
     * @return array
     */
    public function get_base_add_url_params()
    {
        return [];
    }
    /**
     * Returns parameters which should be appended to seo or std url.
     *
     * @return array
     */
    public function get_add_url_params()
    {
        if ($this->_a_add_url_params === null) {
            $this->_a_add_url_params = $this->get_base_add_url_params();
            // appending currency
            if ($i_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_currency()) {
                $this->_a_add_url_params['cur'] = $i_cur;
            }
        }
        return $this->_a_add_url_params;
    }
    /**
     * prepareUrlForNoSession adds extra url params making it usable without session
     * also removes sid=xxxx&.
     *
     * @param string $sUrl given url
     *
     * @access public
     * @return string
     */
    public function prepare_url_for_no_session($s_url)
    {
        $o_str = Str::get_str();
        // cleaning up session id..
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)(force_)?(admin_)?sid=[a-z0-9\._]+&?(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(&amp;|\?)$/', '', $s_url);
        if (\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active()) {
            return $s_url;
        }
        if ($qpos = $o_str->strpos($s_url, '?')) {
            if ($qpos == $o_str->strlen($s_url) - 1) {
                $s_sep = '';
            } else {
                $s_sep = '&amp;';
            }
        } else {
            $s_sep = '?';
        }
        if (!$o_str->preg_match('/[&?](amp;)?lang=[0-9]+/i', $s_url)) {
            $s_url .= "{$s_sep}lang=" . \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
            $s_sep = '&amp;';
        }
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if (!$o_str->preg_match('/[&?](amp;)?cur=[0-9]+/i', $s_url)) {
            $i_cur = (int) $o_config->get_shop_currency();
            if ($i_cur) {
                $s_url .= "{$s_sep}cur=" . $i_cur;
            }
        }
        return $s_url;
    }
    /**
     * Prepares canonical url.
     *
     * @param string $sUrl given url
     *
     * @access public
     * @return string
     */
    public function prepare_canonical_url($s_url)
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $o_str = Str::get_str();
        // cleaning up session id..
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)(force_)?(admin_)?sid=[a-z0-9\._]+&?(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(&amp;|\?)$/', '', $s_url);
        $s_sep = $o_str->strpos($s_url, '?') === false ? '?' : '&amp;';
        if (!\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active()) {
            // non seo url has no language identifier..
            $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
            if (!$o_str->preg_match('/[&?](amp;)?lang=[0-9]+/i', $s_url) && $i_lang != $o_config->get_config_param('sDefaultLang')) {
                $s_url .= "{$s_sep}lang=" . $i_lang;
            }
        }
        return $s_url;
    }
    /**
     * Appends url with given parameters.
     *
     * @param string $sUrl                    url to append
     * @param array  $parametersToAdd         parameters to append
     * @param bool   $blFinalUrl              final url
     * @param bool   $allowParameterOverwrite Decides if same parameters should overwrite query parameters.
     *
     * @return string
     */
    public function append_url($s_url, $parameters_to_add, $bl_final_url = false, $allow_parameter_overwrite = false)
    {
        $param_separator = self::PARAMETER_SEPARATOR;
        $final_parameters = $this->remove_not_set_parameters($parameters_to_add);
        if (is_array($final_parameters) && !empty($final_parameters)) {
            $url_without_query = $s_url;
            $separator_place = strpos($s_url, '?');
            if ($separator_place !== false) {
                $url_without_query = substr($s_url, 0, $separator_place);
                $url_query_escaped = substr($s_url, $separator_place + 1);
                $url_query = str_replace($param_separator, '&', $url_query_escaped);
                $final_parameters = $this->merge_duplicated_parameters($final_parameters, $url_query, $allow_parameter_overwrite);
            }
            $s_url = $this->append_param_separator($url_without_query);
            $s_url .= http_build_query($final_parameters, '', $param_separator);
        }
        if ($s_url && !$bl_final_url) {
            return $this->append_param_separator($s_url);
        }
        return $s_url;
    }
    /**
     * Removes any or specified dynamic parameter from given url.
     *
     * @param string $sUrl    url to clean.
     * @param array  $aParams parameters to remove [optional].
     *
     * @return string
     */
    public function clean_url($s_url, $a_params = null)
    {
        $o_str = Str::get_str();
        if (is_array($a_params)) {
            foreach ($a_params as $s_param) {
                $s_url = $o_str->preg_replace('/(\?|&(amp;)?)' . preg_quote((string) $s_param) . '=[a-z0-9\.]+&?(amp;)?/i', '\1', $s_url);
            }
        } else {
            $s_url = $o_str->preg_replace('/(\?|&(amp;)?).+/i', '\1', $s_url);
        }
        return trim((string) $s_url, '?');
    }
    public function add_shop_host($url)
    {
        if (!preg_match('#^https?://#i', (string) $url)) {
            return Container_Facade::get_parameter('oxid_esales.shop_url') . $url;
        }
        return $url;
    }
    /**
     * Performs base url processing - adds required parameters to given url.
     *
     * @param string $sUrl       url to process.
     * @param bool   $blFinalUrl should url be finalized or should it end with ? or &amp; (default true).
     * @param array  $aParams    additional parameters (default null).
     * @param int    $iLang      url target language (default null).
     *
     * @return string
     */
    public function process_url($s_url, $bl_final_url = true, $a_params = null, $i_lang = null)
    {
        $s_url = $this->append_url($s_url, $a_params, $bl_final_url);
        if ($this->is_current_shop_host($s_url)) {
            return $this->process_shop_url($s_url, $bl_final_url, $i_lang);
        }
        return $s_url;
    }
    /**
     * Adds additional shop url parameters, session id, language id when needed.
     *
     * @param string $sUrl       url to process.
     * @param bool   $blFinalUrl should url be finalized or should it end with ? or &amp;.
     * @param int    $iLang      url target language.
     *
     * @return string
     */
    public function process_shop_url($s_url, $bl_final_url = true, $i_lang = null)
    {
        $a_add_params = $this->get_add_url_params();
        $s_url = $this->append_url($s_url, $a_add_params, $bl_final_url);
        $s_url = \Oxid_Esales\Eshop\Core\Registry::get_lang()->process_url($s_url, $i_lang);
        $s_url = \Oxid_Esales\Eshop\Core\Registry::get_session()->process_url($s_url);
        if ($bl_final_url) {
            return $this->right_trim_amp($s_url);
        }
        return $s_url;
    }
    /**
     * Method returns active shop host.
     *
     * @return string
     */
    public function get_active_shop_host()
    {
        $shop_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url();
        return $this->extract_host($shop_url);
    }
    /**
     * Extract host from url.
     *
     * @param string $url
     *
     * @return string
     */
    public function extract_host($url)
    {
        return $this->parse_url_and_append_schema($url, PHP_URL_HOST) ?: $url;
    }
    /**
     * Method returns shop URL part - path.
     *
     * @return null|string
     */
    public function get_active_shop_url_path()
    {
        $shop_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url();
        return $this->extract_url_path($shop_url);
    }
    /**
     * Method returns URL part - path.
     *
     * @param string $shopUrl
     *
     * @return string|null
     */
    public function extract_url_path($shop_url)
    {
        return $this->parse_url_and_append_schema($shop_url, PHP_URL_PATH);
    }
    /**
     * Compares current URL to supplied string.
     *
     * @param string $sUrl
     *
     * @return bool true if $sUrl is equal to current page URL.
     */
    public function is_current_shop_host($s_url)
    {
        $bl_current = false;
        $s_url_host = @parse_url($s_url, PHP_URL_HOST);
        // checks if it is relative url.
        if (is_null($s_url_host)) {
            $bl_current = true;
        } else {
            $a_hosts = $this->get_hosts();
            foreach ($a_hosts as $s_host) {
                if ($s_host === $s_url_host) {
                    $bl_current = true;
                    break;
                }
            }
        }
        return $bl_current;
    }
    /**
     * Improved url parsing with parse_url as base and scheme checking improvement in url preprocessing
     *
     * @param string $url
     * @param string $appendScheme Append this scheme to url if no scheme found
     * @return string
     */
    private function parse_url_and_append_schema($url, int $flag, string $append_scheme = 'http'): array|int|string|false|null
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = $append_scheme . '://' . $url;
        }
        return parse_url($url, $flag);
    }
    /**
     * Seo url processor: adds various needed parameters, like currency, shop id.
     *
     * @param string $sUrl url to process.
     *
     * @return string
     */
    public function process_seo_url($s_url)
    {
        if (!$this->is_admin()) {
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            $s_url = $session->process_url($this->append_url($s_url, $this->get_add_url_params()));
        }
        $s_url = $this->clean_url_params($s_url);
        return $this->right_trim_amp($s_url);
    }
    /**
     * Remove duplicate GET parameters and clean &amp; and duplicate &.
     *
     * @param string $sUrl       url to process.
     * @param string $sConnector GET elements connector.
     *
     * @return string
     */
    public function clean_url_params($s_url, $s_connector = '&amp;')
    {
        $a_url_parts = explode('?', $s_url);
        // check for params part
        if (count($a_url_parts) != 2) {
            return $s_url;
        }
        $s_url = $a_url_parts[0];
        $s_url_params = $a_url_parts[1];
        $o_str_utils = Str::get_str();
        $s_url_params = $o_str_utils->preg_replace(['@(\&(amp;){1,})@ix', '@\&{1,}@', '@\?&@x'], ['&', '&', '?'], $s_url_params);
        // remove duplicate entries
        parse_str($s_url_params, $a_url_params);
        $s_url .= '?' . http_build_query($a_url_params, '', $s_connector);
        // replace brackets
        $s_url = str_replace(['%5B', '%5D'], ['[', ']'], $s_url);
        return $s_url;
    }
    /**
     * Appends parameter separator - '?' if it is not in the url or &amp; otherwise.
     *
     * @param string $sUrl url
     *
     * @return string
     */
    public function append_param_separator($s_url)
    {
        return $s_url . $this->get_url_parameters_separator($s_url);
    }
    /**
     * Return current url.
     *
     * @return string
     */
    public function get_current_url()
    {
        $o_utils_server = \Oxid_Esales\Eshop\Core\Registry::get_utils_server();
        $a_server_params['HTTPS'] = $o_utils_server->get_server_var('HTTPS');
        $a_server_params['HTTP_X_FORWARDED_PROTO'] = $o_utils_server->get_server_var('HTTP_X_FORWARDED_PROTO');
        $a_server_params['HTTP_HOST'] = $o_utils_server->get_server_var('HTTP_HOST');
        $a_server_params['REQUEST_URI'] = $o_utils_server->get_server_var('REQUEST_URI');
        $s_protocol = 'http://';
        if (isset($a_server_params['HTTPS']) && ($a_server_params['HTTPS'] == 'on' || $a_server_params['HTTPS'] == 1) || isset($a_server_params['HTTP_X_FORWARDED_PROTO']) && $a_server_params['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $s_protocol = 'https://';
        }
        return $s_protocol . $a_server_params['HTTP_HOST'] . $a_server_params['REQUEST_URI'];
    }
    /**
     * Forms parameters array out of a string.
     * Takes & and &amp; as delimiters.
     * Returns associative array with parameters.
     *
     * @param string $sValue String
     *
     * @return array
     */
    public function string_to_params_array($s_value)
    {
        // url building
        // replace possible ampersands, explode, and filter out empty values
        $s_value = str_replace('&amp;', '&', $s_value);
        $a_nav_params = explode('&', $s_value);
        $a_nav_params = array_filter($a_nav_params);
        $a_params = [];
        foreach ($a_nav_params as $s_value) {
            $exp = explode('=', $s_value);
            $a_params[$exp[0]] = $exp[1] ?? null;
        }
        return $a_params;
    }
    /**
     * Return array of language key and language value.
     *
     * @param integer $languageId
     *
     * @return array
     */
    public function get_url_language_parameter($language_id)
    {
        return [\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_name() => $language_id];
    }
    /**
     * Extracts host from given url and appends $aHosts with it
     *
     * @param string $sUrl   url to extract
     * @param array  $aHosts hosts array
     */
    protected function add_host($s_url, &$a_hosts)
    {
        if ($s_url && $s_host = @parse_url($s_url, PHP_URL_HOST)) {
            if (!in_array($s_host, $a_hosts)) {
                $a_hosts[] = $s_host;
            }
        }
    }
    /**
     * Appends language urls to $aHosts.
     *
     * @param array $aLanguageUrls array of language urls to extract
     * @param array $aHosts        hosts array
     */
    protected function add_language_host($a_language_urls, &$a_hosts)
    {
        $i_language_id = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        if (isset($a_language_urls[$i_language_id])) {
            $this->add_host($a_language_urls[$i_language_id], $a_hosts);
        }
    }
    /**
     * Collects and returns current shop hosts array.
     *
     * @return array
     */
    protected function get_hosts()
    {
        if ($this->_a_hosts === null) {
            $this->_a_hosts = [];
            $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            $this->add_mall_hosts($this->_a_hosts);
            // language url
            $this->add_language_host($o_config->get_config_param('aLanguageURLs'), $this->_a_hosts);
            $this->add_language_host($o_config->get_config_param('aLanguageSSLURLs'), $this->_a_hosts);
            // current url
            $this->add_host(Container_Facade::get_parameter('oxid_esales.shop_url'), $this->_a_hosts);
            if ($this->is_admin()) {
                $this->add_host(Container_Facade::get_parameter('oxid_esales.shop_admin_url'), $this->_a_hosts);
            }
        }
        return $this->_a_hosts;
    }
    /**
     * Appends shop mall urls to $aHosts if needed
     *
     * @param array $aHosts hosts array
     */
    protected function add_mall_hosts(&$a_hosts)
    {
    }
    /**
     * Returns url separator (?,&amp;) for adding new parameters.
     *
     * @param string $url
     */
    private function get_url_parameters_separator($url): string
    {
        $o_str = Str::get_str();
        $url_separator = '&amp;';
        if ($o_str->preg_match('/(\?|&(amp;)?)$/i', $url)) {
            $url_separator = '';
        } elseif ($o_str->strpos($url, '?') === false) {
            $url_separator = '?';
        }
        return $url_separator;
    }
    /**
     * Removes parameters which are not set.
     *
     * @param string $parametersToAdd
     */
    private function remove_not_set_parameters($parameters_to_add): string
    {
        if (is_array($parameters_to_add) && !empty($parameters_to_add)) {
            foreach ($parameters_to_add as $key => $value) {
                if (is_null($value)) {
                    unset($parameters_to_add[$key]);
                }
            }
        }
        return $parameters_to_add;
    }
    /**
     * @param array  $aAddParams              parameters to add to URL
     * @param string $query                   URL query part
     * @param bool   $allowParameterOverwrite Decides if same parameters should overwrite query parameters
     */
    private function merge_duplicated_parameters(array $a_add_params, string|array $query, $allow_parameter_overwrite = true): array
    {
        parse_str($query, $current_url_parameters);
        if ($allow_parameter_overwrite) {
            return array_merge($current_url_parameters, $a_add_params);
        }
        $new_filtered_parameters = array_diff_key($a_add_params, $current_url_parameters);
        return array_merge($current_url_parameters, $new_filtered_parameters);
    }
    /**
     * @param string $url
     *
     * @return string
     */
    private function right_trim_amp($url)
    {
        return Str::get_str()->preg_replace('/(\?|&(amp;)?)$/i', '', $url);
    }
}