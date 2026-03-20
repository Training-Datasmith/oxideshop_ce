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
 * Admin article main delivery manager.
 * There is possibility to change delivery name, article, user
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main.
 */
class Delivery_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        // remove itm from list
        unset($this->_a_view_data['sumtype'][2]);
        // Deliverytypes
        $a_del_types = $this->get_delivery_types();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_delivery = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery::class);
            $o_delivery->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_delivery->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_delivery->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_delivery;
            //Disable editing for derived articles
            if ($o_delivery->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            // remove already created languages
            $a_lang = array_diff($o_lang->get_language_names(), $o_other_lang);
            if (count($a_lang)) {
                $this->_a_view_data['posslang'] = $a_lang;
            }
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
            // set selected delivery type
            if (!$o_delivery->oxdelivery__oxdeltype->value) {
                $o_delivery->oxdelivery__oxdeltype = new \Oxid_Esales\Eshop\Core\Field('a');
                // default
            }
            $a_del_types[$o_delivery->oxdelivery__oxdeltype->value]->selected = true;
        }
        $this->_a_view_data['deltypes'] = $a_del_types;
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_delivery_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_delivery_main_ajax->get_columns();
            return 'popups/delivery_main';
        }
        return 'delivery_main';
    }
    /**
     * Saves delivery information changes.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_delivery = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery::class);
        if ($sox_id != '-1') {
            $o_delivery->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxdelivery__oxid'] = null;
        }
        // checkbox handling
        if (!isset($a_params['oxdelivery__oxactive'])) {
            $a_params['oxdelivery__oxactive'] = 0;
        }
        if (!isset($a_params['oxdelivery__oxfixed'])) {
            $a_params['oxdelivery__oxfixed'] = 0;
        }
        if (!isset($a_params['oxdelivery__oxfinalize'])) {
            $a_params['oxdelivery__oxfinalize'] = 0;
        }
        if (!isset($a_params['oxdelivery__oxsort'])) {
            $a_params['oxdelivery__oxsort'] = 9999;
        }
        //Disable editing for derived articles
        if ($o_delivery->is_derived()) {
            return;
        }
        $o_delivery->set_language(0);
        $o_delivery->assign($a_params);
        $o_delivery->set_language($this->_i_edit_lang);
        $o_delivery = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->process_files($o_delivery);
        $o_delivery->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_delivery->get_id());
    }
    /**
     * Saves delivery information changes.
     */
    public function saveinnlang(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_delivery = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery::class);
        if ($sox_id != '-1') {
            $o_delivery->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxdelivery__oxid'] = null;
        }
        // checkbox handling
        if (!isset($a_params['oxdelivery__oxactive'])) {
            $a_params['oxdelivery__oxactive'] = 0;
        }
        if (!isset($a_params['oxdelivery__oxfixed'])) {
            $a_params['oxdelivery__oxfixed'] = 0;
        }
        //Disable editing for derived articles
        if ($o_delivery->is_derived()) {
            return;
        }
        $o_delivery->set_language(0);
        $o_delivery->assign($a_params);
        $o_delivery->set_language($this->_i_edit_lang);
        $o_delivery = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->process_files($o_delivery);
        $o_delivery->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_delivery->get_id());
    }
    /**
     * returns delivery types
     *
     * @return array
     */
    public function get_delivery_types()
    {
        $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $i_lang = $o_lang->get_tpl_language();
        $a_del_types = [];
        $o_type = new stdClass();
        $o_type->s_type = 'a';
        // amount
        $o_type->s_desc = $o_lang->translate_string('amount', $i_lang);
        $a_del_types['a'] = $o_type;
        $o_type = new stdClass();
        $o_type->s_type = 's';
        // Size
        $o_type->s_desc = $o_lang->translate_string('size', $i_lang);
        $a_del_types['s'] = $o_type;
        $o_type = new stdClass();
        $o_type->s_type = 'w';
        // Weight
        $o_type->s_desc = $o_lang->translate_string('weight', $i_lang);
        $a_del_types['w'] = $o_type;
        $o_type = new stdClass();
        $o_type->s_type = 'p';
        // Price
        $o_type->s_desc = $o_lang->translate_string('price', $i_lang);
        $a_del_types['p'] = $o_type;
        return $a_del_types;
    }
}