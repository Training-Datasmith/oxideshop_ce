<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Current user "My account" window.
 * When user is logged in arranges "My account" window, by creating
 * links to user details, order review, notice list, wish list. There
 * is a link for logging out. Template includes Topoffer , bargain
 * boxes. OXID eShop -> MY ACCOUNT.
 */
class Account_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Number of user's orders.
     *
     * @var integer
     */
    protected $_i_order_cnt;
    /**
     * Current article id.
     *
     * @var string
     */
    protected $_s_article_id;
    /**
     * Search parameter for Html
     *
     * @var string
     */
    protected $_s_search_param_for_html;
    /**
     * Search parameter
     *
     * @var string
     */
    protected $_s_search_param;
    /**
     * List type
     *
     * @var string
     */
    protected $_s_list_type;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/dashboard';
    /**
     * Current class login template name.
     *
     * @var string
     */
    protected $_s_this_login_template = 'page/account/login';
    /**
     * Alternative login template name.
     *
     * @var string
     */
    protected $_s_this_alt_login_template = 'page/privatesales/login';
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Start page meta description CMS ident
     *
     * @var string
     */
    protected $_s_meta_description_ident = 'oxstartmetadescription';
    /**
     * Start page meta keywords CMS ident
     *
     * @var string
     */
    protected $_s_meta_keywords_ident = 'oxstartmetakeywords';
    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = true;
    /**
     * Status of the account deletion
     */
    private ?bool $account_deletion_status = null;
    /**
     * Loads action articles. If user is logged and returns name of
     * template to render account::_sThisTemplate
     *
     * @return  string  $_sThisTemplate current template file name
     */
    public function render()
    {
        parent::render();
        // performing redirect if needed
        $this->redirect_after_login();
        // is logged in ?
        $user = $this->get_user();
        $password_field = 'oxuser__oxpassword';
        if (!$user || $user && !$user->{$password_field}->value || $this->is_enabled_private_sales() && $user && (!$user->is_terms_accepted() || $this->confirm_terms())) {
            $this->_s_this_template = $this->get_login_template();
        }
        return $this->_s_this_template;
    }
    /**
     * Returns login template name:
     *  - if "login" feature is on returns $this->_sThisAltLoginTemplate
     *  - else returns $this->_sThisLoginTemplate
     *
     * @return string
     */
    protected function get_login_template()
    {
        return $this->is_enabled_private_sales() ? $this->_s_this_alt_login_template : $this->_s_this_login_template;
    }
    /**
     * Confirms term agreement. Returns value of confirmed term
     *
     * @return string|bool
     */
    public function confirm_terms()
    {
        $terms_confirmation = Registry::get_request()->get_request_escaped_parameter('term');
        if (!$terms_confirmation && $this->is_enabled_private_sales()) {
            $user = $this->get_user();
            if ($user && !$user->is_terms_accepted()) {
                $terms_confirmation = true;
            }
        }
        return $terms_confirmation;
    }
    /**
     * Returns array from parent::getNavigationParams(). If current request
     * contains "sourcecl" and "anid" parameters - appends array with this
     * data. Array is used to fill forms and append shop urls with actual
     * state parameters
     *
     * @return array
     */
    public function get_navigation_params()
    {
        $parameters = parent::get_navigation_params();
        if ($source_class = Registry::get_request()->get_request_escaped_parameter('sourcecl')) {
            $parameters['sourcecl'] = $source_class;
        }
        if ($article_id = Registry::get_request()->get_request_escaped_parameter('anid')) {
            $parameters['anid'] = $article_id;
        }
        return $parameters;
    }
    /**
     * For some user actions (like writing product
     * review) user must be logged in. So e.g. in product details page
     * there is a link leading to current view. Link contains parameter
     * "sourcecl", which tells where to redirect after successfull login.
     * If this parameter is defined and oxcmp_user::getLoginStatus() ==
     * USER_LOGIN_SUCCESS (means user has just logged in) then user is
     * redirected back to source view.
     */
    public function redirect_after_login()
    {
        // in case source class is provided - redirecting back to it with all default parameters
        if (($source_class = Registry::get_request()->get_request_escaped_parameter('sourcecl')) && $this->_oa_components['oxcmp_user']->get_login_status() === USER_LOGIN_SUCCESS) {
            $redirect_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url() . 'index.php?cl=' . rawurlencode($source_class);
            // building redirect link
            foreach ($this->get_navigation_params() as $key => $value) {
                if ($value && $key != 'sourcecl') {
                    $redirect_url .= '&' . rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
                }
            }
            /** @var \OxidEsales\Eshop\Core\UtilsUrl $utilsUrl */
            $utils_url = Registry::get_utils_url();
            return Registry::get_utils()->redirect($utils_url->process_url($redirect_url), true, 302);
        }
    }
    /**
     * changes default template for compare in popup
     */
    public function get_order_cnt()
    {
        if ($this->_i_order_cnt === null) {
            $this->_i_order_cnt = 0;
            if ($user = $this->get_user()) {
                $this->_i_order_cnt = $user->get_order_count();
            }
        }
        return $this->_i_order_cnt;
    }
    /**
     * Return the active article id
     *
     * @return string|bool
     */
    public function get_article_id()
    {
        if ($this->_s_article_id === null) {
            // passing wishlist information
            if ($article_id = Registry::get_request()->get_request_escaped_parameter('aid')) {
                $this->_s_article_id = $article_id;
            }
        }
        return $this->_s_article_id;
    }
    /**
     * Template variable getter. Returns search parameter for Html
     *
     * @return string
     */
    public function get_search_param_for_html()
    {
        if ($this->_s_search_param_for_html === null) {
            $this->_s_search_param_for_html = false;
            if ($this->get_article_id()) {
                $this->_s_search_param_for_html = Registry::get_request()->get_request_escaped_parameter('searchparam');
            }
        }
        return $this->_s_search_param_for_html;
    }
    /**
     * Template variable getter. Returns search parameter
     *
     * @return string
     */
    public function get_search_param()
    {
        if ($this->_s_search_param === null) {
            $this->_s_search_param = false;
            if ($this->get_article_id()) {
                $this->_s_search_param = rawurlencode((string) Registry::get_request()->get_request_parameter('searchparam'));
            }
        }
        return $this->_s_search_param;
    }
    /**
     * Template variable getter. Returns list type
     *
     * @return string
     */
    public function get_list_type()
    {
        if ($this->_s_list_type === null) {
            $this->_s_list_type = false;
            if ($this->get_article_id()) {
                // searching in vendor #671
                $this->_s_list_type = Registry::get_request()->get_request_escaped_parameter('listtype');
            }
        }
        return $this->_s_list_type;
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $paths = [];
        $path_data = [];
        $language = Registry::get_lang();
        $base_language_id = $language->get_base_language();
        if ($user = $this->get_user()) {
            $username = $user->oxuser__oxusername->value;
            $path_data['title'] = $language->translate_string('MY_ACCOUNT', $base_language_id, false) . ' - ' . $username;
        } else {
            $path_data['title'] = $language->translate_string('LOGIN', $base_language_id, false);
        }
        $path_data['link'] = $this->get_link();
        $paths[] = $path_data;
        return $paths;
    }
    /**
     * Template variable getter. Returns article list count in comparison
     *
     * @return integer
     */
    public function get_compare_items_cnt()
    {
        $compare = ox_new(\Oxid_Esales\Eshop\Application\Controller\Compare_Controller::class);
        return $compare->get_compare_items_cnt();
    }
    /**
     * Page Title
     *
     * @return string
     */
    public function get_title()
    {
        $title = parent::get_title();
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view()->get_class_key() == 'account') {
            $base_language_id = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
            $title = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('PAGE_TITLE_ACCOUNT', $base_language_id, false);
            if ($user = $this->get_user()) {
                $username = $user->oxuser__oxusername->value;
                $title .= ' - "' . $username . '"';
            }
        }
        return $title;
    }
    /**
     * Deletes User account.
     */
    public function delete_account(): void
    {
        $this->account_deletion_status = false;
        $user = $this->get_user();
        /**
         * Setting derived to false allows mall users to delete their account being in a different shop as the shop
         * the account was originally created in.
         */
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blMallUsers')) {
            $user->set_is_derived(false);
        }
        if ($this->can_user_account_be_deleted() && $user->delete()) {
            $this->account_deletion_status = true;
            $user->logout();
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            $session->destroy();
        }
    }
    /**
     * Returns true if User is allowed to delete own account.
     *
     * @return bool
     */
    public function is_user_allowed_to_delete_own_account()
    {
        $allow_users_to_delete_their_account = Registry::get_config()->get_config_param('blAllowUsersToDeleteTheirAccount');
        $user = $this->get_user();
        return $allow_users_to_delete_their_account && $user && !$user->is_mall_admin();
    }
    /**
     * Template variable getter. Returns true, if a user account has been sucessfully deleted, else false.
     *
     * @return bool
     */
    public function get_account_deletion_status()
    {
        return $this->account_deletion_status;
    }
    /**
     * Checks if possible to delete user.
     */
    private function can_user_account_be_deleted(): bool
    {
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        return $session->check_session_challenge() && $this->is_user_allowed_to_delete_own_account();
    }
}