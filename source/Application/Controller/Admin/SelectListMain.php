<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
if (!defined('ERR_SUCCESS')) {
    DEFINE('ERR_SUCCESS', 1);
}
if (!defined('ERR_REQUIREDMISSING')) {
    DEFINE('ERR_REQUIREDMISSING', -1);
}
if (!defined('ERR_POSOUTOFBOUNDS')) {
    DEFINE('ERR_POSOUTOFBOUNDS', -2);
}
/**
 * Admin article main selectlist manager.
 * Performs collection and updatind (on user submit) main item information.
 */
class Select_List_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Keeps all act. fields to store
     */
    public $a_field_array;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $s_ox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        //create empty edit object
        $this->_a_view_data['edit'] = ox_new(\Oxid_Esales\Eshop\Application\Model\Select_List::class);
        if (isset($s_ox_id) && $s_ox_id != '-1') {
            // generating category tree for select list
            // A. hack - passing language by post as lists uses only language passed by POST/GET/SESSION
            $_POST['language'] = $this->_i_edit_lang;
            $this->create_category_tree('artcattree', $s_ox_id);
            // load object
            $o_attr = ox_new(\Oxid_Esales\Eshop\Application\Model\Select_List::class);
            $o_attr->load_in_lang($this->_i_edit_lang, $s_ox_id);
            $a_field_list = $o_attr->get_field_list();
            if (is_array($a_field_list)) {
                foreach ($a_field_list as $o_field) {
                    if ($o_field->price_unit == '%') {
                        $o_field->price = $o_field->fprice;
                    }
                }
            }
            $o_other_lang = $o_attr->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_attr->load_in_lang(key($o_other_lang), $s_ox_id);
            }
            $this->_a_view_data['edit'] = $o_attr;
            // Disable editing for derived items.
            if ($o_attr->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            // remove already created languages
            $a_lang = array_diff(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_names(), $o_other_lang);
            if (count($a_lang)) {
                $this->_a_view_data['posslang'] = $a_lang;
            }
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
            $i_err = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('iErrorCode');
            if (!$i_err) {
                $i_err = ERR_SUCCESS;
            }
            $this->_a_view_data['iErrorCode'] = $i_err;
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('iErrorCode', ERR_SUCCESS);
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_selectlist_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Select_List_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_selectlist_main_ajax->get_columns();
            return 'popups/selectlist_main';
        }
        return 'selectlist_main';
    }
    /**
     * Saves selection list parameters changes.
     */
    public function save(): void
    {
        parent::save();
        $s_ox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_attr = ox_new(\Oxid_Esales\Eshop\Application\Model\Select_List::class);
        if ($s_ox_id != '-1') {
            $o_attr->load_in_lang($this->_i_edit_lang, $s_ox_id);
        } else {
            $a_params['oxselectlist__oxid'] = null;
        }
        //Disable editing for derived items
        if ($o_attr->is_derived()) {
            return;
        }
        //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        $o_attr->set_language(0);
        $o_attr->assign($a_params);
        //#708
        if (!is_array($this->a_field_array)) {
            $this->a_field_array = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_attr->oxselectlist__oxvaldesc->get_raw_value());
        }
        // build value
        $o_attr->oxselectlist__oxvaldesc = new \Oxid_Esales\Eshop\Core\Field('', \Oxid_Esales\Eshop\Core\Field::T_RAW);
        foreach ($this->a_field_array as $o_field) {
            $o_attr->oxselectlist__oxvaldesc->set_value($o_attr->oxselectlist__oxvaldesc->get_raw_value() . $o_field->name, \Oxid_Esales\Eshop\Core\Field::T_RAW);
            if (isset($o_field->price) && $o_field->price) {
                $o_attr->oxselectlist__oxvaldesc->set_value($o_attr->oxselectlist__oxvaldesc->get_raw_value() . '!P!' . trim(str_replace(',', '.', $o_field->price)), \Oxid_Esales\Eshop\Core\Field::T_RAW);
                if ($o_field->price_unit == '%') {
                    $o_attr->oxselectlist__oxvaldesc->set_value($o_attr->oxselectlist__oxvaldesc->get_raw_value() . '%', \Oxid_Esales\Eshop\Core\Field::T_RAW);
                }
            }
            $o_attr->oxselectlist__oxvaldesc->set_value($o_attr->oxselectlist__oxvaldesc->get_raw_value() . '__@@', \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        $o_attr->set_language($this->_i_edit_lang);
        $o_attr->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_attr->get_id());
    }
    /**
     * Saves selection list parameters changes in different language (eg. english).
     */
    public function saveinnlang(): void
    {
        $s_ox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_obj = ox_new(\Oxid_Esales\Eshop\Application\Model\Select_List::class);
        if ($s_ox_id != '-1') {
            $o_obj->load_in_lang($this->_i_edit_lang, $s_ox_id);
        } else {
            $a_params['oxselectlist__oxid'] = null;
        }
        //Disable editing for derived items
        if ($o_obj->is_derived()) {
            return;
        }
        parent::save();
        //$aParams = $oObj->ConvertNameArray2Idx( $aParams);
        $o_obj->set_language(0);
        $o_obj->assign($a_params);
        // apply new language
        $o_obj->set_language(Registry::get_request()->get_request_escaped_parameter('new_lang'));
        $o_obj->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_obj->get_id());
    }
    /**
     * Deletes field from field array and stores object
     */
    public function del_fields(): void
    {
        $o_selectlist = ox_new(\Oxid_Esales\Eshop\Application\Model\Select_List::class);
        if ($o_selectlist->load_in_lang($this->_i_edit_lang, $this->get_edit_object_id())) {
            // Disable editing for derived items.
            if ($o_selectlist->is_derived()) {
                return;
            }
            $a_del_fields = Registry::get_request()->get_request_escaped_parameter('aFields');
            $this->a_field_array = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_selectlist->oxselectlist__oxvaldesc->get_raw_value());
            if (is_array($a_del_fields) && count($a_del_fields)) {
                foreach ($a_del_fields as $s_del_field) {
                    $s_del = $this->parse_field_name($s_del_field);
                    foreach ($this->a_field_array as $s_key => $o_field) {
                        if ($o_field->name == $s_del) {
                            unset($this->a_field_array[$s_key]);
                            break;
                        }
                    }
                }
                $this->save();
            }
        }
    }
    /**
     * Adds a field to field array and stores object
     */
    public function add_field(): void
    {
        $o_selectlist = ox_new(\Oxid_Esales\Eshop\Application\Model\Select_List::class);
        if ($o_selectlist->load_in_lang($this->_i_edit_lang, $this->get_edit_object_id())) {
            //Disable editing for derived items.
            if ($o_selectlist->is_derived()) {
                return;
            }
            $s_add_field = Registry::get_request()->get_request_escaped_parameter('sAddField');
            if (empty($s_add_field)) {
                \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('iErrorCode', ERR_REQUIREDMISSING);
                return;
            }
            $this->a_field_array = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_selectlist->oxselectlist__oxvaldesc->get_raw_value());
            $o_field = new stdClass();
            $o_field->name = $s_add_field;
            $o_field->price = Registry::get_request()->get_request_escaped_parameter('sAddFieldPriceMod');
            $o_field->price_unit = Registry::get_request()->get_request_escaped_parameter('sAddFieldPriceModUnit');
            $this->a_field_array[] = $o_field;
            if ($i_pos = Registry::get_request()->get_request_escaped_parameter('sAddFieldPos')) {
                if ($this->rearrange_fields($o_field, $i_pos - 1)) {
                    return;
                }
            }
            $this->save();
        }
    }
    /**
     * Modifies field from field array's first elem. and stores object
     */
    public function change_field(): void
    {
        $s_add_field = Registry::get_request()->get_request_escaped_parameter('sAddField');
        if (empty($s_add_field)) {
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('iErrorCode', ERR_REQUIREDMISSING);
            return;
        }
        $a_change_fields = Registry::get_request()->get_request_escaped_parameter('aFields');
        if (is_array($a_change_fields) && count($a_change_fields)) {
            $o_selectlist = ox_new(\Oxid_Esales\Eshop\Application\Model\Select_List::class);
            if ($o_selectlist->load_in_lang($this->_i_edit_lang, $this->get_edit_object_id())) {
                $this->a_field_array = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_selectlist->oxselectlist__oxvaldesc->get_raw_value());
                $s_change_field_name = $this->parse_field_name($a_change_fields[0]);
                foreach ($this->a_field_array as $s_key => $o_field) {
                    if ($o_field->name == $s_change_field_name) {
                        $this->a_field_array[$s_key]->name = $s_add_field;
                        $this->a_field_array[$s_key]->price = Registry::get_request()->get_request_escaped_parameter('sAddFieldPriceMod');
                        $this->a_field_array[$s_key]->price_unit = Registry::get_request()->get_request_escaped_parameter('sAddFieldPriceModUnit');
                        if ($i_pos = Registry::get_request()->get_request_escaped_parameter('sAddFieldPos')) {
                            if ($this->rearrange_fields($this->a_field_array[$s_key], $i_pos - 1)) {
                                return;
                            }
                        }
                        break;
                    }
                }
                $this->save();
            }
        }
    }
    /**
     * Resorts fields list and moves $oField to $iPos,
     * uses $this->aFieldArray for fields storage.
     *
     * @param object  $oField field to be moved
     * @param integer $iPos   new pos of the field
     *
     * @return bool - true if failed.
     */
    protected function rearrange_fields($o_field, $i_pos)
    {
        if (!isset($this->a_field_array) || !is_array($this->a_field_array)) {
            return true;
        }
        $i_field_count = count($this->a_field_array);
        if ($i_pos < 0 || $i_pos >= $i_field_count) {
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('iErrorCode', ERR_POSOUTOFBOUNDS);
            return true;
        }
        $i_current_pos = -1;
        for ($i = 0; $i < $i_field_count; $i++) {
            if ($this->a_field_array[$i] == $o_field) {
                $i_current_pos = $i;
                break;
            }
        }
        if ($i_current_pos == -1) {
            return true;
        }
        if ($i_current_pos == $i_pos) {
            return false;
        }
        $s_field = $this->a_field_array[$i_current_pos];
        if ($i_current_pos < $i_pos) {
            for ($i = $i_current_pos; $i < $i_pos; $i++) {
                $this->a_field_array[$i] = $this->a_field_array[$i + 1];
            }
            $this->a_field_array[$i_pos] = $s_field;
            return false;
        }
        for ($i = $i_current_pos; $i > $i_pos; $i--) {
            $this->a_field_array[$i] = $this->a_field_array[$i - 1];
        }
        $this->a_field_array[$i_pos] = $s_field;
        return false;
    }
    /**
     * Parses field name from given string
     * String format is: "someNr__@@someName__@@someTxt"
     *
     * @param string $sInput given string
     *
     * @return string - name
     */
    public function parse_field_name($s_input)
    {
        $a_input = explode('__@@', $s_input, 3);
        return $a_input[1];
    }
}