<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
/**
 * Admin selectlist list manager.
 */
class Language_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Default sorting parameter.
     *
     * @var string
     */
    protected $_s_def_sort_field = 'sort';
    /**
     * Default sorting order.
     *
     * @var string
     */
    protected $_s_def_sort_order = 'asc';
    /**
     * Checks for Malladmin rights
     */
    public function delete_entry(): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_ox_id = $this->get_edit_object_id();
        $a_lang_data['params'] = $my_config->get_config_param('aLanguageParams');
        $a_lang_data['lang'] = $my_config->get_config_param('aLanguages');
        $a_lang_data['urls'] = $my_config->get_config_param('aLanguageURLs');
        $a_lang_data['sslUrls'] = $my_config->get_config_param('aLanguageSSLURLs');
        $i_base_id = (int) $a_lang_data['params'][$s_ox_id]['baseId'];
        // preventing deleting main language with base id = 0
        if ($i_base_id == 0) {
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
            $o_ex->set_message('LANGUAGE_DELETINGMAINLANG_WARNING');
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex);
            return;
        }
        // unsetting selected lang from languages arrays
        unset($a_lang_data['params'][$s_ox_id]);
        unset($a_lang_data['lang'][$s_ox_id]);
        unset($a_lang_data['urls'][$i_base_id]);
        unset($a_lang_data['sslUrls'][$i_base_id]);
        //saving languages info back to DB
        $my_config->save_shop_conf_var('aarr', 'aLanguageParams', $a_lang_data['params']);
        $my_config->save_shop_conf_var('aarr', 'aLanguages', $a_lang_data['lang']);
        $my_config->save_shop_conf_var('arr', 'aLanguageURLs', $a_lang_data['urls']);
        $my_config->save_shop_conf_var('arr', 'aLanguageSSLURLs', $a_lang_data['sslUrls']);
        //if deleted language was default, setting defalt lang to 0
        if ($i_base_id == $my_config->get_config_param('sDefaultLang')) {
            $my_config->save_shop_conf_var('str', 'sDefaultLang', 0);
        }
    }
    /**
     * Executes parent method parent::render() and returns name of template
     * file "selectlist_list".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_a_view_data['mylist'] = $this->get_languages_list();
        return 'language_list';
    }
    /**
     * Collects shop languages list.
     *
     * @return array
     */
    protected function get_languages_list()
    {
        $a_lang_params = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aLanguageParams');
        $a_languages = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_array();
        $s_default_lang = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sDefaultLang');
        foreach ($a_languages as $s_key => $s_value) {
            $s_ox_id = $s_value->oxid;
            $a_languages[$s_key]->active = !isset($a_lang_params[$s_ox_id]['active']) ? 1 : $a_lang_params[$s_ox_id]['active'];
            $a_languages[$s_key]->default = $a_lang_params[$s_ox_id]['baseId'] == $s_default_lang ? true : false;
            $a_languages[$s_key]->sort = $a_lang_params[$s_ox_id]['sort'];
        }
        if (is_array($a_lang_params)) {
            $a_sorting = $this->get_list_sorting();
            if (is_array($a_sorting)) {
                foreach ($a_sorting as $a_field_sorting) {
                    foreach ($a_field_sorting as $s_field => $s_dir) {
                        $this->_s_def_sort_field = $s_field;
                        $this->_s_def_sort_order = $s_dir;
                        if ($s_field == 'active') {
                            //reverting sort order for field 'active'
                            $this->_s_def_sort_order = 'desc';
                        }
                        break 2;
                    }
                }
            }
            uasort($a_languages, $this->sort_languages_callback(...));
        }
        return $a_languages;
    }
    /**
     * Callback function for sorting languages objects. Sorts array according
     * 'sort' parameter
     *
     * @param object $oLang1 language object
     * @param object $oLang2 language object
     *
     * @return bool
     */
    protected function sort_languages_callback($o_lang1, $o_lang2)
    {
        $s_sort_param = $this->_s_def_sort_field;
        $s_val1 = is_string($o_lang1->{$s_sort_param}) ? strtolower($o_lang1->{$s_sort_param}) : $o_lang1->{$s_sort_param};
        $s_val2 = is_string($o_lang2->{$s_sort_param}) ? strtolower($o_lang2->{$s_sort_param}) : $o_lang2->{$s_sort_param};
        if ($this->_s_def_sort_order == 'asc') {
            return $s_val1 < $s_val2 ? -1 : 1;
        }
        return $s_val1 > $s_val2 ? -1 : 1;
    }
    /**
     * Resets all multilanguage fields with specific language id
     * to default value in all tables.
     *
     * @param string $iLangId language ID
     */
    protected function reset_multi_lang_db_fields($i_lang_id)
    {
        $i_lang_id = (int) $i_lang_id;
        //skipping reseting language with id = 0
        if ($i_lang_id) {
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->start_transaction();
            try {
                $o_db_meta = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
                $o_db_meta->reset_language($i_lang_id);
                \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->commit_transaction();
            } catch (Exception $o_ex) {
                // if exception, rollBack everything
                \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->rollback_transaction();
                //show warning
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
                $o_ex->set_message('LANGUAGE_ERROR_RESETING_MULTILANG_FIELDS');
                \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex);
            }
        }
    }
}