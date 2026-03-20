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
 * Admin article main attributes manager.
 * There is possibility to change attribute description, assign articles to
 * this attribute, etc.
 * Admin Menu: Manage Products -> Attributes -> Main.
 */
class Attribute_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $o_attr = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        // copy this tree for our article choose
        if (isset($sox_id) && $sox_id != '-1') {
            // generating category tree for select list
            $this->create_category_tree('artcattree', $sox_id);
            // load object
            $o_attr->load_in_lang($this->_i_edit_lang, $sox_id);
            //Disable editing for derived items
            if ($o_attr->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            $o_other_lang = $o_attr->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_attr->load_in_lang(key($o_other_lang), $sox_id);
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
        $this->_a_view_data['edit'] = $o_attr;
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_attribute_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Attribute_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_attribute_main_ajax->get_columns();
            return 'popups/attribute_main';
        }
        return 'attribute_main';
    }
    /**
     * Saves article attributes.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_attr = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
        if ($sox_id != '-1') {
            $o_attr->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxattribute__oxid'] = null;
            //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        }
        //Disable editing for derived items
        if ($o_attr->is_derived()) {
            return;
        }
        $o_attr->set_language(0);
        $o_attr->assign($a_params);
        $o_attr->set_language($this->_i_edit_lang);
        $o_attr = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->process_files($o_attr);
        $o_attr->save();
        $this->set_edit_object_id($o_attr->get_id());
    }
    /**
     * Saves attribute data to different language (eg. english).
     */
    public function saveinnlang(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_attr = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
        if ($sox_id != '-1') {
            $o_attr->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxattribute__oxid'] = null;
        }
        //Disable editing for derived items
        if ($o_attr->is_derived()) {
            return;
        }
        $o_attr->set_language(0);
        $o_attr->assign($a_params);
        // apply new language
        $o_attr->set_language(Registry::get_request()->get_request_escaped_parameter('new_lang'));
        $o_attr->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_attr->get_id());
    }
}