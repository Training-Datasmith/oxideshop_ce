<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Current user password change form.
 * When user is logged in he may change his Billing and Shipping
 * information (this is important for ordering purposes).
 * Information as email, password, greeting, name, company, address,
 * etc. Some fields must be entered. OXID eShop -> MY ACCOUNT
 * -> Update your billing and delivery settings.
 */
class Account_Password_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/password';
    /**
     * Whether the password had been changed.
     *
     * @var bool
     */
    protected $_bl_password_changed = false;
    /**
     * If user is not logged in - returns name of template AccountUserController::_sThisLoginTemplate, or if user is
     * already logged in additionally loads user delivery address info and forms country list. Returns name of template
     * AccountUserController::_sThisTemplate
     *
     * @return string $_sThisTemplate current template file name
     */
    public function render()
    {
        parent::render();
        // is logged in ?
        $o_user = $this->get_user();
        if (!$o_user) {
            return $this->_s_this_template = $this->_s_this_login_template;
        }
        return $this->_s_this_template;
    }
    /**
     * changes current user password
     */
    public function change_password()
    {
        if (!\Oxid_Esales\Eshop\Core\Registry::get_session()->check_session_challenge()) {
            return;
        }
        $o_user = $this->get_user();
        if (!$o_user) {
            return;
        }
        $s_old_pass = Registry::get_request()->get_request_parameter('password_old');
        $s_new_pass = Registry::get_request()->get_request_parameter('password_new');
        $s_conf_pass = Registry::get_request()->get_request_parameter('password_new_confirm');
        /** @var \OxidEsales\Eshop\Core\InputValidator $oInputValidator */
        $o_input_validator = \Oxid_Esales\Eshop\Core\Registry::get_input_validator();
        if ($o_excp = $o_input_validator->check_password($o_user, $s_new_pass, $s_conf_pass, true)) {
            return match ($o_excp->get_message()) {
                \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_EMPTYPASS'), \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_PASSWORD_TOO_SHORT') => \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_PASSWORD_TOO_SHORT', false, true),
                default => \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_PASSWORD_DO_NOT_MATCH', false, true),
            };
        }
        if (!$s_old_pass || !$o_user->is_same_password($s_old_pass)) {
            /** @var \OxidEsales\Eshop\Core\UtilsView $oUtilsView */
            $o_utils_view = \Oxid_Esales\Eshop\Core\Registry::get_utils_view();
            return $o_utils_view->add_error_to_display('ERROR_MESSAGE_CURRENT_PASSWORD_INVALID', false, true);
        }
        // testing passed - changing password
        $o_user->set_password($s_new_pass);
        if ($o_user->save()) {
            $this->_bl_password_changed = true;
            // deleting user autologin cookies.
            \Oxid_Esales\Eshop\Core\Registry::get_utils_server()->delete_user_cookie(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id());
        }
    }
    /**
     * Template variable getter. Returns true when password had been changed.
     *
     * @return bool
     */
    public function is_password_changed()
    {
        return $this->_bl_password_changed;
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $a_paths = [];
        $a_path = [];
        /** @var \OxidEsales\Eshop\Core\SeoEncoder $oSeoEncoder */
        $o_seo_encoder = \Oxid_Esales\Eshop\Core\Registry::get_seo_encoder();
        $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $i_base_language = $o_lang->get_base_language();
        $a_path['title'] = $o_lang->translate_string('MY_ACCOUNT', $i_base_language, false);
        $a_path['link'] = $o_seo_encoder->get_static_url($this->get_view_config()->get_self_link() . 'cl=account');
        $a_paths[] = $a_path;
        $a_path['title'] = $o_lang->translate_string('CHANGE_PASSWORD', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}