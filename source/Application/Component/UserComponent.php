<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

use function array_key_exists;
use Exception;
use function is_array;
use Oxid_Esales\Eshop\Application\Model\Address;
use Oxid_Esales\Eshop\Application\Model\User;
use Oxid_Esales\Eshop\Application\Model\User\User_Shipping_Address_Updatable_Fields;
use Oxid_Esales\Eshop\Application\Model\User\User_Updatable_Fields;
use Oxid_Esales\Eshop\Core\Contract\Abstract_Updatable_Fields;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Exception\Connection_Exception;
use Oxid_Esales\Eshop\Core\Exception\Database_Connection_Exception;
use Oxid_Esales\Eshop\Core\Exception\Input_Exception;
use Oxid_Esales\Eshop\Core\Exception\User_Exception;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Form\Form_Fields;
use Oxid_Esales\Eshop\Core\Form\Form_Fields_Trimmer;
use Oxid_Esales\Eshop\Core\Form\Updatable_Fields_Constructor;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge\Password_Service_Bridge_Interface;
// defining login/logout states
define('USER_LOGIN_SUCCESS', 1);
define('USER_LOGIN_FAIL', 2);
define('USER_LOGOUT', 3);
/**
 * User object manager.
 * Sets user details data, switches, logouts, logins user etc.
 *
 * @subpackage oxcmp
 */
class User_Component extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * Boolean - if user is new or not.
     *
     * @var bool
     */
    protected $_bl_is_new_user = false;
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_bl_is_component = true;
    /**
     * Newsletter subscription status
     *
     * @var bool
     */
    protected $_bl_news_subscription_status;
    /**
     * User login state marker:
     *  - USER_LOGIN_SUCCESS - user successfully logged in;
     *  - USER_LOGIN_FAIL - login failed;
     *  - USER_LOGOUT - user logged out.
     *
     * @var int
     */
    protected $_i_login_status;
    /**
     * Terms/conditions version number
     *
     * @var string
     */
    protected $_s_terms_ver;
    /**
     * View classes accessible for not logged in customers
     *
     * @var array
     */
    protected $_a_allowed_classes = ['register', 'forgotpwd', 'content', 'account', 'clearcookies', 'oxwservicemenu', 'oxwminibasket'];
    /**
     * Sets oxcmp_oxuser::blIsComponent = true, fetches user error
     * code and sets it to default - 0. Executes parent::init().
     *
     * Session variable:
     * <b>usr_err</b>
     */
    public function init(): void
    {
        $this->save_delivery_address_state();
        $this->load_session_user();
        $this->save_invitor();
        parent::init();
    }
    /**
     * Executes parent::render(), oxcmp_user::_loadSessionUser(), loads user delivery
     * info. Returns user object oxcmp_user::oUser.
     *
     * @return  object  user object
     */
    public function render()
    {
        // checks if private sales allows further tasks
        $this->check_ps_state();
        parent::render();
        return $this->get_user();
    }
    /**
     * If private sales enabled, checks:
     *  (1) if no session user and view can be accessed;
     *  (2) session user is available and accepted terms version matches actual version.
     * In case any condition is not satisfied redirects user to:
     *  (1) login page;
     *  (2) terms agreement page;
     */
    protected function check_ps_state()
    {
        $o_config = Registry::get_config();
        if ($this->get_parent()->is_enabled_private_sales()) {
            // load session user
            $o_user = $this->get_user();
            $s_class = $this->get_parent()->get_class_key();
            // no session user
            if (!$o_user && !in_array($s_class, $this->_a_allowed_classes)) {
                Registry::get_utils()->redirect($o_config->get_shop_home_url() . 'cl=account', false, 302);
            }
            if ($o_user && !$o_user->is_terms_accepted() && !in_array($s_class, $this->_a_allowed_classes)) {
                Registry::get_utils()->redirect($o_config->get_shop_home_url() . 'cl=account&term=1', false, 302);
            }
        }
    }
    /**
     * Tries to load user ID from session.
     */
    protected function load_session_user()
    {
        $my_config = Registry::get_config();
        $session = Registry::get_session();
        $o_user = $this->get_user();
        // no session user
        if (!$o_user) {
            return;
        }
        // this user is blocked, deny him
        if ($o_user->in_group('oxidblocked')) {
            $s_url = $my_config->get_shop_home_url() . 'cl=content&tpl=user_blocked';
            Registry::get_utils()->redirect($s_url, true, 302);
        }
        // TODO: move this to a proper place
        if ($o_user->is_loaded_from_cookie() && !$my_config->get_config_param('blPerfNoBasketSaving')) {
            if ($o_basket = $session->get_basket()) {
                $o_basket->load();
                $o_basket->on_update();
            }
        }
    }
    /**
     * Collects posted user information from posted variables ("lgn_usr",
     * "lgn_pwd", "lgn_cook"), executes User::login() and checks if
     * such user exists.
     *
     * Session variables:
     * <b>usr</b>, <b>usr_err</b>
     *
     * Template variables:
     * <b>usr_err</b>
     *
     * @return  string  redirection string
     */
    public function login()
    {
        $s_user = Registry::get_request()->get_request_escaped_parameter('lgn_usr');
        $s_password = Registry::get_request()->get_request_parameter('lgn_pwd');
        $s_cookie = Registry::get_request()->get_request_escaped_parameter('lgn_cook');
        $this->set_login_status(USER_LOGIN_FAIL);
        // trying to login user
        try {
            /** @var User $oUser */
            $o_user = ox_new(User::class);
            $o_user->login($s_user, $s_password, $s_cookie);
            $this->set_login_status(USER_LOGIN_SUCCESS);
        } catch (User_Exception $o_ex) {
            // for login component send exception text to a custom component (if defined)
            Registry::get_utils_view()->add_error_to_display($o_ex, false, true, '', false);
            return 'user';
        } catch (\Oxid_Esales\Eshop\Core\Exception\Cookie_Exception $o_ex) {
            Registry::get_utils_view()->add_error_to_display($o_ex);
            return 'user';
        }
        // finalizing ..
        return $this->after_login($o_user);
    }
    /**
     * Special functionality which is performed after user logs in (or user is created without pass).
     * Performes additional checking if user is not BLOCKED
     * (User::InGroup("oxidblocked")) - if yes - redirects to blocked user
     * page ("cl=content&tpl=user_blocked").
     * Stores cookie info if user confirmed in login screen.
     * Then loads delivery info and forces basket to recalculate
     * (\OxidEsales\Eshop\Core\Session::getBasket() + oBasket::blCalcNeeded = true). Returns
     * "payment" to redirect to payment screen. If problems occured loading
     * user - sets error code according problem, and returns "user" to redirect
     * to user info screen.
     *
     * @param User $oUser user object
     *
     * @return string
     */
    protected function after_login($o_user)
    {
        $session = Registry::get_session();
        if ($session->is_session_started()) {
            $session->regenerate_session_id();
        }
        // this user is blocked, deny him
        if ($o_user->in_group('oxidblocked')) {
            $s_url = Registry::get_config()->get_shop_home_url() . 'cl=content&tpl=user_blocked';
            Registry::get_utils()->redirect($s_url, true, 302);
        }
        // recalc basket
        if ($o_basket = $session->get_basket()) {
            $o_basket->on_update();
        }
        return 'payment';
    }
    /**
     * Executes oxcmp_user::login() method. After loggin user will not be
     * redirected to user or payment screens.
     */
    public function login_noredirect(): void
    {
        $bl_agb = Registry::get_request()->get_request_escaped_parameter('ord_agb');
        if ($this->get_parent()->is_enabled_private_sales() && $bl_agb !== null && $o_user = $this->get_user()) {
            if ($bl_agb) {
                $o_user->accept_terms();
            }
        } else {
            $this->login();
            if (!$this->is_admin() && !Registry::get_config()->get_config_param('blPerfNoBasketSaving')) {
                //load basket from the database
                try {
                    $session = Registry::get_session();
                    if ($o_basket = $session->get_basket()) {
                        $o_basket->load();
                    }
                } catch (Exception) {
                    //just ignore it
                }
            }
        }
    }
    /**
     * Special utility function which is executed right after
     * oxcmp_user::logout is called. Currently it unsets such
     * session parameters as user chosen payment id, delivery
     * address id, active delivery set.
     */
    protected function after_logout()
    {
        $session = Registry::get_session();
        $session->delete_variable('paymentid');
        $session->delete_variable('sShipSet');
        $session->delete_variable('deladrid');
        $session->delete_variable('dynvalue');
        // resetting & recalc basket
        if ($o_basket = $session->get_basket()) {
            $o_basket->reset_user_info();
            $o_basket->on_update();
            // resetting voucher reservations
            if ($vouchers = $o_basket->get_vouchers()) {
                foreach ($vouchers as $voucher_id => $voucher) {
                    $o_basket->remove_voucher($voucher_id);
                }
            }
        }
        $session->del_basket();
    }
    /**
     * @return string|void
     */
    public function logout()
    {
        if (ox_new(User::class)->logout()) {
            $this->set_login_status(USER_LOGOUT);
            $this->after_logout();
            $this->reset_permissions();
            if ($this->get_parent()->is_enabled_private_sales()) {
                return 'account';
            }
            if (Container_Facade::get_parameter('oxid_esales.shop_url') && Registry::get_request()->get_request_escaped_parameter('redirect')) {
                Registry::get_utils()->redirect($this->get_logout_link());
            }
        }
    }
    /**
     * Any additional permission reset actions required on logout or changeuser actions
     */
    protected function reset_permissions()
    {
    }
    /**
     * Executes blUserRegistered = oxcmp_user::changeUserWithoutRedirect().
     * if this returns true - returns "payment" (this redirects to
     * payment page), else returns blUserRegistered value.
     *
     * @see oxcmp_user::changeUserWithoutRedirect()
     *
     * @return  mixed    redirection string or true if user is registered, false otherwise
     */
    public function change_user()
    {
        return $this->change_user_without_redirect() === true ? 'payment' : false;
    }
    /**
     * Executes oxcmp_user::changeUserWithoutRedirect().
     * returns "account_user" (this redirects to billing and shipping settings page) on success
     */
    public function changeuser_testvalues()
    {
        // skip updating user info if this is just form reload
        // on selecting delivery address
        // We do redirect only on success not to loose errors.
        if ($this->change_user_without_redirect()) {
            return 'account_user';
        }
    }
    /**
     * First test if all required fields were filled, then performed
     * additional checking oxcmp_user::CheckValues(). If no errors
     * occured - trying to create new user (User::CreateUser()),
     * logging him to shop (User::Login() if user has entered password).
     * If User::CreateUser() returns false - this means user is
     * already created - we only logging him to shop (oxcmp_user::Login()).
     * If there is any error with missing data - function will return
     * false and set error code (oxcmp_user::iError). If user was
     * created successfully - will return "payment" to redirect to
     * payment interface.
     *
     * Template variables:
     * <b>usr_err</b>
     *
     * Session variables:
     * <b>usr_err</b>, <b>usr</b>
     *
     * @return  mixed    redirection string or true if successful, false otherwise
     */
    public function create_user()
    {
        if (!Registry::get_session()->check_session_challenge()) {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return false;
        }
        $is_private_sales = $this->get_parent()->is_enabled_private_sales();
        if ($is_private_sales && !Registry::get_request()->get_request_escaped_parameter('ord_agb') && Registry::get_config()->get_config_param('blConfirmAGB')) {
            Registry::get_utils_view()->add_error_to_display('READ_AND_CONFIRM_TERMS', false, true);
            return false;
        }
        $username = Registry::get_request()->get_request_escaped_parameter('lgn_usr');
        $password = Registry::get_request()->get_request_parameter('lgn_pwd');
        $password_confirmation = Registry::get_request()->get_request_parameter('lgn_pwd2');
        $billing_address = $this->get_billing_address();
        $shipping_address = $this->get_shipping_address();
        try {
            $user = ox_new(User::class);
            $user->check_values($username, $password, $password_confirmation, $billing_address, $shipping_address);
            $user->oxuser__oxusername = new Field($username, Field::T_RAW);
            $user->set_password($password);
            $user->oxuser__oxactive = new Field($is_private_sales ? 0 : 1, Field::T_RAW);
            $user_subscription_status = $user->get_news_subscription()->get_opt_in_status();
            $database = Database_Provider::get_db();
            $database->start_transaction();
            try {
                $user->create_user();
                $user = $this->configure_user_before_creation($user);
                $user->load($user->get_id());
                $user->change_user_data($user->oxuser__oxusername->value, $password, $password, $billing_address, $shipping_address);
                if ($is_private_sales) {
                    $user->accept_terms();
                }
                $database->commit_transaction();
            } catch (Exception $exception) {
                $database->rollback_transaction();
                throw $exception;
            }
            $invitation_sender_user_id = Registry::get_session()->get_variable('su');
            $invitation_recipient_email = Registry::get_session()->get_variable('re');
            if ($invitation_sender_user_id && $invitation_recipient_email && Registry::get_config()->get_config_param('blInvitationsEnabled')) {
                $user->set_credit_points_for_registrant($invitation_sender_user_id, $invitation_recipient_email);
            }
            $is_subscription_requested = Registry::get_request()->get_request_escaped_parameter('blnewssubscribed');
            if ($is_subscription_requested && $user_subscription_status == 1) {
                // if user was assigned to newsletter
                // and is creating account with newsletter checked,
                // don't require confirm
                $user->get_news_subscription()->set_opt_in_status(1);
                $user->add_to_group('oxidnewsletter');
                $this->_bl_news_subscription_status = 1;
            } else {
                $is_subscription_email_requested = Registry::get_config()->get_config_param('blOrderOptInEmail');
                $this->_bl_news_subscription_status = $user->set_news_subscription($is_subscription_requested, $is_subscription_email_requested);
            }
            $user->add_to_group('oxidnotyetordered');
            $user->logout();
        } catch (User_Exception|Connection_Exception|Input_Exception|Database_Connection_Exception $exception) {
            Registry::get_utils_view()->add_error_to_display($exception, false, true);
            return false;
        }
        if (!$is_private_sales) {
            Registry::get_session()->set_variable('usr', $user->get_id());
            $this->set_session_login_token((string) $user->get_field_data('oxpassword'));
            $this->after_login($user);
            // order remark
            //V #427: order remark for new users
            $order_remark = Registry::get_request()->get_request_parameter('order_remark');
            if ($order_remark) {
                Registry::get_session()->set_variable('ordrem', $order_remark);
            }
        }
        if ((int) Registry::get_request()->get_request_escaped_parameter('option') === 3) {
            $user->send_registration_email($is_private_sales);
        }
        // new registered
        $this->_bl_is_new_user = true;
        return $this->_bl_news_subscription_status !== null && !$this->_bl_news_subscription_status ? 'payment?new_user=1&success=1&newslettererror=4' : 'payment?new_user=1&success=1';
    }
    /**
     * If any additional configurations required right before user creation
     *
     * @param User $user
     *
     * @return User The user we gave in.
     */
    protected function configure_user_before_creation($user)
    {
        return $user;
    }
    /**
     * Creates new oxid user
     *
     * @return string partial parameter string or null
     */
    public function register_user()
    {
        // registered new user ?
        if ($this->create_user() != false && $this->_bl_is_new_user) {
            if ($this->_bl_news_subscription_status === null || $this->_bl_news_subscription_status) {
                return 'register?success=1';
            }
            return 'register?success=1&newslettererror=4';
        }
        // problems with registration ...
        $this->logout();
    }
    /**
     * Deletes user shipping address.
     */
    public function delete_shipping_address(): void
    {
        $session = Registry::get_session();
        $address_id = Registry::get_request()->get_request_escaped_parameter('oxaddressid');
        $address = ox_new(Address::class);
        $address->load($address_id);
        if ($this->can_user_delete_shipping_address($address) && $session->check_session_challenge()) {
            $address->delete($address_id);
        }
    }
    /**
     * Checks if shipping address is assigned to user.
     *
     * @param Address $address
     * @return bool
     */
    private function can_user_delete_shipping_address($address)
    {
        $can_delete = false;
        $user = $this->get_user();
        if ($address->oxaddress__oxuserid->value === $user->get_id()) {
            return true;
        }
        return $can_delete;
    }
    /**
     * Saves invitor ID
     */
    protected function save_invitor()
    {
        if (Registry::get_config()->get_config_param('blInvitationsEnabled')) {
            $this->get_invitor();
            $this->set_recipient();
        }
    }
    /**
     * Saving show/hide delivery address state
     */
    protected function save_delivery_address_state()
    {
        $o_session = Registry::get_session();
        $bl_show = Registry::get_request()->get_request_escaped_parameter('blshowshipaddress');
        if (!isset($bl_show)) {
            $bl_show = $o_session->get_variable('blshowshipaddress');
        }
        $o_session->set_variable('blshowshipaddress', $bl_show);
    }
    /**
     * Mostly used for customer profile editing screen (OXID eShop ->
     * MY ACCOUNT). Checks if oUser is set (oxcmp_user::oUser) - if
     * not - executes oxcmp_user::_loadSessionUser(). If user unchecked newsletter
     * subscription option - removes him from this group. There is an
     * additional MUST FILL fields checking. Function returns true or false
     * according to user data submission status.
     *
     * Session variables:
     * <b>ordrem</b>
     *
     * @return  bool|void true on success, false otherwise
     */
    protected function change_user_without_redirect()
    {
        $session = Registry::get_session();
        if (!$session->check_session_challenge()) {
            return;
        }
        $user = $this->get_user();
        if (!$user) {
            return;
        }
        $current_email = $user->get_field_data('oxusername');
        $password = $user->get_field_data('oxpassword');
        $shipping_address = $this->get_shipping_address();
        $billing_address = $this->get_billing_address();
        $new_email = $billing_address['oxuser__oxusername'] ?? '';
        try {
            $is_username_updated = $this->is_user_name_updated($current_email ?? '', $new_email);
            if ($is_username_updated && $this->is_guest_user($user)) {
                $this->delete_existing_guest_user($new_email);
            }
            if (!$this->is_guest_user($user) && $is_username_updated && $user->is_email_in_use($new_email)) {
                Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_USER_USEREXISTS');
                return false;
            }
            $user->change_user_data($current_email, $password, $password, $billing_address, $shipping_address);
            $is_subscription_requested = Registry::get_request()->get_request_escaped_parameter('blnewssubscribed');
            $user_subscription_status = $is_subscription_requested ?? $user->get_news_subscription()->get_opt_in_status();
            $is_subscription_email_requested = Registry::get_config()->get_config_param('blOrderOptInEmail');
            $this->_bl_news_subscription_status = $user->set_news_subscription($user_subscription_status, $is_subscription_email_requested, $is_username_updated);
            $this->reset_permissions();
            $order_remark = Registry::get_request()->get_request_parameter('order_remark');
            if ($order_remark) {
                $session->set_variable('ordrem', $order_remark);
            } else {
                $session->delete_variable('ordrem');
            }
            if ($basket = $session->get_basket()) {
                $basket->set_basket_user(null);
                $basket->on_update();
            }
            return true;
        } catch (User_Exception|Connection_Exception|Input_Exception $exception) {
            Registry::get_utils_view()->add_error_to_display($exception, false, true);
            return;
        } catch (\Throwable) {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_USER_UPDATE_FAILED', false, true);
            return false;
        }
    }
    /**
     * Returns delivery address from request. Before returning array is checked if
     * all needed data is there
     *
     * @return array
     */
    protected function get_del_address_data()
    {
        // if user company name, user name and additional info has special chars
        $bl_show_ship_address_parameter = Registry::get_request()->get_request_escaped_parameter('blshowshipaddress');
        $bl_show_ship_address_variable = Registry::get_session()->get_variable('blshowshipaddress');
        $s_delivery_address_parameter = Registry::get_request()->get_request_parameter('deladr');
        $a_deladr = $bl_show_ship_address_parameter || $bl_show_ship_address_variable ? $s_delivery_address_parameter : [];
        $a_del_adress = $a_deladr;
        if (is_array($a_deladr)) {
            // checking if data is filled
            if (isset($a_deladr['oxaddress__oxsal'])) {
                unset($a_deladr['oxaddress__oxsal']);
            }
            if (!count($a_deladr) || implode('', $a_deladr) == '') {
                // resetting to avoid empty records
                $a_del_adress = [];
            }
        }
        return $a_del_adress;
    }
    /**
     * Returns logout link with additional params
     *
     * @return string $sLogoutLink
     */
    protected function get_logout_link()
    {
        $o_config = Registry::get_config();
        $s_logout_link = $o_config->is_ssl() ? $o_config->get_shop_secure_home_url() : $o_config->get_shop_home_url();
        $s_logout_link .= 'cl=' . $o_config->get_request_controller_id() . $this->get_parent()->get_dyn_url_params();
        if ($s_param = Registry::get_request()->get_request_escaped_parameter('anid')) {
            $s_logout_link .= '&amp;anid=' . $s_param;
        }
        if ($s_param = Registry::get_request()->get_request_escaped_parameter('cnid')) {
            $s_logout_link .= '&amp;cnid=' . $s_param;
        }
        if ($s_param = Registry::get_request()->get_request_escaped_parameter('mnid')) {
            $s_logout_link .= '&amp;mnid=' . $s_param;
        }
        if ($s_param = basename((string) Registry::get_request()->get_request_escaped_parameter('tpl'))) {
            $s_logout_link .= '&amp;tpl=' . $s_param;
        }
        if ($s_param = Registry::get_request()->get_request_escaped_parameter('oxloadid')) {
            $s_logout_link .= '&amp;oxloadid=' . $s_param;
        }
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if ($s_param = Registry::get_request()->get_request_escaped_parameter('recommid')) {
            $s_logout_link .= '&amp;recommid=' . $s_param;
        }
        // END deprecated
        return $s_logout_link . '&amp;fnc=logout';
    }
    /**
     * Sets user login state
     *
     * @param int $iStatus login state (USER_LOGIN_SUCCESS/USER_LOGIN_FAIL/USER_LOGOUT)
     */
    public function set_login_status($i_status): void
    {
        $this->_i_login_status = $i_status;
    }
    /**
     * Returns user login state marker:
     *  - USER_LOGIN_SUCCESS - user successfully logged in;
     *  - USER_LOGIN_FAIL - login failed;
     *  - USER_LOGOUT - user logged out.
     *
     * @return int
     */
    public function get_login_status()
    {
        return $this->_i_login_status;
    }
    /**
     * Sets invitor id to session from URL
     */
    public function get_invitor(): void
    {
        $s_su = Registry::get_session()->get_variable('su');
        if (!$s_su && $s_su_new = Registry::get_request()->get_request_escaped_parameter('su')) {
            Registry::get_session()->set_variable('su', $s_su_new);
        }
    }
    /**
     * sets from URL invitor id
     */
    public function set_recipient(): void
    {
        $s_re = Registry::get_session()->get_variable('re');
        if (!$s_re && $s_re_new = Registry::get_request()->get_request_escaped_parameter('re')) {
            Registry::get_session()->set_variable('re', $s_re_new);
        }
    }
    /**
     * @param array                   $address
     * @param AbstractUpdatableFields $updatableFields
     *
     * @return array
     */
    private function clean_address($address, $updatable_fields)
    {
        if (is_array($address)) {
            /** @var UpdatableFieldsConstructor $updatableFieldsConstructor */
            $updatable_fields_constructor = ox_new(Updatable_Fields_Constructor::class);
            $cleaner = $updatable_fields_constructor->get_allowed_fields_cleaner($updatable_fields);
            return $cleaner->filter_by_updatable_fields($address);
        }
        return $address;
    }
    /**
     * Returns trimmed address.
     *
     * @param array $address
     *
     * @return array
     */
    private function trim_address($address)
    {
        if (is_array($address)) {
            $fields = ox_new(Form_Fields::class, $address);
            $trimmer = ox_new(Form_Fields_Trimmer::class);
            $address = (array) $trimmer->trim($fields);
        }
        return $address;
    }
    private function is_guest_user(User $user): bool
    {
        return empty($user->oxuser__oxpassword->value);
    }
    private function is_user_name_updated(string $current_name, string $new_name): bool
    {
        return $current_name && $new_name && $current_name !== $new_name;
    }
    /**
     * @throws Exception
     */
    private function delete_existing_guest_user(string $new_name): void
    {
        $existing_user = ox_new(User::class);
        $existing_user->load($existing_user->get_id_by_user_name($new_name));
        if ($existing_user && $this->is_guest_user($existing_user)) {
            $existing_user->delete();
        }
    }
    private function get_shipping_address(): ?array
    {
        $shipping_address = $this->get_del_address_data();
        $shipping_address = $this->clean_address($shipping_address, ox_new(User_Shipping_Address_Updatable_Fields::class));
        return $this->trim_address($shipping_address);
    }
    private function get_billing_address(): ?array
    {
        $billing_address = Registry::get_request()->get_request_parameter('invadr');
        $billing_address = $this->clean_address($billing_address, ox_new(User_Updatable_Fields::class));
        if ($billing_address && is_array($billing_address)) {
            $billing_address = $this->remove_non_address_fields($billing_address);
        }
        return $this->trim_address($billing_address);
    }
    private function remove_non_address_fields(array $address_form_data): array
    {
        $non_address_fields = ['oxuser__oxactive', 'oxuser__oxshopid', 'oxuser__oxpassword', 'oxuser__oxpasssalt', 'oxuser__oxupdatekey', 'oxuser__oxupdateexp'];
        foreach ($non_address_fields as $field) {
            if ($address_form_data && array_key_exists($field, $address_form_data)) {
                unset($address_form_data[$field]);
            }
        }
        return $address_form_data;
    }
    private function set_session_login_token(string $password_hash): void
    {
        Registry::get_session()->set_variable('login-token', Container_Facade::get(Password_Service_Bridge_Interface::class)->hash($password_hash));
    }
}