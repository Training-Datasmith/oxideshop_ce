<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use stdClass;
/**
 * Admin vendor main screen.
 * Performs collection and updating (on user submit) main item information.
 */
class Vendor_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Executes parent method parent::render(),
     * and returns name of template file
     * "vendor_main".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
            $o_vendor->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_vendor->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_vendor->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_vendor;
            // category tree
            $this->create_category_tree('artcattree');
            //Disable editing for derived articles
            if ($o_vendor->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
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
        }
        if ($this->get_view_config()->is_alt_image_server_configured()) {
            $this->_a_view_data['imageUrl'] = Container_Facade::get_parameter('oxid_esales.alternative_image_url');
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_vendor_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Vendor_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_vendor_main_ajax->get_columns();
            return 'popups/vendor_main';
        }
        return 'vendor_main';
    }
    /**
     * Saves selection list parameters changes.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (!isset($a_params['oxvendor__oxactive'])) {
            $a_params['oxvendor__oxactive'] = 0;
        }
        $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        if ($sox_id != '-1') {
            $o_vendor->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxvendor__oxid'] = null;
        }
        //Disable editing for derived articles
        if ($o_vendor->is_derived()) {
            return;
        }
        $o_vendor->set_language(0);
        $o_vendor->assign($a_params);
        $o_vendor->set_language($this->_i_edit_lang);
        $o_vendor = Registry::get_utils_file()->process_files($o_vendor);
        $o_vendor->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_vendor->get_id());
    }
    /**
     * Saves selection list parameters changes in different language (eg. english).
     */
    public function saveinnlang(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (!isset($a_params['oxvendor__oxactive'])) {
            $a_params['oxvendor__oxactive'] = 0;
        }
        $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        if ($sox_id != '-1') {
            $o_vendor->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxvendor__oxid'] = null;
        }
        //Disable editing for derived articles
        if ($o_vendor->is_derived()) {
            return;
        }
        $o_vendor->set_language(0);
        $o_vendor->assign($a_params);
        $o_vendor->set_language($this->_i_edit_lang);
        $o_vendor = Registry::get_utils_file()->process_files($o_vendor);
        $o_vendor->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_vendor->get_id());
    }
}