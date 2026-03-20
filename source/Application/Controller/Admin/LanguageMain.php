<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article main selectlist manager.
 * Performs collection and updatind (on user submit) main item information.
 */
class Language_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Current shop base languages
     *
     * @var array
     */
    protected $_a_lang_data;
    /**
     * Current shop base languages parameters
     *
     * @var array
     */
    protected $_a_lang_params;
    /**
     * Current shop base languages base urls
     *
     * @var array
     */
    protected $_a_languages_urls;
    /**
     * Current shop base languages base ssl urls
     *
     * @var array
     */
    protected $_a_languages_ssl_urls;
    /** @var \OxidEsales\Eshop\Core\NoJsValidator */
    private $no_js_validator;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $s_ox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        //loading languages info from config
        $this->_a_lang_data = $this->get_languages();
        if (isset($s_ox_id) && $s_ox_id != '-1') {
            //checking if translations files exists
            $this->check_lang_translations($s_ox_id);
            $this->_a_view_data['edit'] = $this->get_language_info($s_ox_id);
        }
        return 'language_main';
    }
    /**
     * Saves selection list parameters changes.
     */
    public function save(): void
    {
        parent::save();
        $s_ox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (!isset($a_params['active'])) {
            $a_params['active'] = 0;
        }
        if (!isset($a_params['default'])) {
            $a_params['default'] = false;
        }
        if (empty($a_params['sort'])) {
            $a_params['sort'] = '99999';
        }
        //loading languages info from config
        $this->_a_lang_data = $this->get_languages();
        //checking input errors
        if (!$this->validate_input()) {
            return;
        }
        $bl_view_error = false;
        // if changed language abbervation, updating it for all arrays related with languages
        if ($s_ox_id != -1 && $s_ox_id != $a_params['abbr']) {
            // #0004850 preventing changing abbr for main language with base id = 0
            if ((int) $this->_a_lang_data['params'][$s_ox_id]['baseId'] == 0) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
                $o_ex->set_message('LANGUAGE_ABBRCHANGEMAINLANG_WARNING');
                \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex);
                $a_params['abbr'] = $s_ox_id;
            } else {
                $this->update_abbervation($s_ox_id, $a_params['abbr']);
                $s_ox_id = $a_params['abbr'];
                $this->set_edit_object_id($s_ox_id);
                $bl_view_error = true;
            }
        }
        // if adding new language, setting lang id to abbervation
        if ($bl_new_language = $s_ox_id == -1) {
            $s_ox_id = $a_params['abbr'];
            $this->_a_lang_data['params'][$s_ox_id]['baseId'] = $this->get_available_lang_base_id();
            $this->set_edit_object_id($s_ox_id);
        }
        //updating language description
        $this->_a_lang_data['lang'][$s_ox_id] = $a_params['desc'];
        //updating language parameters
        $this->_a_lang_data['params'][$s_ox_id]['active'] = $a_params['active'];
        $this->_a_lang_data['params'][$s_ox_id]['default'] = $a_params['default'];
        $this->_a_lang_data['params'][$s_ox_id]['sort'] = $a_params['sort'];
        //if setting lang as default
        if ($a_params['default'] == '1') {
            $this->set_default_lang($s_ox_id);
        }
        //updating language urls
        $i_base_id = $this->_a_lang_data['params'][$s_ox_id]['baseId'];
        $this->_a_lang_data['urls'][$i_base_id] = $a_params['baseurl'];
        $this->_a_lang_data['sslUrls'][$i_base_id] = $a_params['basesslurl'];
        //sort parameters, urls and languages arrays by language base id
        $this->sort_lang_arrays_by_base_id();
        $this->_a_view_data['updatelist'] = '1';
        if ($this->is_valid_language_data($this->_a_lang_data)) {
            //saving languages info
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('aarr', 'aLanguageParams', $this->_a_lang_data['params']);
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('aarr', 'aLanguages', $this->_a_lang_data['lang']);
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('arr', 'aLanguageURLs', $this->_a_lang_data['urls']);
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('arr', 'aLanguageSSLURLs', $this->_a_lang_data['sslUrls']);
            //checking if added language already has created multilang fields
            //with new base ID - if not, creating new fields
            if ($bl_new_language) {
                if (!$this->check_multilang_fields_exists_in_db($s_ox_id)) {
                    $this->add_new_multilang_fields_to_db();
                } else {
                    $bl_view_error = true;
                }
            }
            // show message for user to generate views
            if ($bl_view_error) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
                $o_ex->set_message('LANGUAGE_ERRORGENERATEVIEWS');
                \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex);
            }
        }
    }
    /**
     * Get selected language info
     *
     * @param string $sOxId language abbervation
     *
     * @return array
     */
    protected function get_language_info($s_ox_id)
    {
        $s_default_lang = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sDefaultLang');
        $a_lang_data = $this->_a_lang_data['params'][$s_ox_id];
        $a_lang_data['abbr'] = $s_ox_id;
        $a_lang_data['desc'] = $this->_a_lang_data['lang'][$s_ox_id];
        $a_lang_data['baseurl'] = $this->_a_lang_data['urls'][$a_lang_data['baseId']];
        $a_lang_data['basesslurl'] = $this->_a_lang_data['sslUrls'][$a_lang_data['baseId']];
        $a_lang_data['default'] = $this->_a_lang_data['params'][$s_ox_id]['baseId'] == $s_default_lang ? true : false;
        return $a_lang_data;
    }
    /**
     * Languages array setter
     *
     * @param array $aLangData languages parameters array
     */
    protected function set_languages($a_lang_data)
    {
        $this->_a_lang_data = $a_lang_data;
    }
    /**
     * Loads from config all data related with languages.
     * If no languages parameters array exists, sets default parameters values.
     * Returns collected languages parameters array.
     *
     * @return array
     */
    protected function get_languages()
    {
        $a_lang_data['params'] = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aLanguageParams');
        $a_lang_data['lang'] = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aLanguages');
        $a_lang_data['urls'] = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aLanguageURLs');
        $a_lang_data['sslUrls'] = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aLanguageSSLURLs');
        // empty languages parameters array - creating new one with default values
        if (!is_array($a_lang_data['params'])) {
            $a_lang_data['params'] = $this->assign_default_lang_params($a_lang_data['lang']);
        }
        return $a_lang_data;
    }
    /**
     * Replaces languages arrays keys by new value.
     *
     * @param string $sOldId old ID
     * @param string $sNewId new ID
     */
    protected function update_abbervation($s_old_id, $s_new_id)
    {
        foreach (array_keys($this->_a_lang_data) as $s_type_key) {
            if (is_array($this->_a_lang_data[$s_type_key]) && count($this->_a_lang_data[$s_type_key]) > 0) {
                if ($s_type_key == 'urls') {
                    continue;
                }
                if ($s_type_key == 'sslUrls') {
                    continue;
                }
                $a_keys = array_keys($this->_a_lang_data[$s_type_key]);
                $a_values = array_values($this->_a_lang_data[$s_type_key]);
                //find and replace key
                $i_replace_id = array_search($s_old_id, $a_keys);
                $a_keys[$i_replace_id] = $s_new_id;
                $this->_a_lang_data[$s_type_key] = array_combine($a_keys, $a_values);
            }
        }
    }
    /**
     * Sort languages, languages parameters, urls, ssl urls arrays according
     * base land ID
     */
    protected function sort_lang_arrays_by_base_id()
    {
        $a_urls = [];
        $a_ssl_urls = [];
        $a_languages = [];
        uasort($this->_a_lang_data['params'], $this->sort_lang_params_by_base_id_callback(...));
        foreach ($this->_a_lang_data['params'] as $s_abbr => $a_params) {
            $i_id = (int) $a_params['baseId'];
            $a_urls[$i_id] = $this->_a_lang_data['urls'][$i_id];
            $a_ssl_urls[$i_id] = $this->_a_lang_data['sslUrls'][$i_id];
            $a_languages[$s_abbr] = $this->_a_lang_data['lang'][$s_abbr];
        }
        $this->_a_lang_data['lang'] = $a_languages;
        $this->_a_lang_data['urls'] = $a_urls;
        $this->_a_lang_data['sslUrls'] = $a_ssl_urls;
    }
    /**
     * Assign default values for eache language
     *
     * @param array $aLanguages language array
     *
     * @return array
     */
    protected function assign_default_lang_params($a_languages)
    {
        $a_params = [];
        $i_base_id = 0;
        foreach (array_keys($a_languages) as $s_ox_id) {
            $a_params[$s_ox_id]['baseId'] = $i_base_id;
            $a_params[$s_ox_id]['active'] = 1;
            $a_params[$s_ox_id]['sort'] = $i_base_id + 1;
            $i_base_id++;
        }
        return $a_params;
    }
    /**
     * Sets default language base ID to config var 'sDefaultLang'
     *
     * @param string $sOxId language abbervation
     */
    protected function set_default_lang($s_ox_id)
    {
        $s_default_id = $this->_a_lang_data['params'][$s_ox_id]['baseId'];
        \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('str', 'sDefaultLang', $s_default_id);
    }
    /**
     * Get availabale language base ID
     *
     * @return int
     */
    protected function get_available_lang_base_id()
    {
        $a_base_id = [];
        foreach ($this->_a_lang_data['params'] as $a_lang) {
            $a_base_id[] = $a_lang['baseId'];
        }
        $i_new_id = 0;
        sort($a_base_id);
        $i_total = count($a_base_id);
        //getting first available id
        while ($i_new_id <= $i_total - 1) {
            if ($i_new_id !== $a_base_id[$i_new_id]) {
                break;
            }
            $i_new_id++;
        }
        return $i_new_id;
    }
    /**
     * Check selected language has translation file lang.php
     * If not - displays warning
     *
     * @param string $sOxId language abbervation
     */
    protected function check_lang_translations($s_ox_id)
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_dir = dirname((string) $my_config->get_translations_dir('lang.php', $s_ox_id));
        if (empty($s_dir)) {
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
            $o_ex->set_message('LANGUAGE_NOTRANSLATIONS_WARNING');
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex);
        }
    }
    /**
     * Check if selected language already has multilanguage fields in DB
     *
     * @param string $sOxId language abbervation
     *
     * @return bool
     */
    protected function check_multilang_fields_exists_in_db($s_ox_id)
    {
        $i_base_id = $this->_a_lang_data['params'][$s_ox_id]['baseId'];
        $s_table = get_lang_table_name('oxarticles', $i_base_id);
        $s_column = 'oxtitle' . \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_tag($i_base_id);
        $o_db_metadata = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
        return $o_db_metadata->table_exists($s_table) && $o_db_metadata->field_exists($s_column, $s_table);
    }
    /**
     * Adding new language to DB - creating new multilangue fields with new
     * language ID (e.g. oxtitle_4)
     */
    protected function add_new_multilang_fields_to_db()
    {
        //creating new multilingual fields with new id over whole DB
        $o_db_meta = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
        try {
            $o_db_meta->add_new_lang_to_db();
        } catch (Exception $o_ex) {
            //show warning
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
            $o_ex->set_message('LANGUAGE_ERROR_ADDING_MULTILANG_FIELDS');
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex);
        }
    }
    /**
     * Check if language already exists
     *
     * @param string $sAbbr language abbervation
     *
     * @return bool
     */
    protected function check_lang_exists($s_abbr)
    {
        $a_abbrs = array_keys($this->_a_lang_data['lang']);
        return in_array($s_abbr, $a_abbrs);
    }
    /**
     * Callback function for sorting languages arraty. Sorts array according
     * 'baseId' parameter
     *
     * @param object $oLang1 language array
     * @param object $oLang2 language array
     *
     * @return bool
     */
    protected function sort_lang_params_by_base_id_callback($o_lang1, $o_lang2)
    {
        return $o_lang1['baseId'] < $o_lang2['baseId'] ? -1 : 1;
    }
    /**
     * Check language input errors
     *
     * @return bool
     */
    protected function validate_input()
    {
        $result = true;
        $oxid = $this->get_edit_object_id();
        $parameters = Registry::get_request()->get_request_escaped_parameter('editval');
        // if creating new language, checking if language already exists with
        // entered language abbreviation
        if ($oxid == -1 && $this->check_lang_exists($parameters['abbr'])) {
            $this->add_display_exception('LANGUAGE_ALREADYEXISTS_ERROR');
            $result = false;
        }
        // As the abbreviation is used in database view creation, check for allowed characters
        if (!$this->check_abbreviation_allowed_characters($parameters['abbr'])) {
            $this->add_display_exception('LANGUAGE_ABBREVIATION_INVALID_ERROR');
            $result = false;
        }
        // checking if language name is not empty
        if (empty($parameters['desc'])) {
            $this->add_display_exception('LANGUAGE_EMPTYLANGUAGENAME_ERROR');
            $result = false;
        }
        return $result;
    }
    /**
     * Check if language abbreviation contains only allowed characters.
     * Abbreviation is used for view creation, so to be on the safe side with MySQL,
     * only allow characters [0-9,a-z,A-Z_] (basic Latin letters, digits 0-9, underscore).
     * Allowing other characters means table names would have to be escaped with backticks in all queries.
     *
     * @param string $abbreviation language abbreviation
     *
     * @throws \Exception if pattern does not match
     *
     * @return bool
     */
    protected function check_abbreviation_allowed_characters($abbreviation)
    {
        $pattern = '/^[a-zA-Z0-9_]*$/';
        $result = preg_match($pattern, $abbreviation);
        if ($result === false) {
            throw new \Exception(preg_last_error(), $pattern, $abbreviation);
        }
        return (bool) $result;
    }
    /**
     * Add exception to be displayed in frontend.
     *
     * @param string $message Language constant
     */
    protected function add_display_exception($message)
    {
        $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
        $exception->set_message($message);
        \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($exception);
    }
    /**
     * Validates provided language data and sets error to view in case it is not valid.
     *
     * @param array $aLanguageData
     *
     * @return bool
     */
    protected function is_valid_language_data($a_language_data)
    {
        $bl_valid = true;
        $config_validator = $this->get_no_js_validator();
        foreach ($a_language_data as $m_language_data_parameters) {
            if (is_array($m_language_data_parameters)) {
                // Recursion till we gonna have a string.
                $bl_deep_result = $this->is_valid_language_data($m_language_data_parameters);
                $bl_valid = $bl_deep_result === false ? $bl_deep_result : $bl_valid;
            } elseif (!$config_validator->is_valid($m_language_data_parameters)) {
                $bl_valid = false;
                $error = ox_new(\Oxid_Esales\Eshop\Core\Display_Error::class);
                $error->set_format_parameters(htmlspecialchars((string) $m_language_data_parameters));
                $error->set_message('SHOP_CONFIG_ERROR_INVALID_VALUE');
                \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($error);
            }
        }
        return $bl_valid;
    }
    /**
     * @return \OxidEsales\Eshop\Core\NoJsValidator
     */
    protected function get_no_js_validator()
    {
        if (is_null($this->no_js_validator)) {
            $this->no_js_validator = ox_new(\Oxid_Esales\Eshop\Core\No_Js_Validator::class);
        }
        return $this->no_js_validator;
    }
}