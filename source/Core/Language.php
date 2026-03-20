<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Exception\Language_Not_Found_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Bridge\Admin_Area_Module_Translation_File_Locator_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Bridge\Frontend_Module_Translation_File_Locator_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Bridge\Admin_Theme_Bridge_Interface;
use stdClass;
use Symfony\Contracts\Cache\Item_Interface;
use Symfony\Contracts\Cache\Tag_Aware_Cache_Interface;
/**
 * Language related utility class
 */
class Language extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Language parameter name
     *
     * @var string
     */
    protected $_s_name = 'lang';
    /**
     * Current shop base language Id
     *
     * @var int
     */
    protected $_i_base_language_id;
    /**
     * Templates language Id
     *
     * @var int
     */
    protected $_i_tpl_language_id;
    /**
     * Editing object language Id
     *
     * @var int
     */
    protected $_i_edit_language_id;
    /**
     * Language translations array cache
     *
     * @var array
     */
    protected $_a_lang_cache = [];
    /**
     * Array containing possible admin template translations
     *
     * @var array
     */
    protected $_a_admin_tpl_language_array;
    /**
     * Language abbreviation array
     *
     * @var array
     */
    protected $_a_lang_abbr = [];
    /**
     * registered additional language filesets to load
     *
     * @var array
     */
    protected $_a_additional_lang_files = [];
    /**
     * registered additional language filesets to load
     *
     * @var array
     */
    protected $_a_lang_map = [];
    /**
     * State is string translated or not
     *
     * @var bool
     */
    protected $_bl_is_translated = true;
    /**
     * Template language id.
     *
     * @var int
     */
    protected $_i_object_tpl_language_id;
    /**
     * Set translation state
     *
     * @param bool $blIsTranslated State is string translated or not. Default true.
     */
    public function set_is_translated($bl_is_translated = true): void
    {
        $this->_bl_is_translated = $bl_is_translated;
    }
    /**
     * Set translation state
     *
     * @return bool
     */
    public function is_translated()
    {
        return $this->_bl_is_translated;
    }
    /**
     * resetBaseLanguage resets base language id cache
     *
     * @access public
     */
    public function reset_base_language(): void
    {
        $this->_i_base_language_id = null;
    }
    /**
     * Returns active shop language id
     *
     * @return string
     */
    public function get_base_language()
    {
        if ($this->_i_base_language_id === null) {
            $my_config = Registry::get_config();
            $bl_admin = $this->is_admin();
            // languages and search engines
            if ($bl_admin && ($i_se_lang = Registry::get_request()->get_request_escaped_parameter('changelang')) !== null) {
                $this->_i_base_language_id = $i_se_lang;
            }
            if (is_null($this->_i_base_language_id)) {
                $this->_i_base_language_id = Registry::get_request()->get_request_escaped_parameter('lang');
            }
            //or determining by domain
            $a_language_urls = $my_config->get_config_param('aLanguageURLs');
            if (!$bl_admin && is_array($a_language_urls)) {
                foreach ($a_language_urls as $i_id => $s_url) {
                    if ($s_url && $my_config->is_current_url($s_url)) {
                        $this->_i_base_language_id = $i_id;
                        break;
                    }
                }
            }
            if (is_null($this->_i_base_language_id)) {
                $this->_i_base_language_id = Registry::get_request()->get_request_escaped_parameter('language');
                if (!isset($this->_i_base_language_id)) {
                    $this->_i_base_language_id = Registry::get_session()->get_variable('language');
                }
            }
            // if language still not set and not search engine browsing,
            // getting language from browser
            if (is_null($this->_i_base_language_id) && !$bl_admin && !Registry::get_utils()->is_search_engine()) {
                // getting from cookie
                $this->_i_base_language_id = Registry::get_utils_server()->get_ox_cookie('language');
                // getting from browser
                if (is_null($this->_i_base_language_id)) {
                    $this->_i_base_language_id = $this->detect_language_by_browser();
                }
            }
            if (is_null($this->_i_base_language_id)) {
                $this->_i_base_language_id = $my_config->get_config_param('sDefaultLang');
            }
            $this->_i_base_language_id = (int) $this->_i_base_language_id;
            // validating language
            $this->_i_base_language_id = $this->validate_language($this->_i_base_language_id);
            Registry::get_utils_server()->set_ox_cookie('language', $this->_i_base_language_id);
        }
        return $this->_i_base_language_id;
    }
    /**
     * Returns language id used to load objects according to current template language
     *
     * @return int
     */
    public function get_object_tpl_language()
    {
        if ($this->_i_object_tpl_language_id === null) {
            $this->_i_object_tpl_language_id = $this->get_tpl_language();
            if ($this->is_admin()) {
                $a_languages = $this->get_admin_tpl_language_array();
                if (!isset($a_languages[$this->_i_object_tpl_language_id]) || $a_languages[$this->_i_object_tpl_language_id]->active == 0) {
                    $this->_i_object_tpl_language_id = key($a_languages);
                }
            }
        }
        return $this->_i_object_tpl_language_id;
    }
    /**
     * Returns active shop templates language id
     * If it is not an admin area, template language id is same
     * as base shop language id
     *
     * @return string
     */
    public function get_tpl_language()
    {
        if ($this->_i_tpl_language_id === null) {
            $i_sess_lang = Registry::get_session()->get_variable('tpllanguage');
            $this->_i_tpl_language_id = $this->is_admin() ? $this->set_tpl_language($i_sess_lang) : $this->get_base_language();
        }
        return $this->_i_tpl_language_id;
    }
    /**
     * Returns editing object working language id
     *
     * @return string
     */
    public function get_edit_language()
    {
        if ($this->_i_edit_language_id === null) {
            if (!$this->is_admin()) {
                $this->_i_edit_language_id = $this->get_base_language();
            } else {
                $i_lang = null;
                // choosing language ident
                // check if we really need to set the new language
                if ('saveinnlang' == Registry::get_config()->get_active_view()->get_fnc_name()) {
                    $i_lang = Registry::get_request()->get_request_escaped_parameter('new_lang');
                }
                $i_lang ??= Registry::get_request()->get_request_escaped_parameter('editlanguage');
                $i_lang ??= Registry::get_session()->get_variable('editlanguage');
                $i_lang ??= $this->get_base_language();
                // validating language
                $this->_i_edit_language_id = $this->validate_language($i_lang);
                // writing to session
                Registry::get_session()->set_variable('editlanguage', $this->_i_edit_language_id);
            }
        }
        return $this->_i_edit_language_id;
    }
    /**
     * Returns array of available languages.
     *
     * @param integer $iLanguage    Number if current language (default null)
     * @param bool    $blOnlyActive load only current language or all
     * @param bool    $blSort       enable sorting or not
     *
     * @return array
     */
    public function get_language_array($i_language = null, $bl_only_active = false, $bl_sort = false)
    {
        $my_config = Registry::get_config();
        if (is_null($i_language)) {
            $i_language = $this->_i_base_language_id;
        }
        $a_languages = [];
        $a_conf_languages = $my_config->get_config_param('aLanguages');
        $a_lang_params = $my_config->get_config_param('aLanguageParams');
        if (is_array($a_conf_languages)) {
            $i = 0;
            reset($a_conf_languages);
            foreach ($a_conf_languages as $key => $val) {
                if ($bl_only_active && is_array($a_lang_params)) {
                    //skipping non active languages
                    if (!$a_lang_params[$key]['active']) {
                        $i++;
                        continue;
                    }
                }
                if ($val) {
                    $o_lang = new stdClass();
                    $o_lang->id = $a_lang_params[$key]['baseId'] ?? $i;
                    $o_lang->oxid = $key;
                    $o_lang->abbr = $key;
                    $o_lang->name = $val;
                    if (is_array($a_lang_params)) {
                        $o_lang->active = $a_lang_params[$key]['active'];
                        $o_lang->sort = $a_lang_params[$key]['sort'];
                    }
                    $o_lang->selected = isset($i_language) && $o_lang->id == $i_language ? 1 : 0;
                    $a_languages[$o_lang->id] = $o_lang;
                }
                ++$i;
            }
        }
        if ($bl_sort && is_array($a_lang_params)) {
            uasort($a_languages, $this->sort_languages_callback(...));
        }
        return $a_languages;
    }
    /**
     * Returns languages array containing possible admin template translations
     *
     * @return array
     */
    public function get_admin_tpl_language_array()
    {
        if ($this->_a_admin_tpl_language_array === null) {
            $config = Registry::get_config();
            $lang_array = $this->get_language_array();
            $this->_a_admin_tpl_language_array = [];
            $admin_theme_name = Container_Facade::get(Admin_Theme_Bridge_Interface::class)->get_active_theme();
            $source_directory = $config->get_app_dir() . 'views' . DIRECTORY_SEPARATOR . $admin_theme_name . DIRECTORY_SEPARATOR;
            foreach ($lang_array as $lang_key => $language) {
                $file_path = $source_directory . $language->abbr . DIRECTORY_SEPARATOR . 'lang.php';
                if (file_exists($file_path) && is_readable($file_path)) {
                    $this->_a_admin_tpl_language_array[$lang_key] = $language;
                }
            }
        }
        // moving pointer to beginning
        reset($this->_a_admin_tpl_language_array);
        return $this->_a_admin_tpl_language_array;
    }
    /**
     * Returns selected language abbreviation
     *
     * @param int $iLanguage language id [optional]
     *
     * @return string
     */
    public function get_language_abbr($language = null)
    {
        if ($this->_a_lang_abbr === []) {
            $this->_a_lang_abbr = $this->get_language_ids();
        }
        $language = isset($language) ? (int) $language : $this->get_base_language();
        if (isset($this->_a_lang_abbr[$language])) {
            return $this->_a_lang_abbr[$language];
        }
        throw new Language_Not_Found_Exception('Could not find language abbreviation for language-id ' . $language . '! ' . (count($this->_a_lang_abbr) === 0 ? 'No languages available' : 'Available languages: ' . implode(', ', $this->_a_lang_abbr)));
    }
    /**
     * getLanguageNames returns array of language names e.g. array('Deutch', 'English')
     *
     * @access public
     * @return array
     */
    public function get_language_names()
    {
        $a_conf_languages = Registry::get_config()->get_config_param('aLanguages');
        $a_lang_ids = $this->get_language_ids();
        $a_languages = [];
        foreach ($a_lang_ids as $i_id => $s_value) {
            $a_languages[$i_id] = $a_conf_languages[$s_value];
        }
        return $a_languages;
    }
    /**
     * @param string $sStringToTranslate Initial string
     * @param int $iLang optional language number
     * @param bool|null $blAdminMode on special case you can force mode,
     *  to load language constant from admin/shops language file
     *
     * @return string|array
     * @deprecated use ShopAdapterInterface::translateString() instead
     *
     * Searches for translation string in file and on success returns translation,
     * otherwise returns initial string.
     */
    public function translate_string($s_string_to_translate, $i_lang = null, $bl_admin_mode = null)
    {
        if ($s_string_to_translate !== null) {
            $this->set_is_translated();
            $a_lang = $this->get_lang_translation_array($i_lang, $bl_admin_mode);
            if (isset($a_lang[$s_string_to_translate])) {
                return $a_lang[$s_string_to_translate];
            }
            $a_map = $this->get_language_map($i_lang, $bl_admin_mode);
            if (isset($a_map[$s_string_to_translate], $a_lang[$a_map[$s_string_to_translate]])) {
                return $a_lang[$a_map[$s_string_to_translate]];
            }
            if (count($this->_a_additional_lang_files)) {
                $a_lang = $this->get_lang_translation_array($i_lang, $bl_admin_mode, $this->_a_additional_lang_files);
                if (isset($a_lang[$s_string_to_translate])) {
                    return $a_lang[$s_string_to_translate];
                }
            }
        }
        $this->set_is_translated(false);
        if (!$this->is_translated()) {
            Registry::get_logger()->warning("translation for {$s_string_to_translate} not found", compact('iLang', 'blAdminMode'));
        }
        return $s_string_to_translate;
    }
    /**
     * Iterates through given array ($aData) and collects data if array key is similar as
     * searchable key ($sKey*). If you pass $aCollection, it will be appended with found items
     *
     * @param array  $aData       array to search in
     * @param string $sKey        key to look for (looking for similar with strpos)
     * @param array  $aCollection array to append found items [optional]
     *
     * @return array
     */
    protected function collect_similar($a_data, $s_key, $a_collection = [])
    {
        foreach ($a_data as $s_val_key => $s_value) {
            if (str_starts_with((string) $s_val_key, $s_key)) {
                $a_collection[$s_val_key] = $s_value;
            }
        }
        return $a_collection;
    }
    /**
     * Returns array( "MY_TRANSLATION_KEY" => "MY_TRANSLATION_VALUE", ... ) by
     * given filter "MY_TRANSLATION_" from language files
     *
     * @param string $sKey    key to look
     * @param int    $iLang   language files to search [optional]
     * @param bool   $blAdmin admin/non admin mode [optional]
     *
     * @return array
     */
    public function get_similar_by_key($s_key, $i_lang = null, $bl_admin = null)
    {
        start_profile('getSimilarByKey');
        $i_lang ??= $this->get_tpl_language();
        $bl_admin ??= $this->is_admin();
        // checking if exists in cache
        $a_lang = $this->get_lang_translation_array($i_lang, $bl_admin);
        $a_similar_const = $this->collect_similar($a_lang, $s_key);
        // checking if in map exist
        $a_map = $this->get_language_map($i_lang, $bl_admin);
        $a_similar_const = $this->collect_similar($a_map, $s_key, $a_similar_const);
        // checking if in theme options exist
        if (count($this->_a_additional_lang_files)) {
            $a_lang = $this->get_lang_translation_array($i_lang, $bl_admin, $this->_a_additional_lang_files);
            $a_similar_const = $this->collect_similar($a_lang, $s_key, $a_similar_const);
        }
        stop_profile('getSimilarByKey');
        return $a_similar_const;
    }
    /**
     * Returns formatted number, according to active currency formatting standards.
     *
     * @param float  $dValue  Plain price
     * @param object $oActCur Object of active currency
     *
     * @return string
     */
    public function format_currency($d_value, $o_act_cur = null)
    {
        if (!$o_act_cur) {
            $o_act_cur = Registry::get_config()->get_act_shop_currency_object();
        }
        $s_value = Registry::get_utils()->f_round($d_value, $o_act_cur);
        return number_format($s_value, $o_act_cur->decimal, $o_act_cur->dec, $o_act_cur->thousand);
    }
    /**
     * Returns formatted vat value, according to formatting standards.
     *
     * @param float  $dValue  Plain price
     * @param object $oActCur Object of active currency
     *
     * @return string
     */
    public function format_vat($d_value, $o_act_cur = null)
    {
        $i_dec_pos = 0;
        $s_value = (string) $d_value;
        $o_str = Str::get_str();
        if (($i_dot_pos = $o_str->strpos($s_value, '.')) !== false) {
            $i_dec_pos = $o_str->strlen($o_str->substr($s_value, $i_dot_pos + 1));
        }
        $o_act_cur = $o_act_cur ?: Registry::get_config()->get_act_shop_currency_object();
        $i_dec_pos = $i_dec_pos < $o_act_cur->decimal ? $i_dec_pos : $o_act_cur->decimal;
        return number_format((float) $d_value, $i_dec_pos, $o_act_cur->dec, $o_act_cur->thousand);
    }
    /**
     * According to user configuration forms and return language prefix.
     *
     * @param integer $iLanguage User selected language (default null)
     *
     * @return string
     */
    public function get_language_tag($i_language = null)
    {
        if (!isset($i_language)) {
            $i_language = $this->get_base_language();
        }
        $i_language = (int) $i_language;
        return $i_language ? "_{$i_language}" : '';
    }
    /**
     * Validate language id. If not valid id, returns default value
     *
     * @param int $iLang Language id
     *
     * @return int
     */
    public function validate_language($i_lang = null)
    {
        // checking if this language is valid
        $a_languages = $this->get_language_array(null, !$this->is_admin());
        if (!isset($a_languages[$i_lang]) && is_array($a_languages)) {
            $o_lang = current($a_languages);
            if (isset($o_lang->id)) {
                $i_lang = $o_lang->id;
            }
        }
        return (int) $i_lang;
    }
    /**
     * Set base shop language
     *
     * @param int $iLang Language id
     */
    public function set_base_language($i_lang = null): void
    {
        if (is_null($i_lang)) {
            $i_lang = $this->get_base_language();
        } else {
            $this->_i_base_language_id = (int) $i_lang;
        }
        Registry::get_session()->set_variable('language', $i_lang);
    }
    /**
     * Validates and sets templates language id
     *
     * @param int $iLang Language id
     */
    public function set_tpl_language($i_lang = null)
    {
        $this->_i_tpl_language_id = isset($i_lang) ? (int) $i_lang : $this->get_base_language();
        if ($this->is_admin()) {
            $a_languages = $this->get_admin_tpl_language_array();
            if (!isset($a_languages[$this->_i_tpl_language_id])) {
                $this->_i_tpl_language_id = key($a_languages);
            }
        }
        Registry::get_session()->set_variable('tpllanguage', $this->_i_tpl_language_id);
        return $this->_i_tpl_language_id;
    }
    /**
     * Returns the encoding all translations will be converted to.
     *
     * @return string
     */
    protected function get_translations_expected_encoding()
    {
        return 'UTF-8';
    }
    /**
     * Returns array with paths where frontend language files are stored
     *
     * @param int $iLang active language
     *
     * @return array
     */
    protected function get_lang_files_path_array($i_lang)
    {
        $o_config = Registry::get_config();
        $a_lang_files = [];
        $s_app_dir = $o_config->get_app_dir();
        $s_lang = Registry::get_lang()->get_language_abbr($i_lang);
        $s_theme = $o_config->get_config_param('sTheme');
        //get generic lang files
        $s_generic_path = $s_app_dir . 'translations/' . $s_lang;
        if ($s_generic_path) {
            $a_lang_files = array_merge($a_lang_files, $this->get_abbreviation_directory_language_files($s_generic_path));
        }
        //get theme lang files
        if ($s_theme) {
            $s_theme_path = $s_app_dir . 'views/' . $s_theme . '/';
            $a_lang_files = array_merge($a_lang_files, $this->get_theme_language_files($s_theme_path, $s_lang));
        }
        $a_lang_files = array_merge($a_lang_files, $this->get_custom_theme_language_files($i_lang));
        // modules language files
        $a_lang_files = $this->append_module_lang_files_for_frontend($a_lang_files, $s_lang);
        // custom language files
        $a_lang_files = $this->append_custom_lang_files($a_lang_files, $s_lang);
        return count($a_lang_files) ? $a_lang_files : false;
    }
    /**
     * @return array<string>
     */
    protected function get_theme_language_files(string $theme_path, string $language_abbreviation): array
    {
        $files = $this->get_abbreviation_directory_language_files($theme_path . 'translations/' . $language_abbreviation);
        return array_merge($files, $this->get_abbreviation_directory_language_files($theme_path . $language_abbreviation));
    }
    /**
     * @return array<string>
     */
    protected function get_abbreviation_directory_language_files(string $directory): array
    {
        $files = [$directory . '/lang.php'];
        return $this->append_lang_file($files, $directory);
    }
    /**
     * Returns custom theme language files.
     *
     * @param int $language active language
     *
     * @return array
     */
    protected function get_custom_theme_language_files($language)
    {
        $o_config = Registry::get_config();
        $s_custom_theme = $o_config->get_config_param('sCustomTheme');
        $s_app_dir = $o_config->get_app_dir();
        $s_lang = Registry::get_lang()->get_language_abbr($language);
        $a_lang_files = [];
        if ($s_custom_theme) {
            $custom_theme_path = $s_app_dir . 'views/' . $s_custom_theme . '/';
            $a_lang_files = array_merge($a_lang_files, $this->get_theme_language_files($custom_theme_path, $s_lang));
        }
        return $a_lang_files;
    }
    /**
     * Returns array with paths where admin language files are stored
     *
     * @param int $activeLanguage The active language
     *
     * @return array
     */
    protected function get_admin_lang_files_path_array($active_language)
    {
        $config = Registry::get_config();
        $lang_files = [];
        $app_directory = $config->get_app_dir();
        $language = Registry::get_lang()->get_language_abbr($active_language);
        // admin lang files
        $admin_theme_name = Container_Facade::get(Admin_Theme_Bridge_Interface::class)->get_active_theme();
        $admin_path = $app_directory . 'views' . DIRECTORY_SEPARATOR . $admin_theme_name . DIRECTORY_SEPARATOR . $language;
        $lang_files[] = $admin_path . DIRECTORY_SEPARATOR . 'lang.php';
        $lang_files[] = $app_directory . 'translations' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'translit_lang.php';
        $lang_files = $this->append_lang_file($lang_files, $admin_path);
        // themes options lang files
        $theme_path = $app_directory . 'views/*/' . $language;
        $lang_files = $this->append_lang_file($lang_files, $theme_path, 'options');
        $theme_path = $app_directory . 'views/*/translations/' . $language;
        $lang_files = $this->append_lang_file($lang_files, $theme_path, 'options');
        // module language files
        $lang_files = $this->append_module_lang_files_for_admin_area($lang_files, $language);
        // custom language files
        $lang_files = $this->append_custom_lang_files($lang_files, $language, true);
        return count($lang_files) ? $lang_files : false;
    }
    /**
     * Appends lang or options files if exists, except custom lang files
     *
     * @param array  $aLangFiles   existing language files
     * @param string $sFullPath    path to language files to append
     * @param string $sFilePattern file pattern to search for, default is "lang"
     *
     * @return array
     */
    protected function append_lang_file($a_lang_files, $s_full_path, $s_file_pattern = 'lang')
    {
        $a_files = glob($s_full_path . "/*_{$s_file_pattern}.php");
        if (is_array($a_files) && count($a_files)) {
            foreach ($a_files as $s_file) {
                if (!strpos($s_file, 'cust_lang.php')) {
                    $a_lang_files[] = $s_file;
                }
            }
        }
        return $a_lang_files;
    }
    /**
     * Appends Custom language files cust_lang.php
     *
     * @param array  $languageFiles existing language files
     * @param string $language      language abbreviation
     * @param bool   $forAdmin      add files for admin
     *
     * @return array
     */
    protected function append_custom_lang_files($language_files, $language, $for_admin = false)
    {
        if ($for_admin) {
            $admin_theme_name = Container_Facade::get(Admin_Theme_Bridge_Interface::class)->get_active_theme();
            $language_files[] = $this->get_custom_file_path($language, $admin_theme_name);
        } else {
            $config = Registry::get_config();
            if ($config->get_config_param('sTheme')) {
                $language_files[] = $this->get_custom_file_path($language, $config->get_config_param('sTheme'));
            }
            if ($config->get_config_param('sCustomTheme')) {
                $language_files[] = $this->get_custom_file_path($language, $config->get_config_param('sCustomTheme'));
            }
        }
        return $language_files;
    }
    /**
     * @param int    $language  The language index
     * @param string $themeName The name of the theme
     */
    private function get_custom_file_path($language, string $theme_name): string
    {
        $config = Registry::get_config();
        return $config->get_app_dir() . 'views' . DIRECTORY_SEPARATOR . $theme_name . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'cust_lang.php';
    }
    private function append_module_lang_files_for_admin_area(array $lang_files, string $lang): array
    {
        $module_lang_files = Container_Facade::get(Admin_Area_Module_Translation_File_Locator_Bridge_Interface::class)->locate($lang);
        return array_merge($lang_files, $module_lang_files);
    }
    private function append_module_lang_files_for_frontend(array $lang_files, string $lang): array
    {
        $module_lang_files = Container_Facade::get(Frontend_Module_Translation_File_Locator_Bridge_Interface::class)->locate($lang);
        return array_merge($lang_files, $module_lang_files);
    }
    /**
     * Returns language cache file name
     *
     * @param bool  $blAdmin    admin or not
     * @param int   $iLang      current language id
     * @param array $aLangFiles language files to load [optional]
     *
     * @return string
     */
    protected function get_lang_file_cache_name($bl_admin, $i_lang, $a_lang_files = null)
    {
        $my_config = Registry::get_config();
        $s_lang_files_ident = '_default';
        if (is_array($a_lang_files) && $a_lang_files) {
            $s_lang_files_ident = '_' . md5(implode('+', $a_lang_files));
        }
        return 'langcache_' . (int) $bl_admin . "_{$i_lang}_" . $my_config->get_shop_id() . '_' . $my_config->get_config_param('sTheme') . $s_lang_files_ident;
    }
    protected function get_language_file_data($bl_admin = false, $i_lang = 0, $a_lang_files = null)
    {
        $s_cache_name = $this->get_lang_file_cache_name($bl_admin, $i_lang, $a_lang_files);
        $cache = Container_Facade::get(Tag_Aware_Cache_Interface::class);
        return $cache->get($s_cache_name, function (Item_Interface $item) use ($a_lang_files, $bl_admin, $i_lang): array {
            $item->tag('oxid_esales.cache.language');
            if ($a_lang_files === null) {
                if ($bl_admin) {
                    $a_lang_files = $this->get_admin_lang_files_path_array($i_lang);
                } else {
                    $a_lang_files = $this->get_lang_files_path_array($i_lang);
                }
            }
            $a_lang_cache = [];
            $s_base_charset = $this->get_translations_expected_encoding();
            $a_lang = [];
            $a_lang_seo_replace_chars = [];
            foreach ($a_lang_files as $s_lang_file) {
                if (file_exists($s_lang_file) && is_readable($s_lang_file)) {
                    //$aSeoReplaceChars null indicates that there is no setting made
                    $a_seo_replace_chars = null;
                    include $s_lang_file;
                    $a_lang = array_merge(['charset' => 'UTF-8'], $a_lang);
                    if (isset($a_seo_replace_chars) && is_array($a_seo_replace_chars)) {
                        $a_lang_seo_replace_chars = array_merge($a_lang_seo_replace_chars, $a_seo_replace_chars);
                    }
                    $a_lang_cache = array_merge($a_lang_cache, $a_lang);
                }
            }
            $a_lang_cache['charset'] = $s_base_charset;
            // special character replacement list
            $a_lang_cache['_aSeoReplaceChars'] = $a_lang_seo_replace_chars;
            return $a_lang_cache;
        });
    }
    /**
     * Returns language map array
     *
     * @param int  $language language index
     * @param bool $isAdmin  admin mode [default NULL]
     *
     * @return array
     */
    protected function get_language_map($language, $is_admin = null)
    {
        $is_admin ??= $this->is_admin();
        $key = $language . (int) $is_admin;
        if (!isset($this->_a_lang_map[$key])) {
            $this->_a_lang_map[$key] = [];
            $config = Registry::get_config();
            $map_file = '';
            $theme = $this->get_real_theme_name($config->get_config_param('sTheme'), $is_admin);
            $custom_theme = $this->get_real_theme_name($config->get_config_param('sCustomTheme'), $is_admin);
            $language_abbr = Registry::get_lang()->get_language_abbr($language);
            $possible_map_file_locations = array_merge($this->get_theme_language_file_map_locations($custom_theme, $language_abbr), $this->get_theme_language_file_map_locations($theme, $language_abbr));
            foreach ($possible_map_file_locations as $tmp_map_file_location) {
                $possible_map_file = $tmp_map_file_location . DIRECTORY_SEPARATOR . 'map.php';
                if (file_exists($possible_map_file) && is_readable($possible_map_file)) {
                    $map_file = $possible_map_file;
                    break;
                }
            }
            if ($map_file) {
                $a_map = [];
                include $map_file;
                $this->_a_lang_map[$key] = $a_map;
            }
        }
        return $this->_a_lang_map[$key];
    }
    /**
     * @param string $theme The name of the theme
     * @param string $languageAbbreviation Language abbreviation
     *
     * @return string[]
     */
    private function get_theme_language_file_map_locations(string $theme, string $language_abbreviation): array
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $theme_directory = $config->get_app_dir() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR;
        return [
            $theme_directory . $language_abbreviation,
            // for backwards compatibility
            $theme_directory . 'translations' . DIRECTORY_SEPARATOR . $language_abbreviation,
            $theme_directory . 'translations',
        ];
    }
    /**
     * @param bool   $isAdmin   The admin mode [default NULL]
     * @param string $themeName The name of the theme
     *
     * @return string
     */
    private function get_real_theme_name($theme_name, $is_admin = null)
    {
        return $is_admin ? Container_Facade::get(Admin_Theme_Bridge_Interface::class)->get_active_theme() : $theme_name;
    }
    /**
     * Returns current language cache language id
     *
     * @param bool $blAdmin admin mode
     * @param int  $iLang   language id [optional]
     *
     * @return int
     */
    protected function get_cache_language_id($bl_admin, $i_lang = null)
    {
        $i_lang = $i_lang === null && $bl_admin ? $this->get_tpl_language() : $i_lang;
        if (!isset($i_lang)) {
            $i_lang = $this->get_base_language();
            if (!isset($i_lang)) {
                $i_lang = 0;
            }
        }
        return (int) $i_lang;
    }
    /**
     * get language array from lang translation file
     *
     * @param int   $iLang      optional language
     * @param bool  $blAdmin    admin mode switch
     * @param array $aLangFiles language files to load [optional]
     *
     * @return array
     */
    protected function get_lang_translation_array($i_lang = null, $bl_admin = null, $a_lang_files = null)
    {
        start_profile('_getLangTranslationArray');
        $bl_admin ??= $this->is_admin();
        $i_lang = $this->get_cache_language_id($bl_admin, $i_lang);
        $s_cache_name = $this->get_lang_file_cache_name($bl_admin, $i_lang, $a_lang_files);
        if (!isset($this->_a_lang_cache[$s_cache_name])) {
            $this->_a_lang_cache[$s_cache_name] = [];
        }
        if (!isset($this->_a_lang_cache[$s_cache_name][$i_lang])) {
            // loading main lang files data
            $this->_a_lang_cache[$s_cache_name][$i_lang] = $this->get_language_file_data($bl_admin, $i_lang, $a_lang_files);
        }
        stop_profile('_getLangTranslationArray');
        // if language array exists ..
        return $this->_a_lang_cache[$s_cache_name][$i_lang] ?? [];
    }
    /**
     * Language sorting callback function
     *
     * @param object $a1 first value to check
     * @param object $a2 second value to check
     *
     * @return int
     */
    protected function sort_languages_callback($a1, $a2)
    {
        return $a1->sort <=> $a2->sort;
    }
    /**
     * Returns language id param name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->_s_name;
    }
    /**
     * Returns form hidden language parameter
     *
     * @return string
     */
    public function get_form_lang()
    {
        if (!$this->is_admin()) {
            return '<input type="hidden" name="' . $this->get_name() . '" value="' . $this->get_base_language() . '" />';
        }
    }
    /**
     * Returns url language parameter
     *
     * @param int $iLang language id [optional]
     *
     * @return string
     */
    public function get_url_lang($i_lang = null)
    {
        if (!$this->is_admin()) {
            $i_lang ??= $this->get_base_language();
            return $this->get_name() . '=' . $i_lang;
        }
    }
    /**
     * Is needed appends url with language parameter
     * Direct usage of this method to retrieve end url result is discouraged - instead
     * see \OxidEsales\Eshop\Core\UtilsUrl::processUrl
     *
     * @param string $sUrl  url to process
     * @param int    $iLang language id [optional]
     *
     * @see \OxidEsales\Eshop\Core\UtilsUrl::processUrl
     *
     * @return string
     */
    public function process_url($s_url, $i_lang = null)
    {
        $i_lang ??= $this->get_base_language();
        $i_default_lang = (int) Registry::get_config()->get_config_param('sDefaultLang');
        $i_browser_language = (int) $this->detect_language_by_browser();
        $o_str = Str::get_str();
        if (!$this->is_admin()) {
            $s_param = $this->get_url_lang($i_lang);
            if (!$o_str->preg_match('/(\?|&(amp;)?)lang=[0-9]+/', $s_url) && ($i_lang != $i_default_lang || $i_default_lang != $i_browser_language)) {
                if ($s_url) {
                    if ($o_str->strpos($s_url, '?') === false) {
                        $s_url .= '?';
                    } elseif (!$o_str->preg_match('/(\?|&(amp;)?)$/', $s_url)) {
                        $s_url .= '&amp;';
                    }
                }
                $s_url .= $s_param . '&amp;';
            } else {
                $s_url = $o_str->preg_replace('/(\?|&(amp;)?)lang=[0-9]+/', '\1' . $s_param, $s_url);
            }
        }
        return $s_url;
    }
    /**
     * Detect language by user browser settings. Returns language ID if
     * detected, otherwise returns null.
     *
     * @return int
     */
    public function detect_language_by_browser()
    {
        $s_browser_language = $this->get_browser_language();
        if (!is_null($s_browser_language)) {
            $a_languages = $this->get_language_array(null, true);
            foreach ($a_languages as $o_lang) {
                if ($o_lang->abbr == $s_browser_language) {
                    return $o_lang->id;
                }
            }
        }
    }
    /** @return array|string[] */
    public function get_multi_lang_tables()
    {
        $tables = ['oxactions', 'oxartextends', 'oxarticles', 'oxattribute', 'oxcategories', 'oxcontents', 'oxcountry', 'oxdelivery', 'oxdeliveryset', 'oxdiscount', 'oxgroups', 'oxlinks', 'oxmanufacturers', 'oxmediaurls', 'oxobject2attribute', 'oxpayments', 'oxselectlist', 'oxshops', 'oxstates', 'oxvendor', 'oxwrapping'];
        $config_tables = Container_Facade::get_parameter('oxid_esales.multilingual_tables');
        if (\is_array($config_tables)) {
            return \array_merge($tables, $config_tables);
        }
        return $tables;
    }
    /**
     * Get SEO spec. chars replacement list for current language
     *
     * @param int $iLang language ID
     */
    public function get_seo_replace_chars($i_lang)
    {
        // get language replace chars
        $a_seo_replace_chars = $this->translate_string('_aSeoReplaceChars', $i_lang);
        if (!is_array($a_seo_replace_chars)) {
            return [];
        }
        return $a_seo_replace_chars;
    }
    /**
     * Gets browser language.
     *
     * @return string
     */
    protected function get_browser_language()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
            return strtolower(substr((string) $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
        }
    }
    /**
     * Returns available language IDs (abbreviations) for all sub shops
     *
     * @return array
     */
    public function get_all_shop_language_ids()
    {
        return $this->get_language_ids_from_database();
    }
    /**
     * Get current Shop language ids.
     *
     * @param int $iShopId shop id
     *
     * @return array
     */
    public function get_language_ids($i_shop_id = 0)
    {
        if (empty($i_shop_id) || $i_shop_id == Registry::get_config()->get_shop_id()) {
            return $this->get_active_shop_language_ids();
        }
        return $this->get_language_ids_from_database($i_shop_id);
    }
    /**
     * Returns available language IDs (abbreviations)
     *
     * @return array
     */
    public function get_active_shop_language_ids()
    {
        $o_config = Registry::get_config();
        //if exists language parameters array, extract lang id's from there
        $a_lang_params = $o_config->get_config_param('aLanguageParams');
        if (is_array($a_lang_params)) {
            return $this->get_language_ids_from_language_params_array($a_lang_params);
        }
        $languages = $o_config->get_config_param('aLanguages');
        return $this->get_language_ids_from_languages_array(is_array($languages) ? $languages : []);
    }
    /**
     * Gets language Ids for given shopId or for all subshops
     *
     *
     * @return array
     */
    protected function get_language_ids_from_database($shop_id = null)
    {
        return $this->get_language_ids();
    }
    /**
     * Returns list of all language codes taken from config values of given 'aLanguages' (for all subshops)
     *
     * @param string $sLanguageParameterName language config parameter name
     * @param int    $iShopId                shop id
     *
     * @return array
     */
    protected function get_config_language_values($s_language_parameter_name, $i_shop_id = null)
    {
        $a_config_decoded_values = [];
        $a_config_values = $this->select_language_param_values($s_language_parameter_name, $i_shop_id);
        foreach ($a_config_values as $s_config_value) {
            $a_config_languages = unserialize($s_config_value['oxvarvalue']);
            $a_languages = [];
            if ($s_language_parameter_name == 'aLanguageParams') {
                $a_languages = $this->get_language_ids_from_language_params_array($a_config_languages);
            } elseif ($s_language_parameter_name == 'aLanguages') {
                $a_languages = $this->get_language_ids_from_languages_array($a_config_languages);
            }
            $a_config_decoded_values = array_unique(array_merge($a_config_decoded_values, $a_languages));
        }
        return $a_config_decoded_values;
    }
    /**
     * Returns array of all config values of given paramName
     *
     * @param string      $sParamName Parameter name
     * @param string|null $sShopId    Shop id
     *
     * @return array
     */
    protected function select_language_param_values($s_param_name, $s_shop_id = null)
    {
        $o_db = Database_Provider::get_db();
        $params = ['oxvarname' => $s_param_name];
        $s_query = '
            select oxvarvalue
            from oxconfig
            where oxvarname = :oxvarname';
        if (!empty($s_shop_id)) {
            $s_query .= ' and oxshopid = :oxshopid limit 1';
            $params['oxshopid'] = $s_shop_id;
        }
        return $o_db->get_all($s_query, $params);
    }
    /**
     * gets language code array from aLanguageParams array
     *
     * @param array $aLanguageParams Language parameters
     *
     * @return array
     */
    protected function get_language_ids_from_language_params_array($a_language_params)
    {
        $a_languages = [];
        foreach ($a_language_params as $s_abbr => $a_value) {
            $i_base_id = (int) $a_value['baseId'];
            $a_languages[$i_base_id] = $s_abbr;
        }
        return $a_languages;
    }
    /**
     * gets language code array from aLanguages array
     *
     * @param array $aLanguages Languages
     *
     * @return array
     */
    protected function get_language_ids_from_languages_array($a_languages)
    {
        return is_array($a_languages) ? array_keys($a_languages) : [];
    }
}