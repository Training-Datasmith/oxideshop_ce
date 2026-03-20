<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Current user Data Maintenance form.
 * When user is logged in he may change his Billing and Shipping
 * information (this is important for ordering purposes).
 * Information as email, password, greeting, name, company, address
 * etc. Some fields must be entered. OXID eShop -> MY ACCOUNT
 * -> Update your billing and delivery settings.
 */
class Account_User_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/user';
    /**
     * If user is not logged in - returns name of template
     * \OxidEsales\Eshop\Application\Controller\AccountUserController::_sThisLoginTemplate, or if user is already
     * logged in additionally loads user delivery address info and forms country list. Returns name of template
     * \OxidEsales\Eshop\Application\Controller\AccountUserController::_sThisTemplate
     *
     * @return  string  $_sThisTemplate current template file name
     */
    public function render()
    {
        parent::render();
        // is logged in ?
        if (!$this->get_user()) {
            return $this->_s_this_template = $this->_s_this_login_template;
        }
        $this->_a_view_data['deladr'] = Registry::get_request()->get_request_escaped_parameter('deladr');
        return $this->_s_this_template;
    }
    /**
     * Template variable getter. Checks to show or not shipping address entry form
     *
     * @return bool
     */
    public function show_ship_address()
    {
        return Registry::get_session()->get_variable('blshowshipaddress');
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
        $s_self_link = $this->get_view_config()->get_self_link();
        $a_path['title'] = Registry::get_lang()->translate_string('MY_ACCOUNT', $i_base_language, false);
        $a_path['link'] = Registry::get_seo_encoder()->get_static_url($s_self_link . 'cl=account');
        $a_paths[] = $a_path;
        $a_path['title'] = Registry::get_lang()->translate_string('BILLING_SHIPPING_SETTINGS', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}