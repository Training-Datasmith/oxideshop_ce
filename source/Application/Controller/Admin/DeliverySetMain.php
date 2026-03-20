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
 * Admin article main deliveryset manager.
 * There is possibility to change deliveryset name, article, user
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main Sets.
 */
class Delivery_Set_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $odeliveryset = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set::class);
            $odeliveryset->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $odeliveryset->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $odeliveryset->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $odeliveryset;
            //Disable editing for derived articles
            if ($odeliveryset->is_derived()) {
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
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_deliveryset_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Set_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_deliveryset_main_ajax->get_columns();
            return 'popups/deliveryset_main';
        }
        return 'deliveryset_main';
    }
    /**
     * Saves deliveryset information changes.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_del_set = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set::class);
        if ($sox_id != '-1') {
            $o_del_set->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxdeliveryset__oxid'] = null;
        }
        // checkbox handling
        if (!isset($a_params['oxdeliveryset__oxactive'])) {
            $a_params['oxdeliveryset__oxactive'] = 0;
        }
        //Disable editing for derived articles
        if ($o_del_set->is_derived()) {
            return;
        }
        //$aParams = $oDelSet->ConvertNameArray2Idx( $aParams);
        $o_del_set->set_language(0);
        $o_del_set->assign($a_params);
        $o_del_set->set_language($this->_i_edit_lang);
        $o_del_set = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->process_files($o_del_set);
        $o_del_set->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_del_set->get_id());
    }
    /**
     * Saves deliveryset data to different language (eg. english).
     */
    public function saveinnlang(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (!isset($a_params['oxdeliveryset__oxactive'])) {
            $a_params['oxdeliveryset__oxactive'] = 0;
        }
        $o_del_set = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set::class);
        if ($sox_id != '-1') {
            $o_del_set->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxdeliveryset__oxid'] = null;
            //$aParams = $oDelSet->ConvertNameArray2Idx( $aParams);
        }
        $o_del_set->set_language(0);
        $o_del_set->assign($a_params);
        //Disable editing for derived articles
        if ($o_del_set->is_derived()) {
            return;
        }
        // apply new language
        $o_del_set->set_language(Registry::get_request()->get_request_escaped_parameter('new_lang'));
        $o_del_set->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_del_set->get_id());
    }
}