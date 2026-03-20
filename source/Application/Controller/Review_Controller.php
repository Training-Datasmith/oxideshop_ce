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
 * Review of chosen article.
 * Collects article review data, saves new review to DB.
 */
class Review_Controller extends \Oxid_Esales\Eshop\Application\Controller\Article_Details_Controller
{
    /**
     * Review user object
     */
    protected $_o_rev_user;
    /**
     * Active object ($_oProduct or $_oActiveRecommList)
     *
     * @var object
     */
    protected $_o_act_object;
    /**
     * Active recommendations list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_o_active_recomm_list;
    /**
     * Active recommlist's items
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_o_active_recomm_items;
    /**
     * Can user rate
     *
     * @var bool
     */
    protected $_bl_rate;
    /**
     * Array of reviews
     *
     * @var array
     */
    protected $_a_reviews;
    /**
     * CrossSelling articlelist
     *
     * @var object
     */
    protected $_o_cross_selling;
    /**
     * Similar products articlelist
     *
     * @var object
     */
    protected $_o_similar_products;
    /**
     * Recommlist
     *
     * @var object
     */
    protected $_o_recomm_list;
    /**
     * Review send status
     *
     * @var bool
     */
    protected $_bl_review_send_status;
    /**
     * Page navigation
     *
     * @var object
     */
    protected $_o_page_navigation;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/review/review';
    /**
     * Current class login template name.
     *
     * @var string
     */
    protected $_s_this_login_template = 'page/review/review_login';
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Returns prefix ID used by template engine.
     *
     * @return  string  $this->_sViewID view id
     */
    public function generate_view_id()
    {
        return \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::generate_view_id();
    }
    /**
     * Executes parent::init(), Loads user chosen product object (with all data).
     */
    public function init(): void
    {
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if (Registry::get_request()->get_request_escaped_parameter('recommid') && !$this->get_active_recomm_list()) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->redirect(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_home_url(), true, 302);
        }
        // END deprecated
        \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::init();
    }
    /**
     * Executes parent::render, loads article reviews and additional data
     * (\OxidEsales\Eshop\Application\Model\Article::getReviews(),
     * \OxidEsales\Eshop\Application\Model\Article::getCrossSelling(),
     * \OxidEsales\Eshop\Application\Model\Article::GetSimilarProducts()). Returns name of template file to
     * render review::_sThisTemplate.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if (!$o_config->get_config_param('bl_perfLoadReviews')) {
            Registry::get_utils()->redirect($o_config->get_shop_home_url());
        }
        \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::render();
        if (!$this->get_review_user()) {
            $this->_s_this_template = $this->_s_this_login_template;
        } else {
            // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            $o_active_recomm_list = $this->get_active_recomm_list();
            $o_list = $this->get_active_recomm_items();
            if ($o_active_recomm_list) {
                if ($o_list && $o_list->count()) {
                    $this->_i_all_art_cnt = $o_active_recomm_list->get_art_count();
                }
                // load only lists which we show on screen
                $i_nrof_cat_articles = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
                $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
                $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $i_nrof_cat_articles);
            }
            // END deprecated
        }
        return $this->_s_this_template;
    }
    /**
     * Saves user review text (oxreview object)
     */
    public function save_review(): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if (($o_rev_user = $this->get_review_user()) && $this->can_accept_form_data()) {
            if (($o_act_object = $this->get_active_object()) && $s_type = $this->get_active_type()) {
                if (($d_rating = Registry::get_request()->get_request_escaped_parameter('rating')) === null) {
                    $d_rating = Registry::get_request()->get_request_escaped_parameter('artrating');
                }
                if ($d_rating !== null) {
                    $d_rating = (int) $d_rating;
                }
                //save rating
                if ($d_rating !== null && $d_rating >= 1 && $d_rating <= 5) {
                    $o_rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
                    if ($o_rating->allow_rating($o_rev_user->get_id(), $s_type, $o_act_object->get_id())) {
                        $o_rating->oxratings__oxuserid = new Field($o_rev_user->get_id());
                        $o_rating->oxratings__oxtype = new Field($s_type);
                        $o_rating->oxratings__oxobjectid = new Field($o_act_object->get_id());
                        $o_rating->oxratings__oxrating = new Field($d_rating);
                        $o_rating->save();
                        $o_act_object->add_to_rating_average($d_rating);
                        $this->_bl_review_send_status = true;
                    }
                }
                if ($s_review_text = trim((string) Registry::get_request()->get_request_parameter('rvw_txt'))) {
                    $o_review = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
                    $o_review->oxreviews__oxobjectid = new Field($o_act_object->get_id());
                    $o_review->oxreviews__oxtype = new Field($s_type);
                    $o_review->oxreviews__oxtext = new Field($s_review_text, Field::T_RAW);
                    $o_review->oxreviews__oxlang = new Field(Registry::get_lang()->get_base_language());
                    $o_review->oxreviews__oxuserid = new Field($o_rev_user->get_id());
                    $o_review->oxreviews__oxrating = new Field($d_rating ?? null);
                    $o_review->save();
                    $this->_bl_review_send_status = true;
                }
            }
        }
    }
    /**
     * Returns review user object
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    public function get_review_user()
    {
        if ($this->_o_rev_user === null) {
            $this->_o_rev_user = false;
            $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            if ($s_user_id = $o_user->get_review_user_id($this->get_review_user_hash())) {
                // review user, by link or other source?
                if ($o_user->load($s_user_id)) {
                    $this->_o_rev_user = $o_user;
                }
            } elseif ($o_user = $this->get_user()) {
                // session user?
                $this->_o_rev_user = $o_user;
            }
        }
        return $this->_o_rev_user;
    }
    /**
     * Template variable getter. Returns review user id
     *
     * @return string
     */
    public function get_review_user_hash()
    {
        return Registry::get_request()->get_request_escaped_parameter('reviewuserhash');
    }
    /**
     * Template variable getter. Returns active object (oxarticle or oxrecommlist)
     *
     * @return object
     */
    protected function get_active_object()
    {
        if ($this->_o_act_object === null) {
            $this->_o_act_object = false;
            if ($o_product = $this->get_product()) {
                $this->_o_act_object = $o_product;
                // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            } elseif ($o_recomm_list = $this->get_active_recomm_list()) {
                $this->_o_act_object = $o_recomm_list;
                // END deprecated
            }
        }
        return $this->_o_act_object;
    }
    /**
     * Template variable getter. Returns active type (oxarticle or oxrecommlist)
     *
     * @return string
     */
    protected function get_active_type()
    {
        if ($this->get_product()) {
            return 'oxarticle';
            // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        } elseif ($this->get_active_recomm_list()) {
            return 'oxrecommlist';
            // END deprecated
        }
    }
    /**
     * Template variable getter. Returns active recommlist
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return \OxidEsales\Eshop\Application\Model\RecommendationList|false
     */
    public function get_active_recomm_list()
    {
        if (!$this->get_view_config()->get_show_listmania()) {
            return false;
        }
        if ($this->_o_active_recomm_list === null) {
            $this->_o_active_recomm_list = false;
            if ($s_recomm_id = Registry::get_request()->get_request_escaped_parameter('recommid')) {
                $o_active_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
                if ($o_active_recomm_list->load($s_recomm_id)) {
                    $this->_o_active_recomm_list = $o_active_recomm_list;
                }
            }
        }
        return $this->_o_active_recomm_list;
    }
    /**
     * Template variable getter. Returns if user can rate
     *
     * @return bool
     */
    public function can_rate()
    {
        if ($this->_bl_rate === null) {
            $this->_bl_rate = false;
            if (($o_act_object = $this->get_active_object()) && $o_rev_user = $this->get_review_user()) {
                $o_rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
                $this->_bl_rate = $o_rating->allow_rating($o_rev_user->get_id(), $this->get_active_type(), $o_act_object->get_id());
            }
        }
        return $this->_bl_rate;
    }
    /**
     * Template variable getter. Returns active object's reviews
     *
     * @return array
     */
    public function get_reviews()
    {
        if ($this->_a_reviews === null) {
            $this->_a_reviews = false;
            if ($o_object = $this->get_active_object()) {
                $this->_a_reviews = $o_object->get_reviews();
            }
        }
        return $this->_a_reviews;
    }
    /**
     * Template variable getter. Returns recommlists
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return object
     */
    public function get_recomm_list()
    {
        if ($this->_o_recomm_list === null) {
            $this->_o_recomm_list = false;
            if ($o_product = $this->get_product()) {
                $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
                $this->_o_recomm_list = $o_recomm_list->get_recomm_lists_by_ids([$o_product->get_id()]);
            }
        }
        return $this->_o_recomm_list;
    }
    /**
     * Template variable getter. Returns active recommlist's items
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return object
     */
    public function get_active_recomm_items()
    {
        if ($this->_o_active_recomm_items === null) {
            $this->_o_active_recomm_items = false;
            if ($o_active_recomm_list = $this->get_active_recomm_list()) {
                // sets active page
                $i_act_page = (int) Registry::get_request()->get_request_escaped_parameter('pgNr');
                $i_act_page = $i_act_page < 0 ? 0 : $i_act_page;
                // load only lists which we show on screen
                $i_nrof_cat_articles = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
                $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
                $o_list = $o_active_recomm_list->get_articles($i_nrof_cat_articles * $i_act_page, $i_nrof_cat_articles);
                if ($o_list && $o_list->count()) {
                    foreach ($o_list as $o_item) {
                        $o_item->text = $o_active_recomm_list->get_art_description($o_item->get_id());
                    }
                    $this->_o_active_recomm_items = $o_list;
                }
            }
        }
        return $this->_o_active_recomm_items;
    }
    /**
     * Template variable getter. Returns review send status
     *
     * @return bool
     */
    public function get_review_send_status()
    {
        return $this->_bl_review_send_status;
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
            // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            if ($this->get_active_recomm_list()) {
                $this->_o_page_navigation = $this->generate_page_navigation();
            }
            // END deprecated
        }
        return $this->_o_page_navigation;
    }
    /**
     * Template variable getter. Returns additional params for url
     *
     * @return string
     */
    public function get_additional_params()
    {
        $s_add_params = \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::get_additional_params();
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if ($o_act_recomm_list = $this->get_active_recomm_list()) {
            $s_add_params .= '&amp;recommid=' . $o_act_recomm_list->get_id();
        }
        // END deprecated
        return $s_add_params;
    }
    /**
     * returns additional url params for dynamic url building
     *
     * @return string
     */
    public function get_dyn_url_params()
    {
        $s_params = parent::get_dyn_url_params();
        if ($s_cn_id = Registry::get_request()->get_request_escaped_parameter('cnid')) {
            $s_params .= "&amp;cnid={$s_cn_id}";
        }
        if ($s_an_id = Registry::get_request()->get_request_escaped_parameter('anid')) {
            $s_params .= "&amp;anid={$s_an_id}";
        }
        if ($s_list_type = Registry::get_request()->get_request_escaped_parameter('listtype')) {
            $s_params .= "&amp;listtype={$s_list_type}";
        }
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if ($s_recomm_id = Registry::get_request()->get_request_escaped_parameter('recommid')) {
            $s_params .= "&amp;recommid={$s_recomm_id}";
        }
        // END deprecated
        return $s_params;
    }
}