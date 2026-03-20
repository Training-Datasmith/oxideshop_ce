<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * The wishlist of someone else is displayed.
 */
class Wish_List_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/wishlist/wishlist';
    /**
     * user object list
     *
     * @return object
     */
    protected $_o_wish_user;
    /**
     * wishlist object list
     *
     * @return object
     */
    protected $_o_wish_list;
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
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = true;
    /**
     * return the user which is owner of the wish list
     *
     * @return object|bool
     */
    public function get_wish_user()
    {
        if ($this->_o_wish_user === null) {
            $this->_o_wish_user = false;
            $s_wish_id_parameter = Registry::get_request()->get_request_escaped_parameter('wishid');
            $s_user_id = $s_wish_id_parameter ?: Registry::get_session()->get_variable('wishid');
            if ($s_user_id) {
                $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
                if ($o_user->load($s_user_id)) {
                    // passing wishlist information
                    $this->_o_wish_user = $o_user;
                    // store this one to session
                    Registry::get_session()->set_variable('wishid', $s_user_id);
                }
            }
        }
        return $this->_o_wish_user;
    }
    /**
     * return the articles which are in the wish list
     *
     * @return object|bool
     */
    public function get_wish_list()
    {
        if ($this->_o_wish_list === null) {
            $this->_o_wish_list = false;
            // passing wishlist information
            if ($o_user = $this->get_wish_user()) {
                $o_wishlist_basket = $o_user->get_basket('wishlist');
                $this->_o_wish_list = $o_wishlist_basket->get_articles();
                if (!$o_wishlist_basket->is_visible()) {
                    $this->_o_wish_list = false;
                }
            }
        }
        return $this->_o_wish_list;
    }
    /**
     * Searches for wishlist of another user. Returns false if no
     * searching conditions set (no login name defined).
     *
     * Template variables:
     * <b>wish_result</b>, <b>search</b>
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
        $a_path['title'] = Registry::get_lang()->translate_string('PUBLIC_GIFT_REGISTRIES', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Page title
     *
     * @return string
     */
    public function get_title()
    {
        $o_lang = Registry::get_lang();
        if ($o_user = $this->get_wish_user()) {
            $s_translated_string = $o_lang->translate_string('GIFT_REGISTRY_OF_3', $o_lang->get_base_language(), false);
            $s_firstname_field = 'oxuser__oxfname';
            $s_lastname_field = 'oxuser__oxlname';
            return $s_translated_string . ' ' . $o_user->{$s_firstname_field}->value . ' ' . $o_user->{$s_lastname_field}->value;
        }
        return $o_lang->translate_string('PUBLIC_GIFT_REGISTRIES', $o_lang->get_base_language(), false);
    }
}