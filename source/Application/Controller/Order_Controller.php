<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Application\Model\Basket_Content_Mark_Generator;
use Oxid_Esales\Eshop\Application\Model\Order;
use Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception;
use Oxid_Esales\Eshop\Core\Exception\No_Article_Exception;
use Oxid_Esales\Eshop\Core\Exception\Out_Of_Stock_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Utils_Object;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Psr\Log\Logger_Interface;
/**
 * Order manager. Arranges user ordering data, checks/validates
 * it, on success stores ordering data to DB.
 */
class Order_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Payment object
     *
     * @var object
     */
    protected $_o_payment;
    /**
     * Active basket
     *
     * @var \OxidEsales\Eshop\Application\Model\Basket
     */
    protected $_o_basket;
    /**
     * Order user remark
     *
     * @var string
     */
    protected $_s_order_remark;
    /**
     * Basket articlelist
     *
     * @var object
     */
    protected $_o_basket_art_list;
    /**
     * Remote Address
     *
     * @var string
     */
    protected $_s_remote_address;
    /**
     * Delivery address
     *
     * @var \OxidEsales\Eshop\Application\Model\Address
     */
    protected $_o_del_address;
    /**
     * Shipping set
     *
     * @var object
     */
    protected $_o_ship_set;
    /**
     * Config option "blConfirmAGB"
     *
     * @var bool
     */
    protected $_bl_confirm_agb;
    /**
     * Config option "blShowOrderButtonOnTop"
     *
     * @var bool
     */
    protected $_bl_show_order_button_on_top;
    /**
     * Boolean of option "blConfirmAGB" error
     *
     * @var bool
     */
    protected $_bl_confirm_agb_error;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/checkout/order';
    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_bl_is_order_step = true;
    /**
     * Count of wrapping + cards options
     */
    protected $_i_wrap_cnt;
    /**
     * Loads basket \OxidEsales\Eshop\Core\Session::getBasket(), sets $this->oBasket->blCalcNeeded = true to
     * recalculate, sets back basket to session \OxidEsales\Eshop\Core\Session::setBasket(), executes
     * parent::init().
     */
    public function init(): void
    {
        // disabling performance control variable
        Registry::get_config()->set_config_param('bl_perfCalcVatOnlyForBasketOrder', false);
        // recalc basket cause of payment stuff
        if ($o_basket = $this->get_basket()) {
            $o_basket->on_update();
        }
        parent::init();
    }
    /**
     * @inheritdoc
     */
    public function render()
    {
        if ($this->get_is_order_step()) {
            $basket = $this->get_basket();
            if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
                Registry::get_session()->get_basket_reservations()->renew_expiration();
                if (!$basket || $basket && !$basket->get_products_count()) {
                    Registry::get_utils()->redirect(Registry::get_config()->get_shop_home_url() . 'cl=basket', true, 302);
                }
            }
            $user = $this->get_user();
            if (!$user && ($basket && $basket->get_products_count() > 0)) {
                Registry::get_utils()->redirect(Registry::get_config()->get_shop_home_url() . 'cl=basket', false, 302);
            } elseif (!$basket || !$user || $basket && !$basket->get_products_count()) {
                Registry::get_utils()->redirect(Registry::get_config()->get_shop_home_url(), false, 302);
            }
            if (!$this->get_payment()) {
                Registry::get_utils()->redirect(Registry::get_config()->get_shop_current_url() . '&cl=payment', true, 302);
            }
        }
        try {
            $this->_a_view_data['basketSummaryHash'] = $this->get_basket_summary_hash();
        } catch (No_Article_Exception) {
            Registry::get_utils()->redirect(Registry::get_config()->get_shop_home_url() . 'cl=basket', false, 302);
        }
        parent::render();
        if (!Registry::get_session()->get_variable('sess_challenge')) {
            Registry::get_session()->set_variable('sess_challenge', $this->get_utils_object_instance()->generate_uid());
        }
        return $this->_s_this_template;
    }
    /**
     * Checks for order rules confirmation ("ord_agb", "ord_custinfo" form values)(if no
     * rules agreed - returns to order view), loads basket contents (plus applied
     * price/amount discount if available - checks for stock, checks user data (if no
     * data is set - returns to user login page). Stores order info to database
     * (\OxidEsales\Eshop\Application\Model\Order::finalizeOrder()). According to sum for items automatically assigns
     * user to special user group ( \OxidEsales\Eshop\Application\Model\User::onOrderExecute(); if this option is not
     * disabled in admin). Finally you will be redirected to next page (order::_getNextStep()).
     *
     * @return string|null
     */
    public function execute()
    {
        $session = Registry::get_session();
        if (!$session->check_session_challenge()) {
            return null;
        }
        try {
            if (!$this->validate_terms_and_conditions()) {
                $this->_bl_confirm_agb_error = 1;
                return null;
            }
            $user = $this->get_user();
            if (!$user) {
                return 'user';
            }
            $basket = $session->get_basket();
            $request_basket_summary_hash = Registry::get_request()->get_request_parameter('basketSummaryHash');
            if (!$request_basket_summary_hash) {
                $this->notify_if_basket_summary_validation_is_not_possible();
            } elseif ($request_basket_summary_hash !== $this->get_basket_summary_hash()) {
                $redirect = $basket->get_products_count() === 0 ? 'basket' : 'order';
                $this->add_basket_summary_validation_error($redirect);
                return $redirect;
            }
            if ($basket->get_products_count()) {
                $order = ox_new(Order::class);
                //finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
                $success = $order->finalize_order($basket, $user);
                // performing special actions after user finishes order (assignment to special user groups)
                $user->on_order_execute($basket, $success);
                // proceeding to next view
                return $this->get_next_step($success);
            }
        } catch (Out_Of_Stock_Exception $exception) {
            $exception->set_destination('basket');
            Registry::get_utils_view()->add_error_to_display($exception, false, true, 'basket');
        } catch (No_Article_Exception) {
            return 'basket';
        } catch (Article_Input_Exception $exception) {
            Registry::get_utils_view()->add_error_to_display($exception);
        }
    }
    /**
     * Template variable getter. Returns payment object
     *
     * @return object
     */
    public function get_payment()
    {
        if ($this->_o_payment === null) {
            $this->_o_payment = false;
            $o_basket = $this->get_basket();
            $o_user = $this->get_user();
            // payment is set ?
            $s_paymentid = $o_basket->get_payment_id();
            $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
            if ($s_paymentid && $o_payment->load($s_paymentid) && $o_payment->is_valid_payment(Registry::get_session()->get_variable('dynvalue'), Registry::get_config()->get_shop_id(), $o_user, $o_basket->get_price_for_payment(), Registry::get_session()->get_variable('sShipSet'))) {
                $this->_o_payment = $o_payment;
            }
        }
        return $this->_o_payment;
    }
    /**
     * Template variable getter. Returns active basket
     *
     * @return \OxidEsales\Eshop\Application\Model\Basket
     */
    public function get_basket()
    {
        if ($this->_o_basket === null) {
            $this->_o_basket = false;
            $session = Registry::get_session();
            if ($o_basket = $session->get_basket()) {
                $this->_o_basket = $o_basket;
            }
        }
        return $this->_o_basket;
    }
    /**
     * Template variable getter. Returns execution function name
     *
     * @return string
     */
    public function get_execute_fnc()
    {
        return 'execute';
    }
    /**
     * Template variable getter. Returns user remark
     *
     * @return string
     */
    public function get_order_remark()
    {
        if ($this->_s_order_remark === null) {
            $this->_s_order_remark = false;
            if ($s_remark = Registry::get_session()->get_variable('ordrem')) {
                $this->_s_order_remark = Registry::get_config()->check_param_special_chars($s_remark);
            }
        }
        return $this->_s_order_remark;
    }
    /**
     * Template variable getter. Returns basket article list
     *
     * @return object
     */
    public function get_basket_articles()
    {
        if ($this->_o_basket_art_list === null) {
            $this->_o_basket_art_list = false;
            if ($o_basket = $this->get_basket()) {
                $this->_o_basket_art_list = $o_basket->get_basket_articles();
            }
        }
        return $this->_o_basket_art_list;
    }
    /**
     * Template variable getter. Returns delivery address
     *
     * @return \OxidEsales\Eshop\Application\Model\Address|null
     */
    public function get_del_address()
    {
        if ($this->_o_del_address === null) {
            $this->_o_del_address = false;
            $o_order = ox_new(Order::class);
            $this->_o_del_address = $o_order->get_del_address_info();
        }
        return $this->_o_del_address;
    }
    /**
     * Template variable getter. Returns shipping set
     *
     * @return object
     */
    public function get_ship_set()
    {
        if ($this->_o_ship_set === null) {
            $this->_o_ship_set = false;
            if ($o_basket = $this->get_basket()) {
                $o_ship_set = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set::class);
                if ($o_ship_set->load($o_basket->get_shipping_id())) {
                    $this->_o_ship_set = $o_ship_set;
                }
            }
        }
        return $this->_o_ship_set;
    }
    /**
     * Template variable getter. Returns if option "blConfirmAGB" is on
     *
     * @return bool
     */
    public function is_confirm_agb_active()
    {
        if ($this->_bl_confirm_agb === null) {
            $this->_bl_confirm_agb = false;
            $this->_bl_confirm_agb = Registry::get_config()->get_config_param('blConfirmAGB');
        }
        return $this->_bl_confirm_agb;
    }
    /**
     * Template variable getter. Returns if option "blConfirmAGB" was not set
     *
     * @return bool
     */
    public function is_confirm_agb_error()
    {
        return $this->_bl_confirm_agb_error;
    }
    /**
     * Template variable getter. Returns if option "blShowOrderButtonOnTop" is on
     *
     * @return bool
     */
    public function show_order_button_on_top()
    {
        if ($this->_bl_show_order_button_on_top === null) {
            $this->_bl_show_order_button_on_top = false;
            $this->_bl_show_order_button_on_top = Registry::get_config()->get_config_param('blShowOrderButtonOnTop');
        }
        return $this->_bl_show_order_button_on_top;
    }
    /**
     * Returns wrapping options availability state (TRUE/FALSE)
     *
     * @return bool
     */
    public function is_wrapping()
    {
        if (!$this->get_view_config()->get_show_gift_wrapping()) {
            return false;
        }
        if ($this->_i_wrap_cnt === null) {
            $this->_i_wrap_cnt = 0;
            $o_wrap = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
            $this->_i_wrap_cnt += $o_wrap->get_wrapping_count('WRAP');
            $this->_i_wrap_cnt += $o_wrap->get_wrapping_count('CARD');
        }
        return (bool) $this->_i_wrap_cnt;
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
        $a_path['title'] = Registry::get_lang()->translate_string('ORDER', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Return error number
     *
     * @return int
     */
    public function get_address_error()
    {
        return Registry::get_request()->get_request_escaped_parameter('iAddressError');
    }
    /**
     * Return users setted delivery address md5
     *
     * @return string
     */
    public function get_delivery_address_md5()
    {
        // bill address
        $o_user = $this->get_user();
        $s_del_address = $o_user->get_encoded_delivery_address();
        // delivery address
        if (Registry::get_session()->get_variable('deladrid')) {
            $o_del_adress = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
            $o_del_adress->load(Registry::get_session()->get_variable('deladrid'));
            $s_del_address .= $o_del_adress->get_encoded_delivery_address();
        }
        return $s_del_address;
    }
    /**
     * Method returns object with explanation marks for articles in basket.
     *
     * @return BasketContentMarkGenerator
     */
    public function get_basket_content_mark_generator()
    {
        return ox_new(Basket_Content_Mark_Generator::class, $this->get_basket());
    }
    /**
     * Returns next order step. If ordering was sucessfull - returns string "thankyou" (possible
     * additional parameters), otherwise - returns string "payment" with additional
     * error parameters.
     *
     * @param integer $iSuccess status code
     *
     * @return  string  $sNextStep  partial parameter url for next step
     */
    protected function get_next_step($i_success)
    {
        $s_next_step = 'thankyou';
        //little trick with switch for multiple cases
        switch (true) {
            case $i_success === Order::ORDER_STATE_MAILINGERROR:
                $s_next_step = 'thankyou?mailerror=1';
                break;
            case $i_success === Order::ORDER_STATE_INVALIDDELADDRESSCHANGED:
                $s_next_step = 'order?iAddressError=1';
                break;
            case $i_success === Order::ORDER_STATE_BELOWMINPRICE:
                $s_next_step = 'order';
                break;
            case $i_success === Order::ORDER_STATE_VOUCHERERROR:
                $s_next_step = 'basket';
                break;
            case $i_success === Order::ORDER_STATE_PAYMENTERROR:
                // no authentication, kick back to payment methods
                Registry::get_session()->set_variable('payerror', 2);
                $s_next_step = 'payment?payerror=2';
                break;
            case $i_success === Order::ORDER_STATE_ORDEREXISTS:
            default:
                break;
            // reload blocker activ
            case is_numeric($i_success) && $i_success > 3:
                Registry::get_session()->set_variable('payerror', $i_success);
                $s_next_step = 'payment?payerror=' . $i_success;
                break;
            case !is_numeric($i_success) && $i_success:
                //instead of error code getting error text and setting payerror to -1
                Registry::get_session()->set_variable('payerror', -1);
                $i_success = urlencode($i_success);
                $s_next_step = 'payment?payerror=-1&payerrortext=' . $i_success;
                break;
        }
        return $s_next_step;
    }
    /**
     * Validates whether necessary terms and conditions checkboxes were checked.
     *
     * @return bool
     */
    protected function validate_terms_and_conditions()
    {
        $bl_valid = true;
        $o_config = Registry::get_config();
        if ($o_config->get_config_param('blConfirmAGB') && !Registry::get_request()->get_request_escaped_parameter('ord_agb')) {
            $bl_valid = false;
        }
        if ($o_config->get_config_param('blEnableIntangibleProdAgreement')) {
            $o_basket = $this->get_basket();
            $bl_downloadable_products_agreement = Registry::get_request()->get_request_escaped_parameter('oxdownloadableproductsagreement');
            if ($bl_valid && $o_basket->has_articles_with_downloadable_agreement() && !$bl_downloadable_products_agreement) {
                $bl_valid = false;
            }
            $bl_service_products_agreement = Registry::get_request()->get_request_escaped_parameter('oxserviceproductsagreement');
            if ($bl_valid && $o_basket->has_articles_with_intangible_agreement() && !$bl_service_products_agreement) {
                $bl_valid = false;
            }
        }
        return $bl_valid;
    }
    /**
     * @return UtilsObject
     */
    protected function get_utils_object_instance()
    {
        return Registry::get_utils_object();
    }
    private function get_basket_summary_hash(): string
    {
        return md5(json_encode($this->get_basket()->get_basket_summary()));
    }
    private function notify_if_basket_summary_validation_is_not_possible(): void
    {
        Container_Facade::get(Logger_Interface::class)->warning('Pricing and payments verification can not be performed, ' . 'the basketSummaryHash parameter was not sent with request data.');
    }
    private function add_basket_summary_validation_error(string $controller): void
    {
        Registry::get_utils_view()->add_error_to_display('BASKET_ITEMS_CHANGED_ERROR', false, true, '', $controller);
    }
}