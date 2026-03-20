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
 * Admin wrapping main manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: System Administration -> Wrapping -> Main.
 */
class Wrapping_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_wrapping = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
            $o_wrapping->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_wrapping->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_wrapping->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_wrapping;
            //Disable editing for derived articles
            if ($o_wrapping->is_derived()) {
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
        return 'wrapping_main';
    }
    /**
     * Saves main wrapping parameters.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (!$this->validate_request_images()) {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_WRONG_IMAGE_FILE_TYPE');
            return;
        }
        // checkbox handling
        if (!isset($a_params['oxwrapping__oxactive'])) {
            $a_params['oxwrapping__oxactive'] = 0;
        }
        $o_wrapping = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
        if ($sox_id != '-1') {
            $o_wrapping->load_in_lang($this->_i_edit_lang, $sox_id);
            // #1173M - not all pic are deleted, after article is removed
            Registry::get_utils_pic()->overwrite_pic($o_wrapping, 'oxwrapping', 'oxpic', 'WP', '0', $a_params, Registry::get_config()->get_picture_dir(false));
        } else {
            $a_params['oxwrapping__oxid'] = null;
            //$aParams = $oWrapping->ConvertNameArray2Idx( $aParams);
        }
        //Disable editing for derived articles
        if ($o_wrapping->is_derived()) {
            return;
        }
        $o_wrapping->set_language(0);
        $o_wrapping->assign($a_params);
        $o_wrapping->set_language($this->_i_edit_lang);
        $o_wrapping = Registry::get_utils_file()->process_files($o_wrapping);
        $o_wrapping->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_wrapping->get_id());
    }
    /**
     * Saves main wrapping parameters.
     */
    public function saveinnlang(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (!isset($a_params['oxwrapping__oxactive'])) {
            $a_params['oxwrapping__oxactive'] = 0;
        }
        $o_wrapping = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
        if ($sox_id != '-1') {
            $o_wrapping->load($sox_id);
        } else {
            $a_params['oxwrapping__oxid'] = null;
            //$aParams = $oWrapping->ConvertNameArray2Idx( $aParams);
        }
        //Disable editing for derived articles
        if ($o_wrapping->is_derived()) {
            return;
        }
        $o_wrapping->set_language(0);
        $o_wrapping->assign($a_params);
        $o_wrapping->set_language($this->_i_edit_lang);
        $o_wrapping = Registry::get_utils_file()->process_files($o_wrapping);
        $o_wrapping->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_wrapping->get_id());
    }
}