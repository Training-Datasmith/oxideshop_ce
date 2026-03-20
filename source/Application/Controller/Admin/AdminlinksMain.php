<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use stdClass;
/**
 * Admin links details manager.
 * Creates form for submitting new admin links or modifying old ones.
 * Admin Menu: Customer Info -> Links.
 */
class Adminlinks_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Sets link information data (or leaves empty), returns name of template
     * file "adminlinks_main".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $o_links = ox_new(\Oxid_Esales\Eshop\Application\Model\Links::class, $table_view_name_generator->get_view_name('oxlinks'));
        if (isset($sox_id) && $sox_id != '-1') {
            $o_links->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_links->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_links->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_links;
            //Disable editing for derived items
            if ($o_links->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            // remove already created languages
            $this->_a_view_data['posslang'] = array_diff(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_names(), $o_other_lang);
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
        }
        // generate editor
        $this->_a_view_data['editor'] = $this->generate_text_editor('100%', 255, $o_links, 'oxlinks__oxurldesc', 'links.css');
        return 'adminlinks_main';
    }
    /**
     * Saves information about link (active, date, URL, description, etc.) to DB.
     */
    public function save(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (!isset($a_params['oxlinks__oxactive'])) {
            $a_params['oxlinks__oxactive'] = 0;
        }
        // adds space to the end of URL description to keep new added links visible
        // if URL description left empty
        if (isset($a_params['oxlinks__oxurldesc']) && strlen($a_params['oxlinks__oxurldesc']) == 0) {
            $a_params['oxlinks__oxurldesc'] .= ' ';
        }
        if (!$a_params['oxlinks__oxinsert']) {
            // sets default (?) date format to output
            // else if possible - changes date format to system compatible
            $s_date = date(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('simpleDateFormat'));
            if ($s_date == 'simpleDateFormat') {
                $a_params['oxlinks__oxinsert'] = date('Y-m-d');
            } else {
                $a_params['oxlinks__oxinsert'] = $s_date;
            }
        }
        $i_edit_language = Registry::get_request()->get_request_escaped_parameter('editlanguage');
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $o_links = ox_new(\Oxid_Esales\Eshop\Application\Model\Links::class, $table_view_name_generator->get_view_name('oxlinks'));
        if ($sox_id != '-1') {
            //$oLinks->load( $soxId );
            $o_links->load_in_lang($i_edit_language, $sox_id);
            //Disable editing for derived items
            if ($o_links->is_derived()) {
                return;
            }
        } else {
            $a_params['oxlinks__oxid'] = null;
        }
        //$aParams = $oLinks->ConvertNameArray2Idx( $aParams);
        $o_links->set_language(0);
        $o_links->assign($a_params);
        $o_links->set_language($i_edit_language);
        $o_links->save();
        parent::save();
        // set oxid if inserted
        $this->set_edit_object_id($o_links->get_id());
    }
    /**
     * Saves link description in different languages (eg. english).
     */
    public function saveinnlang(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (!isset($a_params['oxlinks__oxactive'])) {
            $a_params['oxlinks__oxactive'] = 0;
        }
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $o_links = ox_new(\Oxid_Esales\Eshop\Application\Model\Links::class, $table_view_name_generator->get_view_name('oxlinks'));
        $i_edit_language = Registry::get_request()->get_request_escaped_parameter('editlanguage');
        if ($sox_id != '-1') {
            $o_links->load_in_lang($i_edit_language, $sox_id);
        } else {
            $a_params['oxlinks__oxid'] = null;
            //$aParams = $oLinks->ConvertNameArray2Idx( $aParams);
        }
        //Disable editing for derived items
        if ($o_links->is_derived()) {
            return;
        }
        $o_links->set_language(0);
        $o_links->assign($a_params);
        // apply new language
        $o_links->set_language(Registry::get_request()->get_request_escaped_parameter('new_lang'));
        $o_links->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_links->get_id());
    }
}