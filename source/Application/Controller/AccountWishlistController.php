<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Current user wishlist manager.
 * When user is logged in in this manager window he can modify his
 * own wishlist status - remove articles from wishlist or store
 * them to shopping basket, view detail information. Additionally
 * user can view wishlist of some other user by entering users
 * login name in special field. OXID eShop -> MY ACCOUNT
 *  -> Newsletter.
 */
#[\Allow_Dynamic_Properties]
class Account_Wishlist_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/wishlist';
    /**
     * If true, list will be shown, if false - will not
     *
     * @var bool
     */
    protected $_bl_show_suggest;
    /**
     * Wheter the var is false the wishlist will be shown
     *
     * @var \OxidEsales\Eshop\Application\Model\UserBasket|bool|null
     */
    protected $_o_wish_list;
    /**
     * list the wishlist items
     *
     * @var \OxidEsales\Eshop\Application\Model\UserBasket|bool|null
     */
    protected $_a_recomm_list;
    /**
     * Wheter the var is false the productlist will not be list
     *
     * @var \OxidEsales\Eshop\Application\Model\UserBasket|bool|null
     */
    protected $_o_editval;
    /**
     * If sending failed give false back
     *
     * @var integer / bool
     */
    protected $_i_send_wish_list;
    /**
     * Wishlist search param
     *
     * @var string
     */
    protected $_s_search_param;
    /**
     * List of users which were found according to search condition
     *
     * @var \OxidEsales\Eshop\Core\Model\ListModel
     */
    protected $_o_wish_list_users = false;
    /**
     * Wishlist email sending status
     *
     * @var bool
     */
    protected $_bl_email_sent = false;
    /**
     * User entered values for sending email
     *
     * @var array
     */
    protected $_a_edit_values = false;
    /**
     * Array of id to form recommendation list.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * If user is logged in loads his wishlist articles (articles may be accessed by
     * \OxidEsales\Eshop\Application\Model\User::GetBasket()), loads similar articles (is available) for
     * the last article in list loaded by \OxidEsales\Eshop\Application\Model\Article::GetSimilarProducts() and returns
     * name of template to render \OxidEsales\Eshop\Application\Controller\AccountWishlistController::_sThisTemplate
     *
     * @return  string  $_sThisTemplate current template file name
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
     * check if the wishlist is allowed
     *
     * @return bool
     */
    public function show_suggest()
    {
        if ($this->_bl_show_suggest === null) {
            $this->_bl_show_suggest = (bool) Registry::get_request()->get_request_escaped_parameter('blshowsuggest');
        }
        return $this->_bl_show_suggest;
    }
    /**
     * Show the Wishlist
     *
     * @return \OxidEsales\Eshop\Application\Model\UserBasket|bool
     */
    public function get_wish_list()
    {
        if ($this->_o_wish_list === null) {
            $this->_o_wish_list = false;
            if ($o_user = $this->get_user()) {
                $this->_o_wish_list = $o_user->get_basket('wishlist');
                if ($this->_o_wish_list->is_empty()) {
                    $this->_o_wish_list = false;
                }
            }
        }
        return $this->_o_wish_list;
    }
    /**
     * Returns array of producst assigned to user wish list
     *
     * @return array|bool
     */
    public function get_wish_product_list()
    {
        if (!isset($this->_a_wish_product_list)) {
            $this->_a_wish_product_list = false;
            if ($o_wish_list = $this->get_wish_list()) {
                $this->_a_wish_product_list = $o_wish_list->get_articles();
            }
        }
        return $this->_a_wish_product_list ?? null;
    }
    /**
     * Return array of id to form recommend list.
     *
     * @return array
     */
    public function get_similar_recomm_list_ids()
    {
        if ($this->_a_similar_recomm_list_ids === null) {
            $this->_a_similar_recomm_list_ids = false;
            $a_wish_prod_list = $this->get_wish_product_list();
            if (is_array($a_wish_prod_list) && $o_similar_prod = current($a_wish_prod_list)) {
                $this->_a_similar_recomm_list_ids = [$o_similar_prod->get_id()];
            }
        }
        return $this->_a_similar_recomm_list_ids;
    }
    /**
     * Sends wishlist mail to recipient. On errors returns false.
     *
     * @return bool
     */
    public function send_wish_list()
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return false;
        }
        $a_params = Registry::get_request()->get_request_parameter('editval');
        if (is_array($a_params)) {
            $o_utils_view = Registry::get_utils_view();
            $o_params = (object) $a_params;
            $this->set_entered_data((object) Registry::get_request()->get_request_escaped_parameter('editval'));
            if (!isset($a_params['rec_name']) || !isset($a_params['rec_email']) || !$a_params['rec_name'] || !$a_params['rec_email']) {
                return $o_utils_view->add_error_to_display('ERROR_MESSAGE_COMPLETE_FIELDS_CORRECTLY', false, true);
            }
            if ($o_user = $this->get_user()) {
                $s_first_name = 'oxuser__oxfname';
                $s_last_name = 'oxuser__oxlname';
                $s_send_email = 'send_email';
                $s_user_name_field = 'oxuser__oxusername';
                $s_send_name = 'send_name';
                $s_send_id = 'send_id';
                $o_params->{$s_send_email} = $o_user->{$s_user_name_field}->value;
                $o_params->{$s_send_name} = $o_user->{$s_first_name}->get_raw_value() . ' ' . $o_user->{$s_last_name}->get_raw_value();
                $o_params->{$s_send_id} = $o_user->get_id();
                $this->_bl_email_sent = ox_new(\Oxid_Esales\Eshop\Core\Email::class)->send_wishlist_mail($o_params);
                if (!$this->_bl_email_sent) {
                    return $o_utils_view->add_error_to_display('ERROR_MESSAGE_CHECK_EMAIL', false, true);
                }
            }
        }
    }
    /**
     * If email was sent.
     *
     * @return bool
     */
    public function is_wish_list_email_sent()
    {
        return $this->_bl_email_sent;
    }
    /**
     * Wishlist data setter
     *
     * @param object $oData suggest data object
     */
    public function set_entered_data($o_data): void
    {
        $this->_a_edit_values = $o_data;
    }
    /**
     * Terurns user entered values for sending email.
     *
     * @return array
     */
    public function get_entered_data()
    {
        return $this->_a_edit_values;
    }
    /**
     * Changes wishlist status - public/non public. Returns false on
     * error (if user is not logged in).
     *
     * @return bool
     */
    public function toggle_public()
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return false;
        }
        if ($o_user = $this->get_user()) {
            $bl_public = (int) Registry::get_request()->get_request_escaped_parameter('blpublic');
            $o_basket = $o_user->get_basket('wishlist');
            $o_basket->oxuserbaskets__oxpublic = new \Oxid_Esales\Eshop\Core\Field($bl_public == 1 ? $bl_public : 0);
            $o_basket->save();
        }
    }
    /**
     * Searches for wishlist of another user. Returns false if no
     * searching conditions set (no login name defined).
     */
    public function search_for_wish_list(): void
    {
        if ($s_search = Registry::get_request()->get_request_escaped_parameter('search')) {
            // search for baskets
            $o_user_list = ox_new(\Oxid_Esales\Eshop\Application\Model\User_List::class);
            $o_user_list->load_wishlist_users($s_search);
            if ($o_user_list->count()) {
                $this->_o_wish_list_users = $o_user_list;
            }
            $this->_s_search_param = $s_search;
        }
    }
    /**
     * Returns a list of users which were found according to search condition.
     * If no users were found - false is returned
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel|bool
     */
    public function get_wish_list_users()
    {
        return $this->_o_wish_list_users;
    }
    /**
     * Returns wish list search parameter
     *
     * @return string
     */
    public function get_wish_list_search_param()
    {
        return $this->_s_search_param;
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
        $a_path['title'] = Registry::get_lang()->translate_string('MY_GIFT_REGISTRY', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}