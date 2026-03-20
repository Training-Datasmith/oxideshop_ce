<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * User registration window.
 * Collects and arranges user object data (information, like shipping address, etc.).
 */
class Register_Controller extends \Oxid_Esales\Eshop\Application\Controller\User_Controller
{
    /**
     * Current class template.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/register';
    /**
     * Successful registration confirmation template
     *
     * @var string
     */
    protected $_s_success_template = 'page/account/register_success';
    /**
     * Successful Confirmation state template name
     *
     * @var string
     */
    protected $_s_confirm_template = 'page/account/register_confirm';
    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_bl_is_order_step = false;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Executes parent::render(), passes error code to template engine,
     * returns name of template to render register::_sThisTemplate.
     *
     * @return string   current template file name
     */
    public function render()
    {
        parent::render();
        // checking registration status
        if ($this->is_enabled_private_sales() && $this->is_confirmed()) {
            $s_template = $this->_s_confirm_template;
        } elseif ($this->get_registration_status()) {
            $s_template = $this->_s_success_template;
        } else {
            $s_template = $this->_s_this_template;
        }
        return $s_template;
    }
    /**
     * Returns registration error code (if it was set)
     *
     * @return int|null
     */
    public function get_registration_error()
    {
        return Registry::get_request()->get_request_escaped_parameter('newslettererror');
    }
    /**
     * Return registration status (if it was set)
     *
     * @return int|null
     */
    public function get_registration_status()
    {
        return Registry::get_request()->get_request_escaped_parameter('success');
    }
    /**
     * Check if field is required.
     *
     * @param string $sField required field to check
     *
     * @return bool
     */
    public function is_field_required($s_field)
    {
        return isset($this->get_must_fill_fields()[$s_field]);
    }
    /**
     * Registration confirmation functionality. If registration
     * succeded - redirects to success page, if not - returns
     * exception informing about expired confirmation link
     *
     * @return mixed
     */
    public function confirm_registration()
    {
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        if ($o_user->load_user_by_update_id($this->get_update_id())) {
            // resetting update key parameter
            $o_user->set_update_key(true);
            // saving ..
            $o_user->oxuser__oxactive = new \Oxid_Esales\Eshop\Core\Field(1);
            $o_user->save();
            // forcing user login
            Registry::get_session()->set_variable('usr', $o_user->get_id());
            // redirecting to confirmation page
            return 'register?confirmstate=1';
        }
        // confirmation failed
        Registry::get_utils_view()->add_error_to_display('REGISTER_ERRLINKEXPIRED', false, true);
        // redirecting to confirmation page
        return 'account';
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
     * Returns confirmation state: "1" - success, "-1" - error
     *
     * @return int
     */
    public function is_confirmed()
    {
        return (bool) Registry::get_request()->get_request_escaped_parameter('confirmstate');
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
        $a_path['title'] = Registry::get_lang()->translate_string('REGISTER', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}