<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Seo encoder base
 */
class Seo_Decoder extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * _parseStdUrl parses given url into array of params
     *
     * @param string $sUrl given url
     *
     * @access protected
     * @return array
     */
    public function parse_std_url($s_url)
    {
        $o_str = Str::get_str();
        $a_ret = [];
        $s_url = $o_str->html_entity_decode($s_url);
        if (($i_pos = strpos((string) $s_url, '?')) !== false) {
            parse_str((string) $o_str->substr($s_url, $i_pos + 1), $a_ret);
        }
        return $a_ret;
    }
    /**
     * Returns ident (md5 of seo url) to fetch seo data from DB
     *
     * @param string $sSeoUrl  seo url to calculate ident
     * @param bool   $blIgnore if FALSE - blocks from direct access when default language seo url with language ident executed
     *
     * @return string
     */
    protected function get_ident($s_seo_url, $bl_ignore = false)
    {
        return md5(strtolower($s_seo_url));
    }
    /**
     * decodeUrl decodes given url into oxid eShop required parameters which are returned as array
     *
     * @param string $seoUrl SEO url
     *
     * @access        public
     * @return array || false
     */
    public function decode_url($seo_url)
    {
        $string_object = Str::get_str();
        $base_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url();
        if ($string_object->strpos($seo_url, $base_url) === 0) {
            $seo_url = $string_object->substr($seo_url, $string_object->strlen($base_url));
        }
        $seo_url = rawurldecode((string) $seo_url);
        //extract page number from seo url
        [$seo_url, $page_number] = $this->extract_page_number_from_seo_url($seo_url);
        $shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        $key = $this->get_ident($seo_url);
        $url_parameters = false;
        $database = Database_Provider::get_db();
        $result_set = $database->select('select oxstdurl, oxlang from oxseo where oxident = :oxident and oxshopid = :oxshopid limit 1', ['oxident' => $key, 'oxshopid' => $shop_id]);
        if (!$result_set->EOF) {
            // primary seo language changed ?
            $url_parameters = $this->parse_std_url($result_set->fields['oxstdurl']);
            $url_parameters['lang'] = $result_set->fields['oxlang'];
        }
        if (is_array($url_parameters) && !is_null($page_number) && $page_number > 0) {
            $url_parameters['pgNr'] = $page_number;
        }
        return $url_parameters;
    }
    /**
     * Checks if url is stored in history table and if it was found - tries
     * to fetch new url from seo table
     *
     * @param string $seoUrl SEO url
     *
     * @access         public
     * @return string || false
     */
    protected function decode_old_url($seo_url)
    {
        $string_object = Str::get_str();
        $database = Database_Provider::get_db();
        $base_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url();
        if ($string_object->strpos($seo_url, $base_url) === 0) {
            $seo_url = $string_object->substr($seo_url, $string_object->strlen($base_url));
        }
        $shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        $seo_url = rawurldecode((string) $seo_url);
        //extract page number from seo url
        [$seo_url, $page_number] = $this->extract_page_number_from_seo_url($seo_url);
        $key = $this->get_ident($seo_url, true);
        $url = false;
        $result_set = $database->select('select oxobjectid, oxlang from oxseohistory where oxident = :oxident and oxshopid = :oxshopid limit 1', ['oxident' => $key, 'oxshopid' => $shop_id]);
        if (!$result_set->EOF) {
            // updating hit info (oxtimestamp field will be updated automatically)
            $database->execute('update oxseohistory set oxhits = oxhits + 1 where oxident = :oxident and oxshopid = :oxshopid limit 1', ['oxident' => $key, 'oxshopid' => $shop_id]);
            // fetching new url
            $url = $this->get_seo_url($result_set->fields['oxobjectid'], $result_set->fields['oxlang'], $shop_id);
            // appending with $_SERVER["QUERY_STRING"]
            $url = $this->add_query_string($url);
        }
        if ($url && !is_null($page_number)) {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->append_url($url, ['pgNr' => $page_number]);
        }
        return $url;
    }
    /**
     * Appends and returns given url with $_SERVER["QUERY_STRING"] value
     *
     * @param string $sUrl url to append
     *
     * @return string
     */
    protected function add_query_string($s_url)
    {
        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
            $s_url = rtrim($s_url, '&?');
            $s_q = ltrim((string) $_SERVER['QUERY_STRING'], '&?');
            $s_url .= !str_contains($s_url, '?') ? '?' : '&';
            $s_url .= $s_q;
        }
        return $s_url;
    }
    /**
     * retrieve SEO url by its object id
     * normally used for getting the redirect url from seo history
     *
     * @param string $sObjectId object id
     * @param int    $iLang     language to fetch
     * @param int    $iShopId   shop id
     *
     * @return ?string
     */
    protected function get_seo_url($s_object_id, $i_lang, $i_shop_id)
    {
        $o_db = Database_Provider::get_db();
        $a_info = $o_db->get_row('select oxseourl, oxtype from oxseo where oxobjectid = :oxobjectid and oxlang = :oxlang and oxshopid = :oxshopid order by oxparams limit 1', ['oxobjectid' => $s_object_id, 'oxlang' => $i_lang, 'oxshopid' => $i_shop_id]);
        if (isset($a_info['oxtype']) && 'oxarticle' == $a_info['oxtype']) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_main_cat_id = $o_db->get_one('select oxcatnid from ' . $table_view_name_generator->get_view_name('oxobject2category') . ' where oxobjectid = :oxobjectid order by oxtime', ['oxobjectid' => $s_object_id]);
            if ($s_main_cat_id) {
                $s_url = $o_db->get_one('select oxseourl from oxseo where oxobjectid = :oxobjectid and oxlang = :oxlang and oxshopid = :oxshopid  and oxparams = :oxparams order by oxexpired', ['oxobjectid' => $s_object_id, 'oxlang' => $i_lang, 'oxshopid' => $i_shop_id, 'oxparams' => $s_main_cat_id]);
                if ($s_url) {
                    return $s_url;
                }
            }
        }
        return $a_info['oxseourl'] ?? null;
    }
    /**
     * processSeoCall handles Server information and passes it to decoder
     *
     * @param string $sRequest request
     * @param string $sPath    path
     *
     * @access public
     */
    public function process_seo_call($s_request = null, $s_path = null): void
    {
        // first - collect needed parameters
        if (!$s_request) {
            if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) {
                $s_request = $_SERVER['REQUEST_URI'];
            } else {
                // try something else
                $s_request = $_SERVER['SCRIPT_URI'] ?? null;
            }
        }
        $s_path = $s_path ?: str_replace('oxseo.php', '', $_SERVER['SCRIPT_NAME']);
        if ($s_params = $this->get_params($s_request, $s_path)) {
            // in case SEO url is actual
            if (is_array($a_get = $this->decode_url($s_params))) {
                $_GET = array_merge($a_get, $_GET);
                \Oxid_Esales\Eshop\Core\Registry::get_lang()->reset_base_language();
            } elseif ($s_redirect_url = $this->decode_old_url($s_params)) {
                // in case SEO url was changed - redirecting to new location
                \Oxid_Esales\Eshop\Core\Registry::get_utils()->redirect(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url() . $s_redirect_url, false, 301);
            } elseif ($s_redirect_url = $this->decode_simple_url($s_params)) {
                // old type II seo urls
                \Oxid_Esales\Eshop\Core\Registry::get_utils()->redirect(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url() . $s_redirect_url, false, 301);
            } else {
                \Oxid_Esales\Eshop\Core\Registry::get_session()->start();
                // unrecognized url
                error_404_handler($s_params);
            }
        }
    }
    /**
     * Tries to fetch SEO url according to type II seo url data. If no
     * specified data is found NULL will be returned
     *
     * @param string $sParams request params (url chunk)
     *
     * @return string
     */
    protected function decode_simple_url($s_params)
    {
        $s_last_param = trim($s_params, '/');
        // active object id
        $s_url = null;
        if ($s_last_param) {
            $i_language = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
            // article ?
            if (str_contains($s_last_param, '.htm')) {
                $s_url = $this->get_object_url($s_last_param, 'oxarticles', $i_language, 'oxarticle');
            } else if (!$s_url = $this->get_object_url($s_last_param, 'oxcategories', $i_language, 'oxcategory')) {
                // maybe manufacturer ?
                if (!$s_url = $this->get_object_url($s_last_param, 'oxmanufacturers', $i_language, 'oxmanufacturer')) {
                    // then maybe vendor ?
                    $s_url = $this->get_object_url($s_last_param, 'oxvendor', $i_language, 'oxvendor');
                }
            }
        }
        return $s_url;
    }
    /**
     * Searches and returns (if available) current objects seo url
     *
     * @param string $sSeoId    ident (or last chunk of url)
     * @param string $sTable    name of table to look for data
     * @param int    $iLanguage current language identifier
     * @param string $sType     type of object to search in seo table
     *
     * @return string
     */
    protected function get_object_url($s_seo_id, $s_table, $i_language, $s_type)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name($s_table, $i_language);
        // first checking of field exists at all
        if ($o_db->get_one("show columns from {$s_table} where field = 'oxseoid'")) {
            // if field exists - searching for object id
            if ($s_object_id = $o_db->get_one("select oxid from {$s_table} where oxseoid = :oxseoid", ['oxseoid' => $s_seo_id])) {
                return $o_db->get_one('select oxseourl from oxseo where oxtype = :oxtype and oxobjectid = :oxobjectid and oxlang = :oxlang', ['oxtype' => $s_type, 'oxobjectid' => $s_object_id, 'oxlang' => $i_language]);
            }
        }
    }
    /**
     * Extracts SEO paramteters and returns as array
     *
     * @param string $sRequest request
     * @param string $sPath    path
     *
     * @return array $aParams extracted params
     */
    protected function get_params($s_request, $s_path)
    {
        $o_str = Str::get_str();
        $s_params = $o_str->preg_replace('/\?.*/', '', $s_request);
        $s_path = preg_quote($s_path, '/');
        $s_params = $o_str->preg_replace("/^{$s_path}/", '', $s_params);
        // this should not happen on most cases, because this redirect is handled by .htaccess
        if ($s_params && !$o_str->preg_match('/\.html$/', $s_params) && !$o_str->preg_match('/\/$/', $s_params)) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->redirect(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url() . $s_params . '/', false, 301);
        }
        return $s_params;
    }
    /**
     * Splits seo url into:
     *     - seo url without page number
     *     - page number
     *
     *
     */
    private function extract_page_number_from_seo_url(string $seo_url): array
    {
        $page_number = null;
        if (1 === preg_match('/(.*?)\/(\d+)\/(.*)/', $seo_url, $matches)) {
            $seo_url = $matches[1] . '/' . $matches[3];
            $page_number = $this->convert_seo_page_string_to_actual_page_number($matches[2]);
        }
        return [$seo_url, $page_number];
    }
    /**
     * Converts seo url pagination number to actual page number.
     *
     *
     * @return int
     */
    private function convert_seo_page_string_to_actual_page_number(string $seo_page_number)
    {
        if (!is_null($seo_page_number)) {
            return max(0, (int) $seo_page_number - 1);
        }
        return $seo_page_number;
    }
}