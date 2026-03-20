<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Current user order history review.
 * When user is logged in order review fulfils history about user
 * submitted orders. There is some details information, such as
 * ordering date, number, recipient, order status, some base
 * ordered articles information, button to add article to basket.
 * OXID eShop -> MY ACCOUNT -> Newsletter.
 */
class Account_Order_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Count of all articles in list.
     *
     * @var integer
     */
    protected $_i_all_art_cnt = 0;
    /**
     * Number of possible pages.
     *
     * @var integer
     */
    protected $_i_cnt_pages;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/order';
    /**
     * collecting orders
     *
     * @var array
     */
    protected $_a_order_list;
    /**
     * collecting article which ordered
     *
     * @var array
     */
    protected $_a_articles_list;
    /**
     * If user is not logged in - returns name of template AccountOrderController::_sThisLoginTemplate, or if user is
     * already logged in - returns name of template AccountOrderController::_sThisTemplate
     *
     * @return string $_sThisTemplate current template file name
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
     * Template variable getter. Returns orders
     *
     * @return array
     */
    public function get_order_list()
    {
        if ($this->_a_order_list === null) {
            $this->_a_order_list = [];
            // Load user Orderlist
            if ($o_user = $this->get_user()) {
                $i_nrof_cat_articles = (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
                $i_nrof_cat_articles = $i_nrof_cat_articles ?: 1;
                $this->_i_all_art_cnt = $o_user->get_order_count();
                if ($this->_i_all_art_cnt && $this->_i_all_art_cnt > 0) {
                    $this->_a_order_list = $o_user->get_orders($i_nrof_cat_articles, $this->get_act_page());
                    $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $i_nrof_cat_articles);
                }
            }
        }
        return $this->_a_order_list;
    }
    /**
     * Template variable getter. Returns ordered articles
     *
     * @return \OxidEsales\Eshop\Application\Model\ArticleList|false
     */
    public function get_order_article_list()
    {
        if ($this->_a_articles_list === null) {
            // marking as set
            $this->_a_articles_list = false;
            $o_orders_list = $this->get_order_list();
            if ($o_orders_list && $o_orders_list->count()) {
                $this->_a_articles_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
                $this->_a_articles_list->load_order_articles($o_orders_list);
            }
        }
        return $this->_a_articles_list;
    }
    /**
     * Template variable getter. Returns page navigation
     *
     * @return object
     */
    public function get_page_navigation()
    {
        if ($this->_o_page_navigation === null) {
            $this->_o_page_navigation = $this->generate_page_navigation();
        }
        return $this->_o_page_navigation;
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
        $a_path['title'] = Registry::get_lang()->translate_string('ORDER_HISTORY', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}