<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * User details.
 * Collects and arranges user object data (information, like shipping address, etc.).
 */
class User_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template.
     *
     * @var string
     */
    protected $_s_this_template = 'page/checkout/user';
    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_bl_is_order_step = true;
    /**
     * Revers of option blOrderDisWithoutReg
     *
     * @var array
     */
    protected $_bl_show_no_reg_opt;
    /**
     * Selected Address
     *
     * @var object
     */
    protected $_s_selected_address;
    /**
     * Login option
     *
     * @var integer
     */
    protected $_i_option;
    /**
     * Country list
     *
     * @var object
     */
    protected $_o_country_list;
    /**
     * Order remark
     *
     * @var string
     */
    protected $_s_order_remark;
    /**
     * Wishlist user id
     *
     * @var string
     */
    protected $_s_wish_id;
    /**
     * Loads customer basket object form session (\OxidEsales\Eshop\Core\Session::getBasket()),
     * passes action article/basket/country list to template engine. If
     * available - loads user delivery address data (oxAddress). Returns
     * name template file to render user::_sThisTemplate.
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        $config = Registry::get_config();
        if ($this->get_is_order_step()) {
            $session = Registry::get_session();
            if ($config->get_config_param('blPsBasketReservationEnabled')) {
                $session->get_basket_reservations()->renew_expiration();
            }
            $basket = $session->get_basket();
            $is_ps_basket_reservations_enabled = $config->get_config_param('blPsBasketReservationEnabled');
            if ($this->_bl_is_order_step && $is_ps_basket_reservations_enabled && (!$basket || $basket && !$basket->get_products_count())) {
                Registry::get_utils()->redirect($config->get_shop_home_url() . 'cl=basket', true, 302);
            }
        }
        $this->_a_view_data['deladr'] = Registry::get_request()->get_request_escaped_parameter('deladr');
        parent::render();
        return $this->_s_this_template;
    }
    /**
     * Template variable getter. Returns reverse option blOrderDisWithoutReg
     *
     * @return bool
     */
    public function get_show_no_reg_option()
    {
        if ($this->_bl_show_no_reg_opt === null) {
            $this->_bl_show_no_reg_opt = !Registry::get_config()->get_config_param('blOrderDisWithoutReg');
        }
        return $this->_bl_show_no_reg_opt;
    }
    /**
     * Template variable getter. Returns user login option
     *
     * @return integer
     */
    public function get_login_option()
    {
        if ($this->_i_option === null) {
            // passing user chosen option value to display correct content
            $option = Registry::get_request()->get_request_escaped_parameter('option');
            // if user chosen "Option 2"" - we should show user details only if he is authorized
            if ($option == 2 && !$this->get_user()) {
                $option = 0;
            }
            $this->_i_option = $option;
        }
        return $this->_i_option;
    }
    /**
     * Template variable getter. Returns order remark
     *
     * @return string
     */
    public function get_order_remark()
    {
        $config = Registry::get_config();
        if ($this->_s_order_remark === null) {
            // if already connected, we can use the session
            if ($this->get_user()) {
                $order_remark = Registry::get_session()->get_variable('ordrem');
            } else {
                // not connected so nowhere to save, we're gonna use what we get from post
                $order_remark = Registry::get_request()->get_request_parameter('order_remark');
            }
            $this->_s_order_remark = $order_remark ? $config->check_param_special_chars($order_remark) : false;
        }
        return $this->_s_order_remark;
    }
    /**
     * Template variable getter. Returns if user subscribed for newsletter
     *
     * @return bool
     */
    public function is_news_subscribed()
    {
        if ($this->_bl_news_subscribed === null) {
            if (($is_subscribed_to_news = Registry::get_request()->get_request_escaped_parameter('blnewssubscribed')) === null) {
                $is_subscribed_to_news = false;
            }
            if ($user = $this->get_user()) {
                $is_subscribed_to_news = $user->get_news_subscription()->get_opt_in_status();
            }
            $this->_bl_news_subscribed = $is_subscribed_to_news;
        }
        if (is_null($this->_bl_news_subscribed)) {
            $this->_bl_news_subscribed = false;
        }
        return $this->_bl_news_subscribed;
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
     * Return true if user wants to change his billing address
     *
     * @return bool
     */
    public function modify_bill_address()
    {
        return Registry::get_request()->get_request_escaped_parameter('blnewssubscribed');
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $paths = [];
        $path = [];
        $base_language_id = Registry::get_lang()->get_base_language();
        $path['title'] = Registry::get_lang()->translate_string('ADDRESS', $base_language_id, false);
        $path['link'] = $this->get_link();
        $paths[] = $path;
        return $paths;
    }
    /**
     * Returns warning message if user want to buy downloadable product without registration.
     *
     * @return bool
     */
    public function is_downloadable_product_warning()
    {
        $session = Registry::get_session();
        $basket = $session->get_basket();
        if (!$basket) {
            return false;
        }
        if (!Registry::get_config()->get_config_param('blEnableDownloads')) {
            return false;
        }
        if ($basket->has_downloadable_products()) {
            return true;
        }
        return false;
    }
}