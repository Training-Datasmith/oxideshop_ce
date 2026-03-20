<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Application\Model\Wrapping;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Managing Gift Wrapping
 */
class Wrapping_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/checkout/wrapping';
    /**
     * Basket items array
     *
     * @var array
     */
    protected $_a_basket_item_list;
    /**
     * Wrapping objects list
     */
    protected $_o_wrappings;
    /**
     * Card objects list
     */
    protected $_o_cards;
    /**
     * Returns array of shopping basket articles
     *
     * @return array
     */
    public function get_basket_items()
    {
        if ($this->_a_basket_item_list === null) {
            $this->_a_basket_item_list = false;
            // passing basket articles
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            if ($o_basket = $session->get_basket()) {
                $this->_a_basket_item_list = $o_basket->get_basket_articles();
            }
        }
        return $this->_a_basket_item_list;
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
     * basket back to session (\OxidEsales\Eshop\Core\Session::setBasket()). Returns
     * "order" to redirect to order confirmation secreen.
     *
     * @return string
     */
    public function change_wrapping()
    {
        $a_wrapping = Registry::get_request()->get_request_escaped_parameter('wrapping');
        if ($this->get_view_config()->get_show_gift_wrapping()) {
            $session = Registry::get_session();
            $o_basket = $session->get_basket();
            // setting wrapping info
            if (is_array($a_wrapping) && count($a_wrapping)) {
                foreach ($o_basket->get_contents() as $s_key => $o_basket_item) {
                    // wrapping ?
                    if (isset($a_wrapping[$s_key])) {
                        $o_basket_item->set_wrapping($a_wrapping[$s_key]);
                    }
                }
            }
            $o_basket->set_card_message(Registry::get_request()->get_request_escaped_parameter('giftmessage'));
            $o_basket->set_card_id(Registry::get_request()->get_request_escaped_parameter('chosencard'));
            $o_basket->on_update();
        }
        return 'order';
    }
}