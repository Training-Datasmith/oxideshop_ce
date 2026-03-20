<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Current user newsletter manager.
 * When user is logged in in this manager window he can modify
 * his newletter subscription status - simply register or
 * unregister from newsletter. OXID eShop -> MY ACCOUNT -> Newsletter.
 */
class Account_Newsletter_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/newsletter';
    /**
     * Whether the newsletter option had been changed give some affirmation.
     *
     * @var integer
     */
    protected $_i_subscription_status = 0;
    /**
     * If user is not logged in - returns name of template AccountNewsletterController::_sThisLoginTemplate, or if user
     * is already logged in - returns name of template AccountNewsletterController::_sThisTemplate
     *
     * @return string
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
     * Template variable getter. Returns 0 when newsletter had been changed.
     *
     * @return int
     */
    public function is_newsletter()
    {
        $o_user = $this->get_user();
        if (!$o_user) {
            return false;
        }
        return $o_user->get_news_subscription()->get_opt_in_status();
    }
    /**
     * Removes or adds user to newsletter group according to
     * current subscription status. Returns true on success.
     *
     * @return bool
     */
    public function subscribe()
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return false;
        }
        // is logged in ?
        $o_user = $this->get_user();
        if (!$o_user) {
            return false;
        }
        $i_status = Registry::get_request()->get_request_escaped_parameter('status');
        if ($o_user->set_news_subscription($i_status, Registry::get_config()->get_config_param('blOrderOptInEmail'))) {
            $this->_i_subscription_status = $i_status == 0 && $i_status !== null ? -1 : 1;
        }
    }
    /**
     * Template variable getter. Returns 1 when newsletter had been changed to "yes"
     * else return -1 if had been changed to "no".
     *
     * @return integer
     */
    public function get_subscription_status()
    {
        return $this->_i_subscription_status;
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
        $o_utils = Registry::get_utils_url();
        $i_base_language = Registry::get_lang()->get_base_language();
        $s_self_link = $this->get_view_config()->get_self_link();
        $a_path['title'] = Registry::get_lang()->translate_string('MY_ACCOUNT', $i_base_language, false);
        $a_path['link'] = Registry::get_seo_encoder()->get_static_url($s_self_link . 'cl=account');
        $a_paths[] = $a_path;
        $a_path['title'] = Registry::get_lang()->translate_string('NEWSLETTER_SETTINGS', $i_base_language, false);
        $a_path['link'] = $o_utils->clean_url($this->get_link(), ['fnc']);
        $a_paths[] = $a_path;
        return $a_paths;
    }
}