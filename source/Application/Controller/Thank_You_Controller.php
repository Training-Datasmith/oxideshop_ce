<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Thankyou page.
 * Arranges Thankyou page, sets ordering status, other parameters
 */
class Thank_You_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * User basket object
     *
     * @var object
     */
    protected $_o_basket;
    /**
     * List of customer also bought thies products
     *
     * @var object
     */
    protected $_a_last_products;
    /**
     * Currency conversion index value
     *
     * @var double
     */
    protected $_d_conv_index;
    /**
     * IPayment basket
     *
     * @var double
     */
    protected $_d_i_payment_basket;
    /**
     * IPayment account
     *
     * @var string
     */
    protected $_s_i_payment_account;
    /**
     * IPayment user name
     *
     * @var string
     */
    protected $_s_i_payment_user;
    /**
     * IPayment password
     *
     * @var string
     */
    protected $_s_i_payment_password;
    /**
     * Mail error
     *
     * @var string
     */
    protected $_s_mail_error;
    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = true;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/checkout/thankyou';
    /**
     * Executes parent::init(), loads basket from session
     * (thankyou::_oBasket = \OxidEsales\Eshop\Core\Session::getBasket()) then destroys
     * it (\OxidEsales\Eshop\Core\Session::delBasket()), unsets user session ID, if
     * this user didn't entered password while ordering.
     */
    public function init(): void
    {
        parent::init();
        // get basket we might need some information from it here
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        $o_basket = $session->get_basket();
        $o_basket->set_order_id(\Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('sess_challenge'));
        // copying basket object
        $this->_o_basket = clone $o_basket;
        // delete it from the session
        $o_basket->delete_basket();
        Registry::get_session()->delete_variable('sess_challenge');
        // if not in order-context, redirect to start
        $order = $this->get_order();
        if (!$order || !$order->get_field_data('oxordernr')) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->redirect(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_home_url() . '&cl=start');
        }
    }
    /**
     * First checks for basket - if no such object available -
     * redirects to start page. Otherwise - executes parent::render()
     * and returns name of template to render thankyou::_sThisTemplate.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        if (!$this->_o_basket || !$this->_o_basket->get_products_count()) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->redirect(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_home_url() . '&cl=start', true, 302);
        }
        parent::render();
        $o_user = $this->get_user();
        // removing also unregistered user info (#2580)
        if (!$o_user || !$o_user->oxuser__oxpassword->value) {
            Registry::get_session()->delete_variable('usr');
            Registry::get_session()->delete_variable('dynvalue');
        }
        // loading order sometimes needed in template
        if ($this->_o_basket->get_order_id()) {
            // owners stock reminder
            $o_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
            $o_email->send_stock_reminder($this->_o_basket->get_contents());
        }
        // we must set active class as start
        $this->get_view_config()->set_view_config_param('cl', 'start');
        return $this->_s_this_template;
    }
    /**
     * Template variable getter. Returns active basket
     *
     * @return \OxidEsales\Eshop\Application\Model\Basket
     */
    public function get_basket()
    {
        return $this->_o_basket;
    }
    /**
     * Template variable getter. Returns list of customer also bought these products
     *
     * @return object
     */
    public function get_also_bought_these_products()
    {
        if ($this->_a_last_products === null) {
            $this->_a_last_products = false;
            // 5th order step
            $a_basket_contents = array_values($this->get_basket()->get_contents());
            if ($o_basket_item = $a_basket_contents[0]) {
                if ($o_product = $o_basket_item->get_article(false)) {
                    $this->_a_last_products = $o_product->get_customer_also_bought_this_products();
                }
            }
        }
        return $this->_a_last_products;
    }
    /**
     * Template variable getter. Returns currency conversion index value
     *
     * @return object
     */
    public function get_currency_cov_index()
    {
        if ($this->_d_conv_index === null) {
            // currency conversion index value
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $this->_d_conv_index = 1 / $o_cur->rate;
        }
        return $this->_d_conv_index;
    }
    /**
     * Template variable getter. Returns ipayment basket price
     *
     * @return double
     */
    public function get_i_payment_basket()
    {
        if ($this->_d_i_payment_basket === null) {
            $this->_d_i_payment_basket = $this->get_basket()->get_price()->get_brutto_price() * 100;
        }
        return $this->_d_i_payment_basket;
    }
    /**
     * Template variable getter. Returns ipayment account
     *
     * @return string
     */
    public function get_i_payment_account()
    {
        if ($this->_s_i_payment_account === null) {
            $this->_s_i_payment_account = false;
            $this->_s_i_payment_account = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iShopID_iPayment_Account');
        }
        return $this->_s_i_payment_account;
    }
    /**
     * Template variable getter. Returns ipayment user name
     *
     * @return string
     */
    public function get_i_payment_user()
    {
        if ($this->_s_i_payment_user === null) {
            $this->_s_i_payment_user = false;
            $this->_s_i_payment_user = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iShopID_iPayment_User');
        }
        return $this->_s_i_payment_user;
    }
    /**
     * Template variable getter. Returns ipayment password
     *
     * @return string
     */
    public function get_i_payment_password()
    {
        if ($this->_s_i_payment_password === null) {
            $this->_s_i_payment_password = false;
            $this->_s_i_payment_password = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iShopID_iPayment_Passwort');
        }
        return $this->_s_i_payment_password;
    }
    /**
     * Template variable getter. Returns mail error
     *
     * @return string
     */
    public function get_mail_error()
    {
        if ($this->_s_mail_error === null) {
            $this->_s_mail_error = false;
            $this->_s_mail_error = Registry::get_request()->get_request_escaped_parameter('mailerror');
        }
        return $this->_s_mail_error;
    }
    /**
     * Template variable getter. Returns order
     *
     * @return \OxidEsales\Eshop\Application\Model\Order
     */
    public function get_order()
    {
        if (!isset($this->_o_order)) {
            $this->_o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            // loading order sometimes needed in template
            if ($s_order_id = $this->get_basket()->get_order_id()) {
                $this->_o_order->load($s_order_id);
            }
        }
        return $this->_o_order;
    }
    /**
     * Template variable getter. Returns country ISO 3
     *
     * @return string
     */
    public function get_country_iso3()
    {
        $o_order = $this->get_order();
        if ($o_order) {
            $o_country = ox_new(\Oxid_Esales\Eshop\Application\Model\Country::class);
            $o_country->load($o_order->oxorder__oxbillcountryid->value);
            return $o_country->oxcountry__oxisoalpha3->value;
        }
    }
    /**
     * Returns name of a view class, which will be active for an action
     * (given a generic fnc, e.g. logout)
     *
     * @return string
     */
    public function get_action_class_name()
    {
        return 'start';
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
        $i_lang = Registry::get_lang()->get_base_language();
        $a_path['title'] = Registry::get_lang()->translate_string('ORDER_COMPLETED', $i_lang, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}