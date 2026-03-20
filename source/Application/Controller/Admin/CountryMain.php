<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Country;
use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin article main selectlist manager.
 * Performs collection and updatind (on user submit) main item information.
 */
class Country_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_country = ox_new(Country::class);
            $o_country->load_in_lang($this->_i_edit_lang, $sox_id);
            if ($o_country->is_foreign_country()) {
                $this->_a_view_data['blForeignCountry'] = true;
            } else {
                $this->_a_view_data['blForeignCountry'] = false;
            }
            $o_other_lang = $o_country->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_country->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_country;
            // remove already created languages
            $a_lang = array_diff(Registry::get_lang()->get_language_names(), $o_other_lang);
            if (count($a_lang)) {
                $this->_a_view_data['posslang'] = $a_lang;
            }
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
        } else {
            $this->_a_view_data['blForeignCountry'] = true;
        }
        return 'country_main';
    }
    /**
     * Saves selection list parameters changes.
     */
    public function save(): void
    {
        parent::save();
        $oxid_id = $this->get_edit_object_id();
        $query_parameters = Registry::get_request()->get_request_escaped_parameter('editval');
        if ($query_parameters['oxcountry__oxvatstatus'] === '1' && empty($query_parameters['oxcountry__oxvatinprefix'])) {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_INPUT_VAT_PREFIX_EMPTY');
            return;
        }
        if (!isset($query_parameters['oxcountry__oxactive'])) {
            $query_parameters['oxcountry__oxactive'] = 0;
        }
        $country = ox_new(Country::class);
        if ($oxid_id != '-1') {
            $country->load_in_lang($this->_i_edit_lang, $oxid_id);
        } else {
            $query_parameters['oxcountry__oxid'] = null;
        }
        $country->set_language(0);
        $country->assign($query_parameters);
        $country->set_language($this->_i_edit_lang);
        $country = Registry::get_utils_file()->process_files($country);
        $country->save();
        $this->set_edit_object_id($country->get_id());
    }
    /**
     * Saves selection list parameters changes in different language (eg. english).
     */
    public function saveinnlang(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (!isset($a_params['oxcountry__oxactive'])) {
            $a_params['oxcountry__oxactive'] = 0;
        }
        $o_country = ox_new(Country::class);
        if ($sox_id != '-1') {
            $o_country->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxcountry__oxid'] = null;
            //$aParams = $oCountry->ConvertNameArray2Idx( $aParams);
        }
        $o_country->set_language(0);
        $o_country->assign($a_params);
        $o_country->set_language($this->_i_edit_lang);
        $o_country->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_country->get_id());
    }
}