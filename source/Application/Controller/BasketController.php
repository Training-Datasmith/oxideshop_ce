<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Application\Model\Basket_Content_Mark_Generator;
use Oxid_Esales\Eshop\Application\Model\Wrapping;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Psr\Log\Logger_Interface;
/**
 * Current session shopping cart (basket item list).
 * Contains with user selected articles (with detail information), list of
 * similar products, top offer articles.
 * OXID eShop -> SHOPPING CART.
 */
class Basket_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/checkout/basket';
    /**
     * Order step marker
     *
     * @var bool
     */
    protected $_bl_is_order_step = true;
    /**
     * all basket articles
     *
     * @var object
     */
    protected $_o_basket_articles;
    /**
     * Similar List
     *
     * @var object
     */
    protected $_o_similar_list;
    /**
     * Recomm List
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_o_recomm_list;
    /**
     * First basket product object. It is used to load
     * recommendation list info and similar product list
     *
     * @var \OxidEsales\EshopCommunity\Application\Model\Article
     */
    protected $_o_first_basket_product;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Wrapping objects list
     *
     * @var \OxidEsales\Eshop\Core\Model\ListModel
     */
    protected $_o_wrappings;
    /**
     * Card objects list
     *
     * @var \OxidEsales\Eshop\Core\Model\ListModel
     */
    protected $_o_cards;
    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * Executes parent::render(), creates list with basket articles
     * Returns name of template file basket::_sThisTemplate (for Search
     * engines return "content" template to avoid fake orders etc).
     *
     * @return  string   $this->_sThisTemplate  current template file name
     */
    public function render()
    {
        $config = Registry::get_config();
        if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
            $session = Registry::get_session();
            $session->get_basket_reservations()->renew_expiration();
        }
        $this->_a_view_data['allowUnevenAmounts'] = $config->get_config_param('blAllowUnevenAmounts');
        parent::render();
        return $this->_s_this_template;
    }
    /**
     * Return the current articles from the basket
     *
     * @return object|bool
     */
    public function get_basket_articles()
    {
        if ($this->_o_basket_articles === null) {
            $this->_o_basket_articles = false;
            // passing basket articles
            $session = Registry::get_session();
            if ($o_basket = $session->get_basket()) {
                $this->_o_basket_articles = $o_basket->get_basket_articles();
            }
        }
        return $this->_o_basket_articles;
    }
    /**
     * return the basket articles
     *
     * @return object|bool
     */
    public function get_first_basket_product()
    {
        if ($this->_o_first_basket_product === null) {
            $this->_o_first_basket_product = false;
            $a_basket_articles = $this->get_basket_articles();
            if (is_array($a_basket_articles) && $o_product = reset($a_basket_articles)) {
                $this->_o_first_basket_product = $o_product;
            }
        }
        return $this->_o_first_basket_product;
    }
    /**
     * return the similar articles
     *
     * @return object|bool
     */
    public function get_basket_similar_list()
    {
        if ($this->_o_similar_list === null) {
            $this->_o_similar_list = false;
            // similar product info
            if ($o_product = $this->get_first_basket_product()) {
                $this->_o_similar_list = $o_product->get_similar_products();
            }
        }
        return $this->_o_similar_list;
    }
    /**
     * Return array of id to form recommend list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array
     */
    public function get_similar_recomm_list_ids()
    {
        if ($this->_a_similar_recomm_list_ids === null) {
            $this->_a_similar_recomm_list_ids = false;
            if ($o_product = $this->get_first_basket_product()) {
                $this->_a_similar_recomm_list_ids = [$o_product->get_id()];
            }
        }
        return $this->_a_similar_recomm_list_ids;
    }
    /**
     * return the Link back to shop
     *
     * @return bool
     */
    public function show_back_to_shop()
    {
        $i_new_basket_item_message = Registry::get_config()->get_config_param('iNewBasketItemMessage');
        $s_back_to_shop = Registry::get_session()->get_variable('_backtoshop');
        return $i_new_basket_item_message == 3 && $s_back_to_shop;
    }
    /**
     * Assigns voucher to current basket
     */
    public function add_voucher(): void
    {
        $session = Registry::get_session();
        if (!$session->check_session_challenge()) {
            Container_Facade::get(Logger_Interface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }
        if (!$this->get_view_config()->get_show_vouchers()) {
            return;
        }
        $o_basket = $session->get_basket();
        try {
            $o_basket->add_voucher(Registry::get_request()->get_request_escaped_parameter('voucherNr'));
        } catch (\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception $o_ex) {
            // problems adding voucher
            Registry::get_utils_view()->add_error_to_display($o_ex, false, true);
        }
    }
    /**
     * Removes voucher from basket (calls \OxidEsales\Eshop\Application\Model\Basket::removeVoucher())
     */
    public function remove_voucher(): void
    {
        $session = Registry::get_session();
        if (!$session->check_session_challenge()) {
            Container_Facade::get(Logger_Interface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }
        if (!$this->get_view_config()->get_show_vouchers()) {
            return;
        }
        $o_basket = $session->get_basket();
        $o_basket->remove_voucher(Registry::get_request()->get_request_escaped_parameter('voucherId'));
    }
    /**
     * Redirects user back to previous part of shop (list, details, ...) from basket.
     * Used with option "Display Message when Product is added to Cart" set to "Open Basket"
     * ($myConfig->iNewBasketItemMessage == 3)
     *
     * @return string   $sBackLink  back link
     */
    public function back_to_shop()
    {
        if (Registry::get_config()->get_config_param('iNewBasketItemMessage') == 3) {
            $o_session = Registry::get_session();
            if ($s_back_link = $o_session->get_variable('_backtoshop')) {
                $o_session->delete_variable('_backtoshop');
                return $s_back_link;
            }
        }
    }
    /**
     * Returns a name of the view variable containing the error/exception messages
     */
    public function get_error_destination()
    {
        return 'basket';
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
        if (!isset($this->_i_wrap_cnt)) {
            $wrapping = ox_new(Wrapping::class);
            $this->_i_wrap_cnt = $wrapping->get_wrapping_count('WRAP') + $wrapping->get_wrapping_count('CARD');
        }
        return (bool) $this->_i_wrap_cnt;
    }
    /**
     * Return basket wrappings list if available
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_wrapping_list()
    {
        if ($this->_o_wrappings === null) {
            $this->_o_wrappings = new \Oxid_Esales\Eshop\Core\Model\List_Model();
            // load wrapping papers
            if ($this->get_view_config()->get_show_gift_wrapping()) {
                $this->_o_wrappings = ox_new(Wrapping::class)->get_wrapping_list('WRAP');
            }
        }
        return $this->_o_wrappings;
    }
    /**
     * Returns greeting cards list if available
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_card_list()
    {
        if ($this->_o_cards === null) {
            $this->_o_cards = new \Oxid_Esales\Eshop\Core\Model\List_Model();
            // load gift cards
            if ($this->get_view_config()->get_show_gift_wrapping()) {
                $this->_o_cards = ox_new(Wrapping::class)->get_wrapping_list('CARD');
            }
        }
        return $this->_o_cards;
    }
    /**
     * Updates wrapping data in session basket object
     * (\OxidEsales\Eshop\Core\Session::getBasket()) - adds wrapping info to
     * each article in basket (if possible). Plus adds
     * gift message and chosen card ( takes from GET/POST/session;
     * oBasket::giftmessage, oBasket::chosencard). Then sets
     * basket back to session (\OxidEsales\Eshop\Core\Session::setBasket()).
     */
    public function change_wrapping(): void
    {
        $session = Registry::get_session();
        if (!$session->check_session_challenge()) {
            Container_Facade::get(Logger_Interface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }
        if ($this->get_view_config()->get_show_gift_wrapping()) {
            $o_basket = $session->get_basket();
            $this->set_wrapping_info($o_basket, Registry::get_request()->get_request_escaped_parameter('wrapping'));
            $o_basket->set_card_message(Registry::get_request()->get_request_escaped_parameter('giftmessage'));
            $o_basket->set_card_id(Registry::get_request()->get_request_escaped_parameter('chosencard'));
            $o_basket->on_update();
        }
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
        $a_path['title'] = Registry::get_lang()->translate_string('CART', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Method returns object with explanation marks for articles in basket.
     *
     * @return \OxidEsales\Eshop\Application\Model\BasketContentMarkGenerator
     */
    public function get_basket_content_mark_generator()
    {
        $session = Registry::get_session();
        return ox_new(Basket_Content_Mark_Generator::class, $session->get_basket());
    }
    /**
     * Sets basket wrapping
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket
     * @param array                                      $aWrapping
     */
    protected function set_wrapping_info($o_basket, $a_wrapping)
    {
        if (is_array($a_wrapping) && count($a_wrapping)) {
            foreach ($o_basket->get_contents() as $s_key => $o_basket_item) {
                if (isset($a_wrapping[$s_key])) {
                    $o_basket_item->set_wrapping($a_wrapping[$s_key]);
                }
            }
        }
    }
}