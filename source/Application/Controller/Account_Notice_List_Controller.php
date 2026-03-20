<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

/**
 * Current user notice list manager.
 * When user is logged in in this manager window he can modify
 * his notice list status - remove articles from notice list or
 * store them to shopping basket, view detail information.
 * OXID eShop -> MY ACCOUNT -> Newsletter.
 */
class Account_Notice_List_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/noticelist';
    /**
     * Check if there is an product in the noticelist.
     *
     * @var array
     */
    protected $_a_notice_product_list;
    /**
     * return the similar prodcuts from the notice list.
     *
     * @var array
     */
    protected $_a_similar_product_list;
    /**
     * return the recommlist
     *
     * @var array
     */
    protected $_a_recomm_list;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Array of id to form recommendation list.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * If user is not logged in - returns name of template
     * \OxidEsales\Eshop\Application\Controller\AccountNoticeListController::_sThisLoginTemplate, or if user is already
     * logged in - loads notice list articles (articles may be accessed
     * by \OxidEsales\Eshop\Application\Model\User::getBasket()), loads similar articles (if available) for
     * the last article in list \OxidEsales\Eshop\Application\Model\Article::GetSimilarProducts() and returns name of
     * template to render AccountNoticeListController::_sThisTemplate
     *
     * @return string current template file name
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
     * Template variable getter. Returns an array if there is something in the list
     *
     * @return array
     */
    public function get_notice_product_list()
    {
        if ($this->_a_notice_product_list === null) {
            if ($o_user = $this->get_user()) {
                $this->_a_notice_product_list = $o_user->get_basket('noticelist')->get_articles();
            }
        }
        return $this->_a_notice_product_list;
    }
    /**
     * Template variable getter. Returns the products which are in the noticelist
     *
     * @return array
     */
    public function get_similar_products()
    {
        // similar products list
        if ($this->_a_similar_product_list === null && count($this->get_notice_product_list())) {
            // just ensuring that next call will skip this check
            $this->_a_similar_product_list = false;
            // loading similar products
            if ($o_similar_prod = current($this->get_notice_product_list())) {
                $this->_a_similar_product_list = $o_similar_prod->get_similar_products();
            }
        }
        return $this->_a_similar_product_list;
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
            $a_notice_prod_list = $this->get_notice_product_list();
            if (is_array($a_notice_prod_list) && count($a_notice_prod_list)) {
                $this->_a_similar_recomm_list_ids = array_keys($a_notice_prod_list);
            }
        }
        return $this->_a_similar_recomm_list_ids;
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
        $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $s_self_link = $this->get_view_config()->get_self_link();
        $i_base_language = $o_lang->get_base_language();
        $a_path['title'] = $o_lang->translate_string('MY_ACCOUNT', $i_base_language, false);
        $a_path['link'] = \Oxid_Esales\Eshop\Core\Registry::get_seo_encoder()->get_static_url($s_self_link . 'cl=account');
        $a_paths[] = $a_path;
        $a_path['title'] = $o_lang->translate_string('MY_WISH_LIST', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}