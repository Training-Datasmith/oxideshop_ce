<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
/**
 * Seo encoder base
 */
#[\Allow_Dynamic_Properties]
class Seo_Encoder extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Strings that cannot be used in SEO URLs as this may cause
     * compatability/access problems
     *
     * @var array
     */
    protected static $_a_reserved_words = ['admin'];
    /**
     * cache for reserved path root node keys
     *
     * @var array
     */
    protected static $_a_reserved_entry_keys;
    /**
     * SEO separator.
     *
     * @var string
     */
    protected static $_s_separator;
    /**
     * SEO id length.
     *
     * @var integer
     */
    protected $_i_id_length = 255;
    /**
     * SEO prefix.
     *
     * @var string
     */
    protected static $_s_prefix;
    /**
     * Added parameters.
     *
     * @var string
     */
    protected $_s_add_params;
    /**
     * Url fixed state cache
     *
     * @return array
     */
    protected static $_a_fixed_cache = [];
    /**
     * SEO Cache key for active view
     *
     * @var string
     */
    protected static $_s_cache_key;
    /**
     * SEO cache array
     *
     * @var array
     */
    protected static $_a_cache = [];
    /**
     * Maximum seo/dynamic url length
     *
     * @var int
     */
    protected $_i_max_url_length;
    /**
     * Returns part of url defining active language
     *
     * @param string $sSeoUrl seo url
     * @param int    $iLang   language id
     *
     * @return string
     */
    public function add_language_param($s_seo_url, $i_lang)
    {
        $i_lang = (int) $i_lang;
        $i_def_lang = (int) Registry::get_config()->get_config_param('iDefSeoLang');
        $a_lang_ids = Registry::get_lang()->get_language_ids();
        if ($i_lang != $i_def_lang && isset($a_lang_ids[$i_lang]) && Str::get_str()->strpos($s_seo_url, $this->replace_special_chars($a_lang_ids[$i_lang]) . '/') !== 0) {
            return $a_lang_ids[$i_lang] . '/' . $s_seo_url;
        }
        return $s_seo_url;
    }
    /**
     * Processes seo url before saving to db:
     *  - \OxidEsales\Eshop\Core\SeoEncoder::addLanguageParam();
     *  - \OxidEsales\Eshop\Core\SeoEncoder::_getUniqueSeoUrl().
     *
     * @param string $sSeoUrl   seo url to process
     * @param string $sObjectId seo object id [optional]
     * @param int    $iLang     active language id [optional]
     * @param bool   $blExclude exclude language prefix while building seo url
     *
     * @return string
     */
    protected function process_seo_url($s_seo_url, $s_object_id = null, $i_lang = null, $bl_exclude = false)
    {
        if (!$bl_exclude) {
            $s_seo_url = $this->add_language_param($s_seo_url, $i_lang);
        }
        return $this->get_unique_seo_url($s_seo_url, $s_object_id, $i_lang);
    }
    /**
     * SEO encoder constructor
     */
    public function __construct()
    {
        $my_config = Registry::get_config();
        if (!self::$_s_separator) {
            $this->set_separator($my_config->get_config_param('sSEOSeparator'));
        }
        if (!self::$_s_prefix) {
            $this->set_prefix($my_config->get_config_param('sSEOuprefix'));
        }
        $this->set_reserved_words($my_config->get_config_param('aSEOReservedWords'));
    }
    /**
     * Moves current seo record to seo history table
     *
     * @param string $sId     object id
     * @param int    $iShopId active shop id
     * @param int    $iLang   object language
     * @param string $sType   object type (if you pass real object - type is not necessary)
     * @param string $sNewId  new object id, mostly used for static url updates (optional)
     */
    protected function copy_to_history($s_id, $i_shop_id, $i_lang, $s_type = null, $s_new_id = null)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_objectid = $s_new_id ? $o_db->quote($s_new_id) : 'oxobjectid';
        $s_type = $s_type ? 'oxtype =' . $o_db->quote($s_type) . ' and' : '';
        $i_lang = (int) $i_lang;
        // moving
        $s_sub = "select {$s_objectid}, MD5( LOWER( oxseourl ) ), oxshopid, oxlang, now() from oxseo\n                 where {$s_type} oxobjectid = " . $o_db->quote($s_id) . ' and oxshopid = ' . $o_db->quote($i_shop_id) . " and\n                 oxlang = {$i_lang} and oxexpired = '1'";
        $s_q = "replace oxseohistory ( oxobjectid, oxident, oxshopid, oxlang, oxinsert ) {$s_sub}";
        $o_db->execute($s_q);
    }
    /**
     * Generates dynamic url object id (calls \OxidEsales\Eshop\Core\SeoEncoder::_getStaticObjectId)
     *
     * @param int    $iShopId shop id
     * @param string $sStdUrl standard (dynamic) url
     *
     * @return string
     */
    public function get_dynamic_object_id($i_shop_id, $s_std_url)
    {
        return $this->get_static_object_id($i_shop_id, $s_std_url);
    }
    /**
     * Returns dynamic object SEO URI
     *
     * @param string $sStdUrl standard url
     * @param string $sSeoUrl seo uri
     * @param int    $iLang   active language
     *
     * @return string
     */
    protected function get_dynamic_uri($s_std_url, $s_seo_url, $i_lang)
    {
        $i_shop_id = Registry::get_config()->get_shop_id();
        $s_std_url = $this->trim_url($s_std_url);
        $s_object_id = $this->get_dynamic_object_id($i_shop_id, $s_std_url);
        $s_seo_url = $this->prepare_uri($this->add_language_param($s_seo_url, $i_lang), $i_lang);
        //load details link from DB
        $s_old_seo_url = $this->load_from_db('dynamic', $s_object_id, $i_lang);
        if ($s_old_seo_url === $s_seo_url) {
            $s_seo_url = $s_old_seo_url;
        } else {
            if ($s_old_seo_url) {
                // old must be transferred to history
                $this->copy_to_history($s_object_id, $i_shop_id, $i_lang, 'dynamic');
            }
            // creating unique
            $s_seo_url = $this->process_seo_url($s_seo_url, $s_object_id, $i_lang);
            // inserting
            $this->save_to_db('dynamic', $s_object_id, $s_std_url, $s_seo_url, $i_lang, $i_shop_id);
        }
        return $s_seo_url;
    }
    /**
     * Returns SEO url with shop's path + additional params ( \OxidEsales\Eshop\Core\SeoEncoder:: _getAddParams)
     */
    protected function get_full_url($seo_url, $lang = null)
    {
        if ($seo_url) {
            $full_url = Registry::get_config()->get_shop_url($lang) . $seo_url;
            return Registry::get_utils_url()->process_seo_url($full_url);
        }
        return false;
    }
    /**
     * _getSeoIdent returns seo ident for db search
     *
     * @param string $sSeoUrl seo url
     *
     * @access protected
     *
     * @return string
     */
    protected function get_seo_ident($s_seo_url)
    {
        return md5(strtolower($s_seo_url));
    }
    /**
     * Returns SEO static uri
     *
     * @param string $sStdUrl standard page url
     * @param int    $iShopId active shop id
     * @param int    $iLang   active language
     *
     * @return string
     */
    protected function get_static_uri($s_std_url, $i_shop_id, $i_lang)
    {
        $s_std_url = $this->trim_url($s_std_url, $i_lang);
        return $this->load_from_db('static', $this->get_static_object_id($i_shop_id, $s_std_url), $i_lang, $i_shop_id);
    }
    /**
     * Returns target "extension"
     */
    protected function get_url_extension()
    {
    }
    /**
     * _getUniqueSeoUrl returns possibly modified url
     * for not to be same as already existing in db
     *
     * @param string $sSeoUrl     seo url
     * @param string $sObjectId   current object id, used to skip self in query
     * @param int    $iObjectLang object language id
     *
     * @access protected
     *
     * @return string
     */
    protected function get_unique_seo_url($s_seo_url, $s_object_id = null, $i_object_lang = null)
    {
        $s_seo_url = $this->prepare_uri($s_seo_url, $i_object_lang);
        $o_str = Str::get_str();
        $s_ext = '';
        if ($o_str->preg_match('/(\.html?|\/)$/i', $s_seo_url, $a_matched)) {
            $s_ext = $a_matched[0];
        }
        $s_base_seo_url = $s_seo_url;
        if ($s_ext && $o_str->substr($s_seo_url, 0 - $o_str->strlen($s_ext)) == $s_ext) {
            $s_base_seo_url = $o_str->substr($s_seo_url, 0, $o_str->strlen($s_seo_url) - $o_str->strlen($s_ext));
        }
        $i_shop_id = Registry::get_config()->get_shop_id();
        $i_cnt = 0;
        $s_check_seo_url = $this->trim_url($s_seo_url);
        $s_q = "select 1 from oxseo where oxshopid = '{$i_shop_id}'";
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // skipping self
        if ($s_object_id && isset($i_object_lang)) {
            $i_object_lang = (int) $i_object_lang;
            $s_q .= ' and not (oxobjectid = ' . $o_db->quote($s_object_id) . " and oxlang = {$i_object_lang})";
        }
        while ($o_db->get_one($s_q . ' and oxident= ' . $o_db->quote($this->get_seo_ident($s_check_seo_url)))) {
            $s_add = '';
            if (self::$_s_prefix) {
                $s_add = self::$_s_separator . self::$_s_prefix;
            }
            if ($i_cnt) {
                $s_add .= self::$_s_separator . $i_cnt;
            }
            ++$i_cnt;
            $s_seo_url = $s_base_seo_url . $s_add . $s_ext;
            $s_check_seo_url = $this->trim_url($s_seo_url);
        }
        return $s_seo_url;
    }
    /**
     * check if seo url exist and is fixed
     *
     * @param string $sType               object type
     * @param string $sId                 object identifier
     * @param int    $iLang               active language id
     * @param mixed  $iShopId             active shop id
     * @param string $sParams             additional seo params. optional (mostly used for db indexing)
     * @param bool   $blStrictParamsCheck strict parameters check
     *
     * @access protected
     *
     * @return bool
     */
    protected function is_fixed($s_type, $s_id, $i_lang, $i_shop_id = null, $s_params = null, $bl_strict_params_check = true)
    {
        if ($i_shop_id === null) {
            $i_shop_id = Registry::get_config()->get_shop_id();
        }
        $i_lang = (int) $i_lang;
        if (!isset(self::$_a_fixed_cache[$s_type][$i_shop_id][$s_id][$i_lang])) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'SELECT `oxfixed`
                FROM `oxseo`
                WHERE `oxtype` = ' . $o_db->quote($s_type) . '
                   AND `oxobjectid` = ' . $o_db->quote($s_id) . '
                   AND `oxshopid` = ' . $o_db->quote($i_shop_id) . "\n                   AND `oxlang` = '{$i_lang}'";
            $s_params = $s_params ? $o_db->quote($s_params) : "''";
            if ($s_params && $bl_strict_params_check) {
                $s_q .= " AND `oxparams` = {$s_params}";
            } else {
                $s_q .= ' ORDER BY `oxparams` ASC';
            }
            $s_q .= ' LIMIT 1';
            self::$_a_fixed_cache[$s_type][$i_shop_id][$s_id][$i_lang] = (bool) $o_db->get_one($s_q);
        }
        return self::$_a_fixed_cache[$s_type][$i_shop_id][$s_id][$i_lang];
    }
    /**
     * Returns cache key (in non admin mode)
     *
     * @param string $sType   object type
     * @param int    $iLang   active language id
     * @param mixed  $iShopId active shop id
     * @param string $sParams additional seo params. optional (mostly used for db indexing)
     *
     * @return string
     */
    protected function get_cache_key($s_type, $i_lang = null, $i_shop_id = null, $s_params = null)
    {
        $bl_admin = $this->is_admin();
        if (!$bl_admin && $s_type !== 'oxarticle') {
            return $s_type . (int) $i_lang . (int) $i_shop_id . 'seo';
        }
        // use cache in non admin mode
        if (self::$_s_cache_key === null) {
            self::$_s_cache_key = false;
            if (!$bl_admin && $o_view = Registry::get_config()->get_active_view()) {
                self::$_s_cache_key = md5((string) $o_view->get_view_id()) . 'seo';
            }
        }
        return self::$_s_cache_key;
    }
    /**
     * Loads seo data from cache for active view (in non admin mode)
     *
     * @param string $sCacheIdent cache identifier
     * @param string $sType       object type
     * @param int    $iLang       active language id
     * @param mixed  $iShopId     active shop id
     * @param string $sParams     additional seo params. optional (mostly used for db indexing)
     *
     * @return string
     */
    protected function load_from_cache($s_cache_ident, $s_type, $i_lang = null, $i_shop_id = null, $s_params = null)
    {
        if (!Registry::get_config()->get_config_param('blEnableSeoCache')) {
            return false;
        }
        start_profile('seoencoder_loadFromCache');
        $s_cache_key = $this->get_cache_key($s_type, $i_lang, $i_shop_id, $s_params);
        $s_cache = false;
        if ($s_cache_key && !isset(self::$_a_cache[$s_cache_key])) {
            self::$_a_cache[$s_cache_key] = Registry::get_utils()->from_file_cache($s_cache_key);
        }
        if (isset(self::$_a_cache[$s_cache_key]) && isset(self::$_a_cache[$s_cache_key][$s_cache_ident])) {
            $s_cache = self::$_a_cache[$s_cache_key][$s_cache_ident];
        }
        stop_profile('seoencoder_loadFromCache');
        return $s_cache;
    }
    /**
     * Saves seo cache data for active view (in non admin mode)
     *
     * @param string $sCacheIdent cache identifier
     * @param string $sCache      cacheable data
     * @param string $sType       object type
     * @param int    $iLang       active language id
     * @param mixed  $iShopId     active shop id
     * @param string $sParams     additional seo params. optional (mostly used for db indexing)
     *
     * @return bool
     */
    protected function save_in_cache($s_cache_ident, $s_cache, $s_type, $i_lang = null, $i_shop_id = null, $s_params = null)
    {
        if (!Registry::get_config()->get_config_param('blEnableSeoCache')) {
            return false;
        }
        start_profile('seoencoder_saveInCache');
        $bl_saved = false;
        if ($s_cache && ($s_cache_key = $this->get_cache_key($s_type, $i_lang, $i_shop_id, $s_params)) !== false) {
            self::$_a_cache[$s_cache_key][$s_cache_ident] = $s_cache;
            $bl_saved = Registry::get_utils()->to_file_cache($s_cache_key, self::$_a_cache[$s_cache_key]);
        }
        stop_profile('seoencoder_saveInCache');
        return $bl_saved;
    }
    /**
     * _loadFromDb loads data from oxseo table if exists
     * returns oxseo url
     *
     * @param string $sType               object type
     * @param string $sId                 object identifier
     * @param int    $iLang               active language id
     * @param mixed  $iShopId             active shop id
     * @param string $sParams             additional seo params. optional (mostly used for db indexing)
     * @param bool   $blStrictParamsCheck strict parameters check
     *
     * @access         protected
     *
     * @return string || false
     */
    protected function load_from_db($s_type, $s_id, $i_lang, $i_shop_id = null, $s_params = null, $bl_strict_params_check = true)
    {
        if ($i_shop_id === null) {
            $i_shop_id = Registry::get_config()->get_shop_id();
        }
        $i_lang = (int) $i_lang;
        $params = ['oxtype' => $s_type, 'oxobjectid' => $s_id, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang];
        $s_q = '
            SELECT
                `oxfixed`,
                `oxseourl`,
                `oxexpired`,
                `oxtype`
            FROM `oxseo`
            WHERE `oxtype` = :oxtype
               AND `oxobjectid` = :oxobjectid
               AND `oxshopid` = :oxshopid
               AND `oxlang` = :oxlang';
        $s_params = $s_params ?: '';
        if ($s_params && $bl_strict_params_check) {
            $s_q .= ' AND `oxparams` = :oxparams';
            $params['oxparams'] = $s_params;
        } else {
            $s_q .= ' ORDER BY `oxparams` ASC';
        }
        $s_q .= ' LIMIT 1';
        // caching to avoid same queries..
        $s_ident = md5('_loadFromDb' . serialize($params));
        // looking in cache
        if (($s_seo_url = $this->load_from_cache($s_ident, $s_type, $i_lang, $i_shop_id, $s_params)) === false) {
            $o_db = Database_Provider::get_db();
            $o_rs = $o_db->select($s_q, $params);
            if ($o_rs && $o_rs->count() > 0 && !$o_rs->EOF) {
                // moving expired static urls to history ..
                if ($o_rs->fields['oxexpired'] && ($o_rs->fields['oxtype'] == 'static' || $o_rs->fields['oxtype'] == 'dynamic')) {
                    // if expired - copying to history, marking as not expired
                    $this->copy_to_history($s_id, $i_shop_id, $i_lang);
                    $o_db->execute('update oxseo set oxexpired = 0 where oxobjectid = :oxobjectid and oxlang = :oxlang and oxshopid = :oxshopid', ['oxobjectid' => $s_id, 'oxlang' => $i_lang, 'oxshopid' => $i_shop_id]);
                    $s_seo_url = $o_rs->fields['oxseourl'];
                } elseif (!$o_rs->fields['oxexpired'] || $o_rs->fields['oxfixed']) {
                    // if seo url is available and is valid
                    $s_seo_url = $o_rs->fields['oxseourl'];
                }
                // storing in cache
                $this->save_in_cache($s_ident, $s_seo_url, $s_type, $i_lang, $i_shop_id, $s_params);
            }
        }
        return $s_seo_url;
    }
    /**
     * cached getter: check root directory php file names for them not to be in 1st part of seo url
     * because then apache will execute that php file instead of url parser
     *
     * @return array
     */
    protected function get_reserved_entry_keys()
    {
        if (!isset(self::$_a_reserved_entry_keys) || !is_array(self::$_a_reserved_entry_keys)) {
            $s_dir = get_shop_base_path();
            self::$_a_reserved_entry_keys = array_map(preg_quote(...), self::$_a_reserved_words, ['#']);
            $o_str = Str::get_str();
            foreach (glob("{$s_dir}/*") as $s_file) {
                if ($o_str->preg_match('/^(.+)\.php[0-9]*$/i', basename($s_file), $a_matches)) {
                    self::$_a_reserved_entry_keys[] = preg_quote((string) $a_matches[0], '#');
                    self::$_a_reserved_entry_keys[] = preg_quote((string) $a_matches[1], '#');
                } elseif (is_dir($s_file)) {
                    self::$_a_reserved_entry_keys[] = preg_quote(basename($s_file), '#');
                }
            }
            self::$_a_reserved_entry_keys = array_unique(self::$_a_reserved_entry_keys);
        }
        return self::$_a_reserved_entry_keys;
    }
    /**
     * Makes safe seo uri - removes unsupported/reserved characters
     *
     * @param string $sUri  seo uri
     * @param int|bool $iLang language ID, for which URI should be prepared
     *
     * @return string
     */
    protected function prepare_uri($s_uri, $i_lang = false)
    {
        // decoding entities
        $s_uri = $this->encode_string($s_uri, true, $i_lang);
        // basic string preparation
        $o_str = Str::get_str();
        $s_uri = $o_str->strip_tags($s_uri);
        // if found ".html" or "/" at the end - removing it temporary
        $s_ext = $this->get_url_extension();
        if ($s_ext === null) {
            $a_matched = [];
            if ($o_str->preg_match('/(\.html?|\/)$/i', $s_uri, $a_matched)) {
                $s_ext = $a_matched[0];
            } else {
                $s_ext = '/';
            }
        }
        if ($s_ext && $o_str->substr($s_uri, 0 - $o_str->strlen($s_ext)) == $s_ext) {
            $s_uri = $o_str->substr($s_uri, 0, $o_str->strlen($s_uri) - $o_str->strlen($s_ext));
        }
        $s_uri = $this->replace_special_chars($s_uri);
        // SEO id is empty ?
        if (!$s_uri && self::$_s_prefix) {
            $s_uri = $this->prepare_uri(self::$_s_prefix, $i_lang);
        }
        $s_add = '_' . self::$_s_prefix;
        if ('/' != self::$_s_separator) {
            $s_add = self::$_s_separator . self::$_s_prefix;
            $s_uri = trim($s_uri, self::$_s_separator);
        }
        // binding the ending back
        $s_uri .= $s_ext;
        // lowercase uri if option is set
        if (Registry::get_config()->get_config_param('blSEOLowerCaseUrls')) {
            $str_utility = Str::get_str();
            $s_uri = $str_utility->strtolower($s_uri);
        }
        // fix for not having url, which executes through /other/ script then seo decoder
        $s_uri = $o_str->preg_replace('#^(/*)(' . implode('|', $this->get_reserved_entry_keys()) . ')(/|$)#i', "\$1\$2{$s_add}\$3", $s_uri);
        // cleaning
        $s_quoted_separator = preg_quote(self::$_s_separator, '/');
        return $o_str->preg_replace(['|//+|', '/' . $s_quoted_separator . $s_quoted_separator . '+/'], ['/', self::$_s_separator], $s_uri);
    }
    /**
     * Prepares and returns formatted object SEO id
     *
     * @param string   $sTitle         Original object title
     * @param bool     $blSkipTruncate Truncate title into defined lenght or not
     * @param int|bool $iLang          language ID, for which to prepare the title
     *
     * @return string
     */
    protected function prepare_title($s_title, $bl_skip_truncate = false, $i_lang = false)
    {
        $s_title = $this->encode_string($s_title, true, $i_lang);
        $s_sep = self::$_s_separator;
        if (!$s_sep || '/' == $s_sep) {
            $s_sep = '_';
        }
        $s_reg_exp = '/[^A-Za-z0-9\/' . preg_quote(self::$_s_prefix, '/') . preg_quote($s_sep, '/') . ']+/';
        $s_title = preg_replace(['#/+#', $s_reg_exp, '# +#', '#(' . preg_quote($s_sep, '/') . ')+#'], $s_sep, $s_title);
        $o_str = Str::get_str();
        // smart truncate
        if (!$bl_skip_truncate && $o_str->strlen($s_title) > $this->_i_id_length) {
            $i_first_space = $o_str->strpos($s_title, $s_sep, $this->_i_id_length);
            if ($i_first_space !== false) {
                $s_title = $o_str->substr($s_title, 0, $i_first_space);
            }
        }
        $s_title = trim((string) $s_title, $s_sep);
        if (!$s_title) {
            return self::$_s_prefix;
        }
        // cleaning
        return $s_title;
    }
    /**
     * _saveToDb saves values to seo table
     *
     * @param string $sType     url type (static, dynamic, oxarticle etc)
     * @param string $sObjectId object identifier
     * @param string $sStdUrl   standard url
     * @param string $sSeoUrl   seo url
     * @param int    $iLang     active object language
     * @param mixed  $iShopId   active object shop id
     * @param bool   $blFixed   seo entry marker. if true, entry should not be automatically changed
     * @param string $sParams   additional seo params. optional (mostly used for db indexing)
     *
     * @access protected
     *
     * @return mixed
     */
    protected function save_to_db($s_type, $s_object_id, $s_std_url, $s_seo_url, $i_lang, $i_shop_id = null, $bl_fixed = null, $s_params = null)
    {
        if ($i_shop_id === null) {
            $i_shop_id = Registry::get_config()->get_shop_id();
        }
        $i_lang = (int) $i_lang;
        $s_std_url = $this->trim_url($s_std_url);
        $s_seo_url = $this->trim_url($s_seo_url);
        $s_ident = $this->get_seo_ident($s_seo_url);
        // transferring old url, thus current url will be regenerated
        $params = ['oxstdurl' => $s_std_url, 'oxseourl' => $s_seo_url, 'oxtype' => $s_type, 'oxobjectid' => $s_object_id, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang];
        $s_q = 'select oxfixed, oxexpired, ( oxstdurl like :oxstdurl ) as samestdurl, oxseourl like :oxseourl as sameseourl
                from oxseo
                where oxtype = :oxtype and
                oxobjectid = :oxobjectid and
                oxshopid = :oxshopid and
                oxlang = :oxlang';
        if ($s_params) {
            $s_q .= ' and oxparams = :oxparams ';
            $params['oxparams'] = $s_params;
        }
        $s_q .= ' limit 1';
        $o_db = Database_Provider::get_db();
        $o_rs = $o_db->select($s_q, $params);
        if ($o_rs && $o_rs->count() > 0 && !$o_rs->EOF) {
            if ($o_rs->fields['samestdurl'] && $o_rs->fields['sameseourl'] && $o_rs->fields['oxexpired']) {
                // fixed state change
                $s_fixed = isset($bl_fixed) ? ', oxfixed = ' . (int) $bl_fixed . ' ' : '';
                // nothing was changed - setting expired status back to 0
                $s_sql = "update oxseo set oxexpired = 0 {$s_fixed} where oxtype = :oxtype and\n                          oxobjectid = :oxobjectid and oxshopid = :oxshopid and oxlang = :oxlang ";
                $s_sql .= $s_params ? ' and oxparams = :oxparams ' : '';
                $s_sql .= ' limit 1';
                return $this->execute_query($s_sql, ['oxtype' => $s_type, 'oxobjectid' => $s_object_id, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang, 'oxparams' => $s_params]);
            }
            if ($o_rs->fields['oxexpired']) {
                // copy to history
                $this->copy_to_history($s_object_id, $i_shop_id, $i_lang, $s_type);
            }
        }
        // inserting new or updating
        $s_q = "insert into oxseo\n                    (oxobjectid, oxident, oxshopid, oxlang, oxstdurl, oxseourl, oxtype, oxfixed, oxexpired, oxparams)\n                values\n                    (:oxobjectid, :oxident, :oxshopid, :oxlang, :oxstdurl, :oxseourl, :oxtype, :oxfixed, '0', :oxparams)\n                on duplicate key update\n                    oxobjectid = :oxobjectid, oxident = :oxident, oxstdurl = :oxstdurl, oxseourl = :oxseourl, oxfixed = :oxfixed, oxexpired = '0'";
        return $this->execute_query($s_q, ['oxobjectid' => $s_object_id ?? '', 'oxident' => $s_ident, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang, 'oxstdurl' => $s_std_url, 'oxseourl' => $s_seo_url, 'oxtype' => $s_type, 'oxfixed' => (int) $bl_fixed, 'oxparams' => $s_params ?: '']);
    }
    /**
     * Runs query.
     * Returns false when the query fail, otherwise return true
     *
     * @param string $query Query to execute.
     * @param array  $params
     *
     * @return bool
     */
    protected function execute_query($query, $params = [])
    {
        $data_base = Database_Provider::get_db();
        $success = true;
        try {
            $data_base->execute($query, $params);
        } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $exception) {
            Registry::get_logger()->error($exception->get_message(), [$exception]);
            $success = false;
        }
        return $success;
    }
    /**
     * Removes shop path part and session id from given url
     *
     * @param string $sUrl  url to clean bad chars
     * @param int|null $iLang active language
     *
     * @access protected
     *
     * @return string
     */
    protected function trim_url($s_url, $i_lang = null)
    {
        $my_config = Registry::get_config();
        $o_str = Str::get_str();
        $s_url = str_replace($my_config->get_shop_url($i_lang, false), '', $s_url);
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)(force_)?(admin_)?sid=[a-z0-9\.]+&?(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)shp=[0-9]+&?(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)lang=[0-9]+&?(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)cur=[0-9]+&?(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)stoken=[a-z0-9]+&?(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)&(amp;)?/i', '\1', $s_url);
        $s_url = $o_str->preg_replace('/(\?|&(amp;)?)+$/i', '', $s_url);
        $s_url = trim((string) $s_url);
        // max length <= $this->_iMaxUrlLength
        $i_length = $this->get_max_url_length();
        if ($o_str->strlen($s_url) > $i_length) {
            return $o_str->substr($s_url, 0, $i_length);
        }
        return $s_url;
    }
    /**
     * Returns maximum seo/dynamic url length
     *
     * @return int
     */
    protected function get_max_url_length()
    {
        if ($this->_i_max_url_length === null) {
            // max length <= 2048 / custom
            $this->_i_max_url_length = Registry::get_config()->get_config_param('iMaxSeoUrlLength');
            if (!$this->_i_max_url_length) {
                $this->_i_max_url_length = 2048;
            }
        }
        return $this->_i_max_url_length;
    }
    /**
     * Replaces special chars in text
     *
     * @param string    $sString        string to encode
     * @param bool      $blReplaceChars is true, replaces user defined (\OxidEsales\Eshop\Core\Language::getSeoReplaceChars) characters into alternative
     * @param int|false $iLang          language, for which to encode the string
     *
     * @return string
     */
    public function encode_string($s_string, $bl_replace_chars = true, $i_lang = false)
    {
        // decoding entities
        $s_string = Str::get_str()->html_entity_decode($s_string);
        if ($bl_replace_chars) {
            if ($i_lang === false || !is_numeric($i_lang)) {
                $i_lang = Registry::get_lang()->get_edit_language();
            }
            if ($a_replace_chars = Registry::get_lang()->get_seo_replace_chars($i_lang)) {
                $s_string = str_replace(array_keys($a_replace_chars), array_values($a_replace_chars), $s_string);
            }
        }
        return str_replace(['&amp;', '&quot;', '&#039;', '&lt;', '&gt;'], '', $s_string);
    }
    /**
     * Sets SEO separator
     *
     * @param string $sSeparator SEO seperator
     */
    public function set_separator($s_separator = null): void
    {
        self::$_s_separator = $s_separator;
        if (!self::$_s_separator) {
            self::$_s_separator = '-';
        }
    }
    /**
     * Sets SEO prefix
     *
     * @param string $sPrefix SEO prefix
     */
    public function set_prefix($s_prefix): void
    {
        if ($s_prefix) {
            self::$_s_prefix = $s_prefix;
        } else {
            self::$_s_prefix = 'oxid';
        }
    }
    /**
     * sets seo id length
     *
     * @param string $iIdlength id length
     */
    public function set_id_length($i_idlength = null): void
    {
        if (isset($i_idlength)) {
            $this->_i_id_length = $i_idlength;
        }
    }
    /**
     * Sets array of words which must be checked before building seo url
     * These words are appended by seo prefix if they are the initial uri segment
     *
     * @param array $aReservedWords reserved words
     */
    public function set_reserved_words($a_reserved_words): void
    {
        self::$_a_reserved_words = array_merge(self::$_a_reserved_words, $a_reserved_words);
    }
    /**
     * Marks object seo records as expired
     *
     * @param string $sId      changed object id. If null is passed, object dependency is not checked
     * @param int    $iShopId  active shop id. Shop id must be passed uf you want to do shop level update (default null)
     * @param int    $iExpStat expiration status: 1 - standard expiration
     * @param int    $iLang    active language (optiona;)
     * @param string $sParams  additional params
     */
    public function mark_as_expired($s_id, $i_shop_id = null, $i_exp_stat = 1, $i_lang = null, $s_params = null): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_where = $s_id ? 'where oxobjectid =  ' . $o_db->quote($s_id) : '';
        $s_where .= isset($i_shop_id) ? $s_where ? ' and oxshopid = ' . $o_db->quote($i_shop_id) : 'where oxshopid = ' . $o_db->quote($i_shop_id) : '';
        $s_where .= !is_null($i_lang) ? $s_where ? " and oxlang = '{$i_lang}'" : "where oxlang = '{$i_lang}'" : '';
        $s_where .= $s_params ? $s_where ? " and {$s_params}" : "where {$s_params}" : '';
        $s_q = "update oxseo set oxexpired = :oxexpired {$s_where}";
        $o_db->execute($s_q, ['oxexpired' => $i_exp_stat]);
    }
    /**
     * Loads if exists or prepares and saves new seo url for passed object
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $oObject object to prepare seo data
     * @param string                                 $sType   type of object (oxvendor/oxcategory)
     * @param string                                 $sStdUrl stanradr url
     * @param string                                 $sSeoUrl seo uri
     * @param string                                 $sParams additional params, liek page number etc. mostly used by mysql for indexes
     * @param int                                    $iLang   language
     * @param bool                                   $blFixed fixed url marker (default is false)
     *
     * @return string
     */
    protected function get_page_uri($o_object, $s_type, $s_std_url, $s_seo_url, $s_params, $i_lang = null, $bl_fixed = false)
    {
        if (!isset($i_lang)) {
            $i_lang = $o_object->get_language();
        }
        $i_shop_id = Registry::get_config()->get_shop_id();
        //load page link from DB
        $s_old_seo_url = $this->load_from_db($s_type, $o_object->get_id(), $i_lang, $i_shop_id, $s_params);
        if (!$s_old_seo_url) {
            // generating new..
            $s_seo_url = $this->process_seo_url($s_seo_url, $o_object->get_id(), $i_lang);
            $this->save_to_db($s_type, $o_object->get_id(), $s_std_url, $s_seo_url, $i_lang, $i_shop_id, (int) $bl_fixed, $s_params);
        } else {
            // using old
            $s_seo_url = $s_old_seo_url;
        }
        return $s_seo_url;
    }
    /**
     * Generates static url object id
     *
     * @param int    $iShopId shop id
     * @param string $sStdUrl standard (dynamic) url
     *
     * @return string
     */
    protected function get_static_object_id($i_shop_id, $s_std_url)
    {
        return md5(strtolower($i_shop_id . $this->trim_url($s_std_url)));
    }
    /**
     * Static url encoder
     *
     * @param array $aStaticUrl static url info (contains standard URL and urls for each language)
     * @param int   $iShopId    active shop id
     * @param int   $iLang      active language
     *
     * @throws Exception
     */
    public function encode_static_urls($a_static_url, $i_shop_id, $i_lang)
    {
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_values = '';
        $s_old_object_id = null;
        // standard url
        $s_std_url = $this->trim_url(trim((string) $a_static_url['oxseo__oxstdurl']));
        $s_object_id = $a_static_url['oxseo__oxobjectid'];
        if (!$s_object_id || $s_object_id == '-1') {
            $s_object_id = $this->get_static_object_id($i_shop_id, $s_std_url);
        } else {
            // marking entry as needs to move to history
            $s_old_object_id = $s_object_id;
            // if std url does not match old
            if ($this->get_static_object_id($i_shop_id, $s_std_url) != $s_object_id) {
                $s_object_id = $this->get_static_object_id($i_shop_id, $s_std_url);
            }
        }
        foreach ($a_static_url['oxseo__oxseourl'] as $i_lang => $s_seo_url) {
            $i_lang = (int) $i_lang;
            // generating seo url
            $s_seo_url = $this->trim_url($s_seo_url);
            if ($s_seo_url) {
                $s_seo_url = $this->process_seo_url($s_seo_url, $s_object_id, $i_lang);
            }
            if ($s_old_object_id) {
                // Transaction picks master automatically (see ESDEV-3804 and ESDEV-3822).
                $db->start_transaction();
                try {
                    // move changed records to history
                    $result = $db->get_one('select (:oxseourl like oxseourl) & (:oxstdurl like oxstdurl) from oxseo where oxobjectid = :oxobjectid and oxshopid = :oxshopid and oxlang = :oxlang', ['oxseourl' => $s_seo_url, 'oxstdurl' => $s_std_url, 'oxobjectid' => $s_old_object_id, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang]);
                    if (!$result) {
                        $this->copy_to_history($s_old_object_id, $i_shop_id, $i_lang, 'static', $s_object_id);
                    }
                    $db->commit_transaction();
                } catch (Exception $exception) {
                    $db->rollback_transaction();
                    throw $exception;
                }
            }
            if (!$s_seo_url) {
                continue;
            }
            if (!$s_std_url) {
                continue;
            }
            $s_ident = $this->get_seo_ident($s_seo_url);
            if ($s_values) {
                $s_values .= ', ';
            }
            $s_values .= '( ' . $db->quote($s_object_id) . ', ' . $db->quote($s_ident) . ', ' . $db->quote($i_shop_id) . ", '{$i_lang}', " . $db->quote($s_std_url) . ', ' . $db->quote($s_seo_url) . ", 'static' )";
        }
        // must delete old before insert/update
        if ($s_old_object_id) {
            $this->execute_database_query('delete from oxseo where oxobjectid in ( ' . $db->quote($s_old_object_id) . ', ' . $db->quote($s_object_id) . ' )');
        }
        // (re)inserting
        if ($s_values) {
            $sql = "insert into oxseo ( oxobjectid, oxident, oxshopid, oxlang, oxstdurl, oxseourl, oxtype ) values {$s_values} ";
            $this->execute_database_query($sql);
        }
        return $s_object_id;
    }
    /**
     * Method copies static urls from base shop to newly created
     *
     * @param int $iShopId new created shop id
     */
    public function copy_static_urls($i_shop_id): void
    {
        $i_base_shop_id = Registry::get_config()->get_base_shop_id();
        if ($i_shop_id != $i_base_shop_id) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            foreach (array_keys(Registry::get_lang()->get_language_ids()) as $i_lang) {
                $s_q = "insert into oxseo ( oxobjectid, oxident, oxshopid, oxlang, oxstdurl, oxseourl, oxtype )\n                       select MD5( LOWER( CONCAT( :shopId, oxstdurl ) ) ), MD5( LOWER( oxseourl ) ),\n                       :shopId, oxlang, oxstdurl, oxseourl, oxtype from oxseo where oxshopid = :baseShopId and oxtype = 'static' and oxlang = :lang";
                $o_db->execute($s_q, ['shopId' => $i_shop_id, 'baseShopId' => $i_base_shop_id, 'lang' => $i_lang]);
            }
        }
    }
    /**
     * Returns static url for passed standard link (if available)
     *
     * @param string $sStdUrl standard Url
     * @param int    $iLang   active language (optional). default null
     * @param int    $iShopId active shop id (optional). default null
     *
     * @return string
     */
    public function get_static_url($s_std_url, $i_lang = null, $i_shop_id = null)
    {
        if (!isset($i_shop_id)) {
            $i_shop_id = Registry::get_config()->get_shop_id();
        }
        if (!isset($i_lang)) {
            $i_lang = Registry::get_lang()->get_edit_language();
        }
        if (isset($this->_a_static_url_cache[$s_std_url][$i_lang][$i_shop_id])) {
            return $this->_a_static_url_cache[$s_std_url][$i_lang][$i_shop_id];
        }
        $s_full_url = '';
        if ($s_seo_url = $this->get_static_uri($s_std_url, $i_shop_id, $i_lang)) {
            $s_full_url = $this->get_full_url($s_seo_url, $i_lang);
        }
        $this->_a_static_url_cache[$s_std_url][$i_lang][$i_shop_id] = $s_full_url;
        return $s_full_url;
    }
    /**
     * Adds new seo entry to db
     *
     * @param string $sObjectId    objects id
     * @param int    $iShopId      shop id
     * @param int    $iLang        objects language
     * @param string $sStdUrl      default url
     * @param string $sSeoUrl      seo url
     * @param string $sType        object type
     * @param bool   $blFixed      marker to keep seo config unchangeable
     * @param string $sKeywords    seo keywords
     * @param string $sDescription seo description
     * @param string $sParams      additional seo params. optional (mostly used for db indexing)
     * @param bool   $blExclude    exclude language prefix while building seo url
     * @param string $sAltObjectId alternative object id used while saving meta info (used to override object id when saving tags related info)
     */
    public function add_seo_entry($s_object_id, $i_shop_id, $i_lang, $s_std_url, $s_seo_url, $s_type, $bl_fixed = 1, $s_keywords = '', $s_description = '', $s_params = '', $bl_exclude = false, $s_alt_object_id = null): void
    {
        $s_seo_url = $this->process_seo_url($this->trim_url($s_seo_url ?: $this->get_alt_uri($s_alt_object_id ?: $s_object_id, $i_lang)), $s_object_id, $i_lang, $bl_exclude);
        if ($this->save_to_db($s_type, $s_object_id, $s_std_url, $s_seo_url, $i_lang, $i_shop_id, $bl_fixed, $s_params)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $o_str = Str::get_str();
            if ($s_keywords !== false) {
                $s_keywords = $o_str->htmlspecialchars($this->encode_string($o_str->strip_tags($s_keywords), false, $i_lang));
            }
            if ($s_description !== false) {
                $s_description = $o_str->htmlspecialchars($o_str->strip_tags($s_description));
            }
            $s_q = 'insert into oxobject2seodata
                       (oxobjectid, oxshopid, oxlang, oxkeywords, oxdescription)
                   values
                       (:oxobjectid, :oxshopid, :oxlang, :insertKeywords, :insertDescription)
                   on duplicate key update
                       oxkeywords = :updateKeywords, oxdescription = :updateDescription';
            $object_id = $s_alt_object_id ?: $s_object_id;
            $insert_keywords = $s_keywords ?: '';
            $insert_description = $s_description ?: '';
            $update_keywords = $s_keywords || $s_keywords == '' ? $s_keywords : 'oxkeywords';
            $update_description = $s_description || $s_description == '' ? $s_description : 'oxdescription';
            $o_db->execute($s_q, ['oxobjectid' => $object_id, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang, 'insertKeywords' => $insert_keywords, 'insertDescription' => $insert_description, 'updateKeywords' => $update_keywords, 'updateDescription' => $update_description]);
        }
    }
    /**
     * Returns alternative uri used while updating seo
     *
     * @param string $sObjectId object id
     * @param int    $iLang     language id
     */
    protected function get_alt_uri($s_object_id, $i_lang)
    {
    }
    /**
     * Remove a SEO entry from the database.
     *
     * @param string $objectId The id of the object to delete.
     * @param int    $shopId   The shop id of the object to delete.
     * @param int    $language The language of the object to delete.
     * @param string $type     The type of the object to delete.
     */
    public function delete_seo_entry($object_id, $shop_id, $language, $type): void
    {
        $query = 'delete from oxseo where oxobjectid = :oxobjectid and oxshopid = :oxshopid and oxlang = :oxlang and oxtype = :oxtype';
        $this->execute_database_query($query, ['oxobjectid' => $object_id, 'oxshopid' => $shop_id, 'oxlang' => $language, 'oxtype' => $type]);
    }
    /**
     * Execute a query on the database.
     *
     * @param string $query  The command to execute on the database.
     * @param array  $params Parameters used in prepare statement.
     */
    protected function execute_database_query($query, $params = [])
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $database->execute($query, $params);
    }
    /**
     * Returns meta information for preferred object
     *
     * @param string $sObjectId information object id
     * @param string $sMetaType metadata type - "oxkeywords", "oxdescription"
     * @param int    $iShopId   active shop id [optional]
     * @param int    $iLang     active language [optional]
     *
     * @return string
     */
    public function get_meta_data($s_object_id, $s_meta_type, $i_shop_id = null, $i_lang = null)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $i_shop_id = !isset($i_shop_id) ? Registry::get_config()->get_shop_id() : $i_shop_id;
        $i_lang = !isset($i_lang) ? Registry::get_lang()->get_object_tpl_language() : (int) $i_lang;
        return $o_db->get_one("SELECT {$s_meta_type} FROM oxobject2seodata WHERE oxobjectid = :oxobjectid AND oxshopid = :oxshopid AND oxlang = :oxlang", ['oxobjectid' => $s_object_id, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang]);
    }
    /**
     * getDynamicUrl acts similar to static urls,
     * except, that dynamic url are not shown in admin
     * and they can be re-encoded by providing new seo url
     *
     * @param string $sStdUrl standard url
     * @param string $sSeoUrl part of URL query which will be attached to standard shop url
     * @param int    $iLang   active language
     *
     * @access public
     *
     * @return string
     */
    public function get_dynamic_url($s_std_url, $s_seo_url, $i_lang)
    {
        start_profile('getDynamicUrl');
        $s_dyn_url = $this->get_full_url($this->get_dynamic_uri($s_std_url, $s_seo_url, $i_lang), $i_lang);
        stop_profile('getDynamicUrl');
        return $s_dyn_url;
    }
    /**
     * Searches for seo url in seo table. If not found - FALSE is returned
     *
     * @param string $standardUrl
     * @param int    $languageId
     *
     * @return string|false
     */
    public function fetch_seo_url($standard_url, $language_id = null)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $language_id = isset($language_id) ? (int) $language_id : Registry::get_lang()->get_base_language();
        $shop_id = Registry::get_config()->get_shop_id();
        $utils_url = Registry::get_utils_url();
        $url_parameters = $utils_url->string_to_params_array($standard_url);
        $no_page_nr_standard_url = $utils_url->clean_url_params($utils_url->clean_url($standard_url, ['pgNr']));
        $postfix = isset($url_parameters['pgNr']) ? 'pgNr=' . $url_parameters['pgNr'] : '';
        $query = 'SELECT `oxseourl` FROM `oxseo` WHERE `oxstdurl` = :oxstdurl AND `oxlang` = :oxlang AND `oxshopid` = :oxshopid LIMIT 1';
        $result = $database->get_one($query, ['oxstdurl' => $no_page_nr_standard_url, 'oxlang' => $language_id, 'oxshopid' => $shop_id]);
        return false !== $result && !empty($postfix) ? $utils_url->append_param_separator($result) . $postfix : $result;
    }
    /**
     * Searches for special characters in a string and replaces them with the configured strings.
     *
     * @param string $stringWithSpecialChars
     *
     * @return string
     */
    protected function replace_special_chars($string_with_special_chars)
    {
        if (!is_string($string_with_special_chars)) {
            return '';
        }
        $o_str = Str::get_str();
        $s_quoted_prefix = preg_quote(self::$_s_separator . self::$_s_prefix, '/');
        $s_reg_exp = '/[^A-Za-z0-9' . $s_quoted_prefix . '\/]+/';
        return $o_str->preg_replace(["/\\W*\\/\\W*/", $s_reg_exp], ['/', self::$_s_separator], $string_with_special_chars);
    }
    /**
     * Assemble full paginated url.
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $object     Object, atm category, vendor, manufacturer, recommendationList.
     * @param string                               $type       Seo identifier, see oxseo.oxtype.
     * @param string                               $stdUrl     Standard url
     * @param string                               $seoUrl     Seo url
     * @param integer                              $pageNumber Number of the page which should be prepared.
     * @param string                               $parameters Additional parameters, mostly used by mysql for indices.
     * @param int                                  $languageId Language id.
     * @param bool                                 $isFixed    Fixed url marker (default is null).
     *
     * @return string
     */
    protected function assemble_full_page_url($object, $type, $std_url, $seo_url, $page_number, $parameters, $language_id, $is_fixed)
    {
        $postfix = (int) $page_number > 0 ? 'pgNr=' . (int) $page_number : '';
        $url_part = $this->get_page_uri($object, $type, $std_url, $seo_url, $parameters, $language_id, $is_fixed);
        $full_url = $this->get_full_url($url_part, $language_id);
        return !empty($postfix) ? Registry::get_utils_url()->append_param_separator($full_url) . $postfix : $full_url;
    }
}