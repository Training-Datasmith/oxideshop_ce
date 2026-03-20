<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Email;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Password reminder page.
 * Collects toparticle, bargain article list. There is a form with entry
 * field to enter login name (usually email). After user enters required
 * information and submits "Request Password" button mail is sent to users email.
 * OXID eShop -> MY ACCOUNT -> "Forgot your password? - click here."
 */
class Forgot_Password_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/forgotpwd';
    /**
     * Send forgot E-Mail.
     *
     * @var string
     */
    protected $_s_forgot_email;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Update link expiration status
     *
     * @var bool
     *
     * @deprecated property will be removed in next major
     */
    protected $_bl_update_link_status;
    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = true;
    /**
     * Executes Email::sendForgotPwdEmail() to send "forgot password" email to user
     */
    public function forgot_password(): void
    {
        $this->_s_forgot_email = Registry::get_request()->get_request_escaped_parameter('lgn_usr');
        if ($this->_s_forgot_email) {
            $result = ox_new(Email::class)->send_forgot_pwd_email($this->_s_forgot_email);
            if ($result === -1) {
                Registry::get_utils_view()->add_error_to_display('MESSAGE_NOT_ABLE_TO_SEND_EMAIL');
                $this->_s_forgot_email = false;
            }
        }
    }
    /**
     * Checks if password is fine and updates old one with new
     * password. On success user is redirected to success page
     *
     * @return string
     */
    public function update_password()
    {
        $s_new_pass = Registry::get_request()->get_request_parameter('password_new');
        $s_conf_pass = Registry::get_request()->get_request_parameter('password_new_confirm');
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        /** @var \OxidEsales\Eshop\Core\InputValidator $oInputValidator */
        $o_input_validator = Registry::get_input_validator();
        if ($o_excp = $o_input_validator->check_password($o_user, $s_new_pass, $s_conf_pass, true)) {
            return Registry::get_utils_view()->add_error_to_display($o_excp->get_message(), false, true);
        }
        // passwords are fine - updating and loggin user in
        if ($o_user->load_user_by_update_id($this->get_update_id())) {
            // setting new pass ..
            $o_user->set_password($s_new_pass);
            // resetting update pass params
            $o_user->set_update_key(true);
            // saving ..
            $o_user->save();
            // forcing user login
            Registry::get_session()->set_variable('usr', $o_user->get_id());
            return 'forgotpwd?success=1';
        }
        // expired reminder
        $o_utils_view = Registry::get_utils_view();
        return $o_utils_view->add_error_to_display('ERROR_MESSAGE_PASSWORD_LINK_EXPIRED', false, true);
    }
    /**
     * If user password update was successfull - setting success status
     *
     * @return bool
     */
    public function update_success()
    {
        return (bool) Registry::get_request()->get_request_escaped_parameter('success');
    }
    /**
     * Notifies that password update form must be shown
     *
     * @return bool
     */
    public function show_update_screen()
    {
        return (bool) $this->get_update_id();
    }
    /**
     * Returns special id used for password update functionality
     *
     * @return string
     */
    public function get_update_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('uid');
    }
    /**
     * Returns password update link expiration status
     *
     * @return bool
     */
    public function is_expired_link()
    {
        if ($s_key = $this->get_update_id()) {
            return ox_new(\Oxid_Esales\Eshop\Application\Model\User::class)->is_expired_update_id($s_key);
        }
        return false;
    }
    /**
     * @return string
     */
    public function get_forgot_email()
    {
        return $this->_s_forgot_email;
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
        $i_base_language = Registry::get_lang()->get_base_language();
        $a_path['title'] = Registry::get_lang()->translate_string('FORGOT_PASSWORD', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Get password reminder page title
     *
     * @return string
     */
    public function get_title()
    {
        $s_title = 'FORGOT_PASSWORD';
        if ($this->show_update_screen()) {
            $s_title = 'NEW_PASSWORD';
        } elseif ($this->update_success()) {
            $s_title = 'CHANGE_PASSWORD';
        }
        return Registry::get_lang()->translate_string($s_title, Registry::get_lang()->get_base_language(), false);
    }
}