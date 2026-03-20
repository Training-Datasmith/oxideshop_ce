<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Application\Model\Delivery_Set_List;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Payment manager.
 * Customer payment manager class. Performs payment validation function, etc.
 */
class Payment_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Paymentlist
     *
     * @var object
     */
    protected $_o_payment_list;
    /**
     * Paymentlist count
     *
     * @var integer
     */
    protected $_i_payment_cnt;
    /**
     * All delivery sets
     *
     * @var array
     */
    protected $_a_all_sets;
    /**
     * Delivery sets count
     *
     * @var integer
     */
    protected $_i_all_sets_cnt;
    /**
     * Payment object 'oxempty'
     *
     * @var object
     */
    protected $_o_empty_payment;
    /**
     * Payment error
     *
     * @var string
     */
    protected $_s_payment_error;
    /**
     * Payment error text
     *
     * @var string
     */
    protected $_s_payment_error_text;
    /**
     * Dyn values
     *
     * @var array
     */
    protected $_a_dyn_value;
    /**
     * Checked payment id
     *
     * @var string
     */
    protected $_s_checked_id;
    /**
     * Selected payment id in db
     *
     * @var string
     */
    protected $_s_checked_payment_id;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/checkout/payment';
    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_bl_is_order_step = true;
    /**
     * TS protection product array
     *
     * @var array
     */
    protected $_a_ts_products;
    /**
     * Executes parent method parent::init().
     */
    public function init(): void
    {
        parent::init();
    }
    /**
     * Executes parent::render(), checks if this connection secure
     * (if not - redirects to secure payment page), loads user object
     * (if user object loading was not successfull - redirects to start
     * page), loads user delivery/shipping information. According
     * to configuration in admin, user profile data loads delivery sets,
     * and possible payment methods. Returns name of template to render
     * payment::_sThisTemplate.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($my_config->get_config_param('blPsBasketReservationEnabled')) {
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            $session->get_basket_reservations()->renew_expiration();
        }
        parent::render();
        //if it happens that you are not in SSL
        //then forcing to HTTPS
        //but first checking maybe there were redirection already to prevent infinite redirections
        //due to possible buggy ssl detection on server
        $bl_already_redirected = Registry::get_request()->get_request_escaped_parameter('sslredirect') == 'forced';
        if ($this->get_is_order_step()) {
            //additional check if we really really have a user now
            //and the basket is not empty
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            $o_basket = $session->get_basket();
            $bl_ps_basket_reservation_enabled = $my_config->get_config_param('blPsBasketReservationEnabled');
            if ($bl_ps_basket_reservation_enabled && (!$o_basket || $o_basket && !$o_basket->get_products_count())) {
                Registry::get_utils()->redirect($my_config->get_shop_home_url() . 'cl=basket', true, 302);
            }
            $o_user = $this->get_user();
            if (!$o_user && ($o_basket && $o_basket->get_products_count() > 0)) {
                Registry::get_utils()->redirect($my_config->get_shop_home_url() . 'cl=basket', false, 302);
            } elseif (!$o_basket || !$o_user || $o_basket && !$o_basket->get_products_count()) {
                Registry::get_utils()->redirect($my_config->get_shop_home_url() . 'cl=start', false, 302);
            }
        }
        $s_fnc_parameter = Registry::get_request()->get_request_escaped_parameter('fnc');
        if ($my_config->get_current_shop_url() != $my_config->get_shop_url() && !$bl_already_redirected && !$s_fnc_parameter) {
            $s_pay_error_parameter = Registry::get_request()->get_request_escaped_parameter('payerror');
            $s_pay_error_text_parameter = Registry::get_request()->get_request_escaped_parameter('payerrortext');
            $shop_secure_home_url = $my_config->get_shop_secure_home_url();
            $s_pay_error = $s_pay_error_parameter ? 'payerror=' . $s_pay_error_parameter : '';
            $s_pay_error_text = $s_pay_error_text_parameter ? 'payerrortext=' . $s_pay_error_text_parameter : '';
            $s_redirect_url = $shop_secure_home_url . 'sslredirect=forced&cl=payment&' . $s_pay_error . '&' . $s_pay_error_text;
            Registry::get_utils()->redirect($s_redirect_url, true, 302);
        }
        if (!$this->get_all_sets_cnt()) {
            // no fitting shipping set found, setting default empty payment
            $this->set_default_empty_payment();
            Registry::get_session()->set_variable('sShipSet', null);
        }
        $this->unset_payment_errors();
        return $this->_s_this_template;
    }
    /**
     * Set default empty payment. If config param 'blOtherCountryOrder' is on,
     * tries to set 'oxempty' payment to aViewData['oxemptypayment'].
     * On error sets aViewData['payerror'] to -2
     */
    protected function set_default_empty_payment()
    {
        // no shipping method there !!
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blOtherCountryOrder')) {
            $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
            if ($o_payment->load('oxempty')) {
                $this->_o_empty_payment = $o_payment;
            } else {
                // some error with setup ??
                $this->_s_payment_error = -2;
            }
        } else {
            $this->_s_payment_error = -2;
        }
    }
    /**
     * Unsets payment errors from session
     */
    protected function unset_payment_errors()
    {
        $i_pay_error = Registry::get_request()->get_request_escaped_parameter('payerror');
        $s_pay_error_text = Registry::get_request()->get_request_escaped_parameter('payerrortext');
        if (!($i_pay_error || $s_pay_error_text)) {
            $i_pay_error = Registry::get_session()->get_variable('payerror');
            $s_pay_error_text = Registry::get_session()->get_variable('payerrortext');
        }
        if ($i_pay_error) {
            Registry::get_session()->delete_variable('payerror');
            $this->_s_payment_error = $i_pay_error;
        }
        if ($s_pay_error_text) {
            Registry::get_session()->delete_variable('payerrortext');
            $this->_s_payment_error_text = $s_pay_error_text;
        }
    }
    /**
     * Changes shipping set to chosen one. Sets basket status to not up-to-date, which later
     * forces to recalculate it
     */
    public function changeshipping(): void
    {
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        $o_basket = $session->get_basket();
        $o_basket->set_shipping(null);
        $o_basket->on_update();
        $session->set_variable('sShipSet', Registry::get_request()->get_request_escaped_parameter('sShipSet'));
    }
    /**
     * Validates oxiddebitnote user payment data.
     * Returns null if problems on validating occured. If everything
     * is OK - returns "order" and redirects to payment confirmation
     * page.
     *
     * Session variables:
     * <b>paymentid</b>, <b>dynvalue</b>, <b>payerror</b>
     *
     * @return  mixed
     */
    public function validate_payment()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        //#1308C - check user. Function is executed before render(), and oUser is not set!
        // Set it manually for use in methods getPaymentList(), getShippingSetList()...
        $o_user = $this->get_user();
        if (!$o_user) {
            $session->set_variable('payerror', 2);
            return;
        }
        if (!$s_ship_set_id = Registry::get_request()->get_request_escaped_parameter('sShipSet')) {
            $s_ship_set_id = $session->get_variable('sShipSet');
        }
        if (!$s_payment_id = Registry::get_request()->get_request_escaped_parameter('paymentid')) {
            $s_payment_id = $session->get_variable('paymentid');
        }
        if (!$a_dynvalue = Registry::get_request()->get_request_escaped_parameter('dynvalue')) {
            $a_dynvalue = $session->get_variable('dynvalue');
        }
        // A. additional protection
        if (!$my_config->get_config_param('blOtherCountryOrder') && $s_payment_id == 'oxempty') {
            $s_payment_id = '';
        }
        //#1308C - check if we have paymentID, and it really exists
        if (!$s_payment_id) {
            $session->set_variable('payerror', 1);
            return;
        }
        $o_basket = $session->get_basket();
        $o_basket->set_payment(null);
        $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
        $o_payment->load($s_payment_id);
        // getting basket price for payment calculation
        $d_basket_price = $o_basket->get_price_for_payment();
        $bl_ok = $o_payment->is_valid_payment($a_dynvalue, $my_config->get_shop_id(), $o_user, $d_basket_price, $s_ship_set_id);
        if ($bl_ok) {
            $session->set_variable('paymentid', $s_payment_id);
            $session->set_variable('dynvalue', $a_dynvalue);
            $o_basket->set_shipping($s_ship_set_id);
            $session->delete_variable('_selected_paymentid');
            return 'order';
        }
        $session->set_variable('payerror', $o_payment->get_payment_error_number());
        //#1308C - delete paymentid from session, and save selected it just for view
        $session->delete_variable('paymentid');
        $session->set_variable('_selected_paymentid', $s_payment_id);
    }
    /**
     * Template variable getter. Returns paymentlist
     *
     * @return object
     */
    public function get_payment_list()
    {
        if ($this->_o_payment_list === null) {
            $this->_o_payment_list = false;
            $s_act_ship_set = Registry::get_request()->get_request_escaped_parameter('sShipSet');
            if (!$s_act_ship_set) {
                $s_act_ship_set = Registry::get_session()->get_variable('sShipSet');
            }
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            $o_basket = $session->get_basket();
            // load sets, active set, and active set payment list
            [$a_all_sets, $s_act_ship_set, $a_payment_list] = Registry::get(Delivery_Set_List::class)->get_delivery_set_data($s_act_ship_set, $this->get_user(), $o_basket);
            $o_basket->set_shipping($s_act_ship_set);
            // calculating payment expences for preview for each payment
            $this->set_values($a_payment_list, $o_basket);
            $this->_o_payment_list = $a_payment_list;
            $this->_a_all_sets = $a_all_sets;
        }
        return $this->_o_payment_list;
    }
    /**
     * Template variable getter. Returns all delivery sets
     *
     * @return array
     */
    public function get_all_sets()
    {
        if ($this->_a_all_sets === null) {
            $this->_a_all_sets = false;
            if ($this->get_payment_list()) {
                return $this->_a_all_sets;
            }
        }
        return $this->_a_all_sets;
    }
    /**
     * Template variable getter. Returns number of delivery sets
     *
     * @return integer
     */
    public function get_all_sets_cnt()
    {
        if ($this->_i_all_sets_cnt === null) {
            $this->_i_all_sets_cnt = 0;
            if ($this->get_payment_list()) {
                $this->_i_all_sets_cnt = count($this->_a_all_sets);
            }
        }
        return $this->_i_all_sets_cnt;
    }
    /**
     * Calculate payment cost for each payment. Sould be removed later
     *
     * @param array                                      $aPaymentList payments array
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket      basket object
     */
    protected function set_values(&$a_payment_list, $o_basket = null)
    {
        if (is_array($a_payment_list)) {
            foreach ($a_payment_list as $o_payment) {
                $o_payment->calculate($o_basket);
                $o_payment->a_dyn_values = $o_payment->get_dyn_values();
                if ($o_payment->oxpayments__oxchecked->value) {
                    $this->_s_checked_id = $o_payment->get_id();
                }
            }
        }
    }
    /**
     * Template variable getter. Returns payment object "oxempty"
     *
     * @return object
     */
    public function get_empty_payment()
    {
        return $this->_o_empty_payment;
    }
    /**
     * Template variable getter. Returns error of payments
     *
     * @return string
     */
    public function get_payment_error()
    {
        return $this->_s_payment_error;
    }
    /**
     * Template variable getter. Returns error text of payments
     *
     * @return string
     */
    public function get_payment_error_text()
    {
        return $this->_s_payment_error_text;
    }
    /**
     * Return if old style bank code is supported.
     *
     * @return bool
     */
    public function is_old_debit_validation_enabled()
    {
        return !\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blSkipDebitOldBankInfo');
    }
    /**
     * Template variable getter. Returns dyn values
     *
     * @return array
     */
    public function get_dyn_value()
    {
        if ($this->_a_dyn_value === null) {
            $this->_a_dyn_value = false;
            // flyspray#1217 (sarunas)
            if ($a_dyn_value = Registry::get_session()->get_variable('dynvalue')) {
                $this->_a_dyn_value = $a_dyn_value;
            } else {
                $this->_a_dyn_value = Registry::get_request()->get_request_escaped_parameter('dynvalue');
            }
            // #701A
            // assign debit note payment params to view data
            $a_payment_list = $this->get_payment_list();
            if (isset($a_payment_list['oxiddebitnote'])) {
                $this->assign_debit_note_params();
            }
        }
        return $this->_a_dyn_value;
    }
    /**
     * Assign debit note payment values to view data. Loads user debit note payment
     * if available and assigns payment data to $this->_aDynValue
     */
    protected function assign_debit_note_params()
    {
        // #701A
        $o_user_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
        //such info available ?
        if ($o_user_payment->get_payment_by_payment_type($this->get_user(), 'oxiddebitnote')) {
            $s_user_payment_field = 'oxuserpayments__oxvalue';
            $a_add_payment_data = Registry::get_utils()->assign_values_from_text($o_user_payment->{$s_user_payment_field}->value);
            //checking if some of values is allready set in session - leave it
            foreach ($a_add_payment_data as $o_data) {
                if (!isset($this->_a_dyn_value[$o_data->name]) || isset($this->_a_dyn_value[$o_data->name]) && !$this->_a_dyn_value[$o_data->name]) {
                    $this->_a_dyn_value[$o_data->name] = $o_data->value;
                }
            }
        }
    }
    /**
     * Get checked payment ID. Tries to get checked payment ID from session,
     * if fails, then tries to get payment ID from last order.
     *
     * @return string
     */
    public function get_checked_payment_id()
    {
        $s_checked_id = null;
        if ($this->_s_checked_payment_id === null) {
            if (!$s_payment_id = Registry::get_request()->get_request_escaped_parameter('paymentid')) {
                $s_payment_id = Registry::get_session()->get_variable('paymentid');
            }
            if ($s_payment_id) {
                $s_checked_id = $s_payment_id;
            } elseif ($s_selected_payment_id = Registry::get_session()->get_variable('_selected_paymentid')) {
                $s_checked_id = $s_selected_payment_id;
            } else if ($o_user = $this->get_user()) {
                $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
                if ($s_last_payment_id = $o_order->get_last_user_payment_type($o_user->get_id())) {
                    $s_checked_id = $s_last_payment_id;
                }
            }
            // #M253 set to selected payment in db
            if (!$s_checked_id && $this->_s_checked_id) {
                $s_checked_id = $this->_s_checked_id;
            }
            // #646
            $o_payment_list = $this->get_payment_list();
            if (isset($o_payment_list) && $o_payment_list && isset($s_checked_id) && !isset($o_payment_list[$s_checked_id])) {
                $s_checked_id = array_key_last($o_payment_list);
            }
            $this->_s_checked_payment_id = $s_checked_id;
        }
        return $this->_s_checked_payment_id;
    }
    /**
     * Template variable getter. Returns payment list count
     *
     * @return integer
     */
    public function get_payment_cnt()
    {
        if ($this->_i_payment_cnt === null) {
            $this->_i_payment_cnt = false;
            if ($o_payment_list = $this->get_payment_list()) {
                $this->_i_payment_cnt = count($o_payment_list);
            }
        }
        return $this->_i_payment_cnt;
    }
    /**
     * Function to check if array values are empty againts given array keys
     *
     * @param array $aData array of data to check
     * @param array $aKeys array of array indexes
     *
     * @return bool
     */
    protected function check_arr_values_empty($a_data, $a_keys)
    {
        if (!is_array($a_keys) || count($a_keys) < 1) {
            return false;
        }
        foreach ($a_keys as $s_key) {
            if (isset($a_data[$s_key]) && !empty($a_data[$s_key])) {
                return false;
            }
        }
        return true;
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
        $a_path['title'] = Registry::get_lang()->translate_string('PAY', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Retuns config true if Vat is splitted
     *
     * @return array
     */
    public function is_payment_vat_splitted()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowVATForPayCharge');
    }
}