<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Application\Model\User;
use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin article main user manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: User Administration -> Users -> Main.
 */
class User_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    private ?string $_s_save_error = null;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        // malladmin stuff
        $o_auth_user = ox_new(User::class);
        $o_auth_user->load_admin_user();
        $blis_mall_admin = $o_auth_user->oxuser__oxrights->value == 'malladmin';
        // User rights
        $a_user_rights = [];
        $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $i_tpl_lang = $o_lang->get_tpl_language();
        $i_pos = count($a_user_rights);
        $a_user_rights[$i_pos] = new stdClass();
        $a_user_rights[$i_pos]->name = $o_lang->translate_string('user', $i_tpl_lang);
        $a_user_rights[$i_pos]->id = 'user';
        if ($blis_mall_admin) {
            $i_pos = count($a_user_rights);
            $a_user_rights[$i_pos] = new stdClass();
            $a_user_rights[$i_pos]->id = 'malladmin';
            $a_user_rights[$i_pos]->name = $o_lang->translate_string('Admin', $i_tpl_lang);
        }
        $a_user_rights = $this->calculate_additional_rights($a_user_rights);
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_user = ox_new(User::class);
            $o_user->load($sox_id);
            $this->_a_view_data['edit'] = $o_user;
            if (!($o_user->oxuser__oxrights->value == 'malladmin' && !$blis_mall_admin)) {
                // generate selected right
                reset($a_user_rights);
                foreach ($a_user_rights as $val) {
                    if ($val->id == $o_user->oxuser__oxrights->value) {
                        $val->selected = 1;
                        break;
                    }
                }
            }
        }
        // passing country list
        $o_country_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Country_List::class);
        $o_country_list->load_active_countries($o_lang->get_object_tpl_language());
        $this->_a_view_data['countrylist'] = $o_country_list;
        $this->_a_view_data['rights'] = $a_user_rights;
        if ($this->_s_save_error) {
            $this->_a_view_data['sSaveError'] = $this->_s_save_error;
        }
        if (!$this->allow_admin_edit($sox_id)) {
            $this->_a_view_data['readonly'] = true;
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_user_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\User_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_user_main_ajax->get_columns();
            return 'popups/user_main';
        }
        return 'user_main';
    }
    /**
     * Saves main user parameters.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        if ($this->allow_admin_edit($sox_id)) {
            $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
            if (!isset($a_params['oxuser__oxactive'])) {
                $a_params['oxuser__oxactive'] = 0;
            }
            $o_user = ox_new(User::class);
            if ($sox_id != '-1') {
                $o_user->load($sox_id);
            } else {
                $a_params['oxuser__oxid'] = null;
            }
            if ($s_new_pass = Registry::get_request()->get_request_escaped_parameter('newPassword')) {
                $o_user->set_password($s_new_pass);
            }
            if (isset($a_params['oxuser__oxusername']) && $a_params['oxuser__oxusername'] !== $o_user->get_raw_field_data('oxusername') && $o_user->is_email_in_use($a_params['oxuser__oxusername'])) {
                $this->_s_save_error = 'EXCEPTION_USER_USEREXISTS';
                return;
            }
            $o_user->assign($a_params);
            if ($sox_id == '-1') {
                $this->on_user_creation($o_user);
            }
            $o_user->oxuser__oxbirthdate->fldtype = 'char';
            try {
                $o_user->save();
                $this->set_edit_object_id($o_user->get_id());
            } catch (Exception $o_excp) {
                $this->_s_save_error = $o_excp->get_message();
            }
        }
    }
    /**
     * If we need to add more rights / modify current rights by any conditions.
     *
     * @param array $userRights
     *
     * @return array
     */
    protected function calculate_additional_rights($user_rights)
    {
        return $user_rights;
    }
    /**
     * Additional actions on user creation.
     *
     * @param User $user
     *
     * @return User
     */
    protected function on_user_creation($user)
    {
        return $user;
    }
}