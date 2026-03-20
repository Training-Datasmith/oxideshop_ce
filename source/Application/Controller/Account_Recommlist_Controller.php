<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Current user recommlist manager.
 * When user is logged in in this manager window he can modify his
 * own recommlists status - remove articles from list or store
 * them to shopping basket, view detail information.
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class Account_Recommlist_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/recommendationlist';
    /**
     * Is recomendation list entry was saved this marker gets value TRUE. Default is FALSE
     *
     * @var bool
     */
    protected $_bl_saved_entry = false;
    /**
     * returns the recomm list articles
     *
     * @var object
     */
    protected $_o_act_recomm_list_articles;
    /**
     * returns the recomm list article. Whether the variable is empty, it list nothing
     *
     * @var array
     */
    protected $_a_user_recomm_lists;
    /**
     * returns the recomm list articles
     *
     * @var object
     */
    protected $_o_act_recomm_list;
    /**
     * List items count
     *
     * @var int
     */
    protected $_i_all_art_cnt = 0;
    /**
     * Page navigation
     *
     * @var object
     */
    protected $_o_page_navigation;
    /**
     * If user is logged in loads his wishlist articles (articles may be accessed by
     * \OxidEsales\Eshop\Application\Model\User::GetBasket()), loads similar articles (is available) for the last
     * article in list loaded by \OxidEsales\Eshop\Application\Model\Article::GetSimilarProducts() and returns name of
     * template to render \OxidEsales\Eshop\Application\Controller\AccountWishlistController::_sThisTemplate
     *
     * @return  string  $_sThisTemplate current template file name
     */
    public function render()
    {
        parent::render();
        // is logged in ?
        if (!$o_user = $this->get_user()) {
            return $this->_s_this_template = $this->_s_this_login_template;
        }
        $o_lists = $this->get_recomm_lists();
        $o_act_list = $this->get_active_recomm_list();
        // list of found oxrecommlists
        if (!$o_act_list && $o_lists->count()) {
            $this->_i_all_art_cnt = $o_user->get_recomm_lists_count();
            $i_nrof_cat_articles = (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
            $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
            $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $i_nrof_cat_articles);
        }
        return $this->_s_this_template;
    }
    /**
     * Returns array of params => values which are used in hidden forms and as additional url params
     *
     * @return array
     */
    public function get_navigation_params()
    {
        $a_params = parent::get_navigation_params();
        // adding recommendation list id to list product urls
        if ($o_list = $this->get_active_recomm_list()) {
            $a_params['recommid'] = $o_list->get_id();
        }
        return $a_params;
    }
    /**
     * return recomm list from the user
     *
     * @return array
     */
    public function get_recomm_lists()
    {
        if ($this->_a_user_recomm_lists === null) {
            $this->_a_user_recomm_lists = false;
            if ($o_user = $this->get_user()) {
                // recommendation list
                $this->_a_user_recomm_lists = $o_user->get_user_recomm_lists();
            }
        }
        return $this->_a_user_recomm_lists;
    }
    /**
     * return all articles in the recomm list
     */
    public function get_article_list()
    {
        if ($this->_o_act_recomm_list_articles === null) {
            $this->_o_act_recomm_list_articles = false;
            if ($o_recomm_list = $this->get_active_recomm_list()) {
                $o_item_list = $o_recomm_list->get_articles();
                if ($o_item_list->count()) {
                    foreach ($o_item_list as $key => $o_item) {
                        if (!$o_item->is_visible()) {
                            $o_recomm_list->remove_article($o_item->get_id());
                            $o_item_list->offsetUnset($key);
                            continue;
                        }
                        $o_item->text = $o_recomm_list->get_art_description($o_item->get_id());
                    }
                    $this->_o_act_recomm_list_articles = $o_item_list;
                }
            }
        }
        return $this->_o_act_recomm_list_articles;
    }
    /**
     * return the active entrys
     */
    public function get_active_recomm_list()
    {
        if (!$this->get_view_config()->get_show_listmania()) {
            return false;
        }
        if ($this->_o_act_recomm_list === null) {
            $this->_o_act_recomm_list = false;
            if (($o_user = $this->get_user()) && $s_recomm_id = Registry::get_request()->get_request_escaped_parameter('recommid')) {
                $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
                $s_user_id_field = 'oxrecommlists__oxuserid';
                if ($o_recomm_list->load($s_recomm_id) && $o_user->get_id() === $o_recomm_list->{$s_user_id_field}->value) {
                    $this->_o_act_recomm_list = $o_recomm_list;
                }
            }
        }
        return $this->_o_act_recomm_list;
    }
    /**
     * Set active recommlist
     *
     * @param object $oRecommList Recommendation list
     */
    public function set_active_recomm_list($o_recomm_list): void
    {
        $this->_o_act_recomm_list = $o_recomm_list;
    }
    /**
     * add new recommlist
     */
    public function save_recomm_list(): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if (!$this->get_view_config()->get_show_listmania()) {
            return;
        }
        if ($o_user = $this->get_user()) {
            if (!$o_recomm_list = $this->get_active_recomm_list()) {
                $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
                $o_recomm_list->oxrecommlists__oxuserid = new \Oxid_Esales\Eshop\Core\Field($o_user->get_id());
                $o_recomm_list->oxrecommlists__oxshopid = new \Oxid_Esales\Eshop\Core\Field(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id());
            } else {
                $this->_s_this_template = 'page/account/recommendationedit';
            }
            $s_title = trim((string) Registry::get_request()->get_request_parameter('recomm_title'));
            $s_author = trim((string) Registry::get_request()->get_request_parameter('recomm_author'));
            $s_text = trim((string) Registry::get_request()->get_request_parameter('recomm_desc'));
            $o_recomm_list->oxrecommlists__oxtitle = new Field($s_title);
            $o_recomm_list->oxrecommlists__oxauthor = new Field($s_author);
            $o_recomm_list->oxrecommlists__oxdesc = new Field($s_text);
            try {
                // marking entry as saved
                $this->_bl_saved_entry = (bool) $o_recomm_list->save();
                $this->set_active_recomm_list($this->_bl_saved_entry ? $o_recomm_list : false);
            } catch (\Oxid_Esales\Eshop\Core\Exception\Object_Exception $o_ex) {
                //add to display at specific position
                Registry::get_utils_view()->add_error_to_display($o_ex, false, true, 'user');
            }
        }
    }
    /**
     * List entry saving status getter. Saving status is
     *
     * @return bool
     */
    public function is_saved_list()
    {
        return $this->_bl_saved_entry;
    }
    /**
     * Delete recommlist
     */
    public function edit_list(): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if (!$this->get_view_config()->get_show_listmania()) {
            return;
        }
        // deleting on demand
        if (($s_action = Registry::get_request()->get_request_escaped_parameter('deleteList')) && $o_recomm_list = $this->get_active_recomm_list()) {
            $o_recomm_list->delete();
            $this->set_active_recomm_list(false);
        } else {
            $this->_s_this_template = 'page/account/recommendationedit';
        }
    }
    /**
     * Delete recommlist
     */
    public function remove_article(): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if (!$this->get_view_config()->get_show_listmania()) {
            return;
        }
        if (($s_art_id = Registry::get_request()->get_request_escaped_parameter('aid')) && $o_recomm_list = $this->get_active_recomm_list()) {
            $o_recomm_list->remove_article($s_art_id);
        }
        $this->_s_this_template = 'page/account/recommendationedit';
    }
    /**
     * Template variable getter. Returns page navigation
     *
     * @return object
     */
    public function get_page_navigation()
    {
        if ($this->_o_page_navigation === null) {
            $this->_o_page_navigation = false;
            if (!$this->get_active_recommlist()) {
                $this->_o_page_navigation = $this->generate_page_navigation();
            }
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
        $a_path['title'] = Registry::get_lang()->translate_string('LISTMANIA', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Article count getter
     *
     * @return int
     */
    public function get_article_count()
    {
        return $this->_i_all_art_cnt;
    }
}