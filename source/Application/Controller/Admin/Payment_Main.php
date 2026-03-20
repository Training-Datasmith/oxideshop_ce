<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin article main payment manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: Shop Settings -> Payment Methods -> Main.
 */
class Payment_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Keeps all act. fields to store
     */
    protected $_a_field_array;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        // remove itm from list
        unset($this->_a_view_data['sumtype'][2]);
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
        if (isset($sox_id) && $sox_id != '-1') {
            $o_payment->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_payment->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_payment->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_payment;
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
            // #708
            $this->_a_view_data['aFieldNames'] = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_payment->oxpayments__oxvaldesc->value);
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_payment_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Payment_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_payment_main_ajax->get_columns();
            return 'popups/payment_main';
        }
        $this->_a_view_data['editor'] = $this->generate_text_editor('100%', 300, $o_payment, 'oxpayments__oxlongdesc');
        return 'payment_main';
    }
    /**
     * Saves payment parameters changes.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (!isset($a_params['oxpayments__oxactive'])) {
            $a_params['oxpayments__oxactive'] = 0;
        }
        if (!isset($a_params['oxpayments__oxchecked'])) {
            $a_params['oxpayments__oxchecked'] = 0;
        }
        $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
        if ($sox_id != '-1') {
            $o_payment->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxpayments__oxid'] = null;
            //$aParams = $oPayment->ConvertNameArray2Idx( $aParams);
        }
        $o_payment->set_language(0);
        $o_payment->assign($a_params);
        // setting add sum calculation rules
        $a_rules = (array) Registry::get_request()->get_request_escaped_parameter('oxpayments__oxaddsumrules');
        // if sum eqals 0, show notice, that default value will be used.
        if (empty($a_rules)) {
            $this->_a_view_data['noticeoxaddsumrules'] = 1;
        }
        $o_payment->oxpayments__oxaddsumrules = new \Oxid_Esales\Eshop\Core\Field(array_sum($a_rules));
        //#708
        if (!is_array($this->_a_field_array)) {
            $this->_a_field_array = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_payment->oxpayments__oxvaldesc->value);
        }
        // build value
        $s_valdesc = '';
        foreach ($this->_a_field_array as $o_field) {
            $s_valdesc .= $o_field->name . '__@@';
        }
        $o_payment->oxpayments__oxvaldesc = new \Oxid_Esales\Eshop\Core\Field($s_valdesc, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $o_payment->set_language($this->_i_edit_lang);
        $o_payment->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_payment->get_id());
    }
    /**
     * Saves payment parameters data in dofferent language (eg. english).
     */
    public function saveinnlang(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_obj = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
        if ($sox_id != '-1') {
            $o_obj->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxpayments__oxid'] = null;
            //$aParams = $oObj->ConvertNameArray2Idx( $aParams);
        }
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
        $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
        if ($o_payment->load_in_lang($this->_i_edit_lang, $this->get_edit_object_id())) {
            $a_del_fields = Registry::get_request()->get_request_escaped_parameter('aFields');
            $this->_a_field_array = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_payment->oxpayments__oxvaldesc->value);
            if (is_array($a_del_fields) && count($a_del_fields)) {
                foreach ($a_del_fields as $s_del_field) {
                    foreach ($this->_a_field_array as $s_key => $o_field) {
                        if ($o_field->name == $s_del_field) {
                            unset($this->_a_field_array[$s_key]);
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
        $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
        if ($o_payment->load_in_lang($this->_i_edit_lang, $this->get_edit_object_id())) {
            $this->_a_field_array = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($o_payment->oxpayments__oxvaldesc->value);
            $o_field = new stdClass();
            $o_field->name = Registry::get_request()->get_request_escaped_parameter('sAddField');
            if (!empty($o_field->name)) {
                $this->_a_field_array[] = $o_field;
            }
            $this->save();
        }
    }
}