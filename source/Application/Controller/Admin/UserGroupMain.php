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
 * Admin article main usergroup manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: User Administration -> User Groups -> Main.
 */
class User_Group_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_group = ox_new(\Oxid_Esales\Eshop\Application\Model\Groups::class);
            $o_group->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_group->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_group->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_group;
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
            $o_usergroup_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\User_Group_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_usergroup_main_ajax->get_columns();
            return 'popups/usergroup_main';
        }
        return 'usergroup_main';
    }
    /**
     * Saves changed usergroup parameters.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (!isset($a_params['oxgroups__oxactive'])) {
            $a_params['oxgroups__oxactive'] = 0;
        }
        $o_group = ox_new(\Oxid_Esales\Eshop\Application\Model\Groups::class);
        if ($sox_id != '-1') {
            $o_group->load($sox_id);
        } else {
            $a_params['oxgroups__oxid'] = null;
        }
        $o_group->set_language(0);
        $o_group->assign($a_params);
        $o_group->set_language($this->_i_edit_lang);
        $o_group->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_group->get_id());
    }
    /**
     * Saves changed selected group parameters in different language.
     */
    public function saveinnlang(): void
    {
        $this->save();
    }
}