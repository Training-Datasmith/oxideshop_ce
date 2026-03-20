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
 * Article suggestion page.
 * Collects some article base information, sets default recomendation text,
 * sends suggestion mail to user.
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class Recomm_List_Controller extends \Oxid_Esales\Eshop\Application\Controller\Article_List_Controller
{
    /**
     * List type
     *
     * @var string
     */
    protected $_s_list_type = 'recommlist';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/recommendations/recommlist';
    /**
     * Other recommendations list
     */
    protected $_o_other_recomm_list;
    /**
     * Recommlist reviews
     *
     * @var array
     */
    protected $_a_reviews;
    /**
     * Can user rate
     *
     * @var bool
     */
    protected $_bl_rate;
    /**
     * Rating value
     *
     * @var double
     */
    protected $_d_rating_value;
    /**
     * Rating count
     *
     * @var integer
     */
    protected $_i_rating_cnt;
    /**
     * Searched recommendations list
     *
     * @var object
     */
    protected $_o_search_recomm_lists;
    /**
     * Search string
     *
     * @var string
     */
    protected $_s_search;
    /**
     * Template location
     *
     * @var string
     */
    protected $_s_tpl_location;
    /**
     * Page navigation
     *
     * @var object
     */
    protected $_o_page_navigation;
    /**
     * Collects current view data, return current template file name
     *
     * @return string
     */
    public function render()
    {
        \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::render();
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $this->_i_all_art_cnt = 0;
        if ($o_active_recomm_list = $this->get_active_recomm_list()) {
            if (($o_list = $this->get_article_list()) && $o_list->count()) {
                $this->_i_all_art_cnt = $o_active_recomm_list->get_art_count();
            }
        } else if (($o_list = $this->get_recomm_lists()) && $o_list->count()) {
            $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
            $this->_i_all_art_cnt = $o_recomm_list->get_search_recomm_list_count($this->get_recomm_search());
        }
        if (!$o_list = $this->get_article_list()) {
            $o_list = $this->get_recomm_lists();
        }
        if ($o_list && $o_list->count()) {
            $i_nrof_cat_articles = (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
            $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
            $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $i_nrof_cat_articles);
        }
        // processing list articles
        $this->process_list_articles();
        return $this->_s_this_template;
    }
    /**
     * Returns product link type (OXARTICLE_LINKTYPE_RECOMM)
     *
     * @return int
     */
    protected function get_product_link_type()
    {
        return OXARTICLE_LINKTYPE_RECOMM;
    }
    /**
     * Returns additional URL parameters which must be added to list products dynamic urls
     *
     * @return string
     */
    public function get_add_url_params()
    {
        $s_add_params = parent::get_add_url_params();
        $s_add_params .= ($s_add_params ? '&amp;' : '') . "listtype={$this->_s_list_type}";
        if ($o_recomm_list = $this->get_active_recomm_list()) {
            $s_add_params .= '&amp;recommid=' . $o_recomm_list->get_id();
        }
        return $s_add_params;
    }
    /**
     * Returns additional URL parameters which must be added to list products seo urls
     *
     * @return string
     */
    public function get_add_seo_url_params()
    {
        $s_add_params = parent::get_add_seo_url_params();
        if ($s_param = Registry::get_request()->get_request_parameter('searchrecomm')) {
            $s_add_params .= '&amp;searchrecomm=' . rawurlencode((string) $s_param);
        }
        return $s_add_params;
    }
    /**
     * Saves user ratings and review text (oxreview object)
     */
    public function save_review(): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if ($this->can_accept_form_data() && ($o_recomm_list = $this->get_active_recomm_list()) && $o_user = $this->get_user()) {
            //save rating
            $d_rating = Registry::get_request()->get_request_escaped_parameter('recommlistrating');
            if ($d_rating !== null) {
                $d_rating = (int) $d_rating;
            }
            if ($d_rating !== null && $d_rating >= 1 && $d_rating <= 5) {
                $o_rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
                if ($o_rating->allow_rating($o_user->get_id(), 'oxrecommlist', $o_recomm_list->get_id())) {
                    $o_rating->oxratings__oxuserid = new Field($o_user->get_id());
                    $o_rating->oxratings__oxtype = new Field('oxrecommlist');
                    $o_rating->oxratings__oxobjectid = new Field($o_recomm_list->get_id());
                    $o_rating->oxratings__oxrating = new Field($d_rating);
                    $o_rating->save();
                    $o_recomm_list->add_to_rating_average($d_rating);
                }
            }
            if ($s_review_text = trim((string) Registry::get_request()->get_request_parameter('rvw_txt'))) {
                $o_review = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
                $o_review->oxreviews__oxobjectid = new Field($o_recomm_list->get_id());
                $o_review->oxreviews__oxtype = new Field('oxrecommlist');
                $o_review->oxreviews__oxtext = new Field($s_review_text, Field::T_RAW);
                $o_review->oxreviews__oxlang = new Field(Registry::get_lang()->get_base_language());
                $o_review->oxreviews__oxuserid = new Field($o_user->get_id());
                $o_review->oxreviews__oxrating = new Field($d_rating ?? null);
                $o_review->save();
            }
        }
    }
    /**
     * Returns array of params => values which are used in hidden forms and as additional url params
     *
     * @return array
     */
    public function get_navigation_params()
    {
        $a_params = \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::get_navigation_params();
        $a_params['recommid'] = Registry::get_request()->get_request_escaped_parameter('recommid');
        return $a_params;
    }
    /**
     * Template variable getter. Returns category's article list
     *
     * @return array
     */
    public function get_article_list()
    {
        if ($this->_a_article_list === null) {
            $this->_a_article_list = false;
            if ($o_active_recomm_list = $this->get_active_recomm_list()) {
                // sets active page
                $i_act_page = (int) Registry::get_request()->get_request_escaped_parameter('pgNr');
                $i_act_page = $i_act_page < 0 ? 0 : $i_act_page;
                // load only lists which we show on screen
                $i_nrof_cat_articles = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
                $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
                $this->_a_article_list = $o_active_recomm_list->get_articles($i_nrof_cat_articles * $i_act_page, $i_nrof_cat_articles);
                if ($this->_a_article_list && $this->_a_article_list->count()) {
                    foreach ($this->_a_article_list as $o_item) {
                        $o_item->text = $o_active_recomm_list->get_art_description($o_item->get_id());
                    }
                }
            }
        }
        return $this->_a_article_list;
    }
    /**
     * Template variable getter. Returns other recommlists
     *
     * @return object
     */
    public function get_similar_recomm_lists()
    {
        if ($this->_o_other_recomm_list === null) {
            $this->_o_other_recomm_list = false;
            if (($o_active_recomm_list = $this->get_active_recomm_list()) && $o_list = $this->get_article_list()) {
                $o_recomm_lists = $o_active_recomm_list->get_recomm_lists_by_ids($o_list->array_keys());
                //do not show the same list
                unset($o_recomm_lists[$o_active_recomm_list->get_id()]);
                $this->_o_other_recomm_list = $o_recomm_lists;
            }
        }
        return $this->_o_other_recomm_list;
    }
    /**
     * Template variable getter. Returns recommlist's reviews
     *
     * @return array
     */
    public function get_reviews()
    {
        if ($this->_a_reviews === null) {
            $this->_a_reviews = false;
            if ($this->is_review_active() && $o_active_recomm_list = $this->get_active_recomm_list()) {
                $this->_a_reviews = $o_active_recomm_list->get_reviews();
            }
        }
        return $this->_a_reviews;
    }
    /**
     * Template variable getter. Returns if review module is on
     *
     * @return bool
     */
    public function is_review_active()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadReviews');
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
            if ($this->is_review_active() && $o_active_recomm_list = $this->get_active_recomm_list()) {
                $o_rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
                $s_user_variable = Registry::get_session()->get_variable('usr');
                $this->_bl_rate = $o_rating->allow_rating($s_user_variable, 'oxrecommlist', $o_active_recomm_list->get_id());
            }
        }
        return $this->_bl_rate;
    }
    /**
     * Template variable getter. Returns rating value
     *
     * @return double
     */
    public function get_rating_value()
    {
        if ($this->_d_rating_value === null) {
            $this->_d_rating_value = 0.0;
            if ($this->is_review_active() && $o_active_recomm_list = $this->get_active_recomm_list()) {
                $this->_d_rating_value = round($o_active_recomm_list->oxrecommlists__oxrating->value, 1);
            }
        }
        return (float) $this->_d_rating_value;
    }
    /**
     * Template variable getter. Returns rating count
     *
     * @return integer
     */
    public function get_rating_count()
    {
        if ($this->_i_rating_cnt === null) {
            $this->_i_rating_cnt = false;
            if ($this->is_review_active() && $o_active_recomm_list = $this->get_active_recomm_list()) {
                $this->_i_rating_cnt = $o_active_recomm_list->oxrecommlists__oxratingcnt->value;
            }
        }
        return $this->_i_rating_cnt;
    }
    /**
     * Template variable getter. Returns searched recommlist
     *
     * @return object
     */
    public function get_recomm_lists()
    {
        if ($this->_o_search_recomm_lists === null) {
            $this->_o_search_recomm_lists = [];
            if (!$this->get_active_recomm_list()) {
                // list of found oxrecommlists
                $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
                $o_list = $o_recomm_list->get_search_recomm_lists($this->get_recomm_search());
                if ($o_list && $o_list->count()) {
                    $this->_o_search_recomm_lists = $o_list;
                }
            }
        }
        return $this->_o_search_recomm_lists;
    }
    /**
     * Template variable getter. Returns search string
     *
     * @return string
     */
    public function get_recomm_search()
    {
        if ($this->_s_search === null) {
            $this->_s_search = false;
            if ($s_search = Registry::get_request()->get_request_escaped_parameter('searchrecomm')) {
                $this->_s_search = $s_search;
            }
        }
        return $this->_s_search;
    }
    /**
     * Template variable getter. Returns category path array
     *
     * @return array
     */
    public function get_tree_path()
    {
        $o_lang = Registry::get_lang();
        $a_path[0] = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $a_path[0]->set_link(false);
        $a_path[0]->oxcategories__oxtitle = new Field($o_lang->translate_string('RECOMMLIST'));
        if ($s_search_param = $this->get_recomm_search()) {
            $shop_home_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_home_url();
            $s_url = $shop_home_url . 'cl=recommlist&amp;searchrecomm=' . rawurlencode($s_search_param);
            $s_title = $o_lang->translate_string('RECOMMLIST_SEARCH') . ' "' . $s_search_param . '"';
            $a_path[1] = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            $a_path[1]->set_link($s_url);
            $a_path[1]->oxcategories__oxtitle = new Field($s_title);
        }
        return $a_path;
    }
    /**
     * Template variable getter. Returns search string
     *
     * @return string
     */
    public function get_search_for_html()
    {
        // #M1450 if active recommlist is loaded return it's title
        if ($o_active_recomm_list = $this->get_active_recomm_list()) {
            return $o_active_recomm_list->oxrecommlists__oxtitle->value;
        }
        return Registry::get_request()->get_request_escaped_parameter('searchrecomm');
    }
    /**
     * Generates Url for page navigation
     *
     * @return string
     */
    public function generate_page_navigation_url()
    {
        if (Registry::get_utils()->seo_is_active() && $o_recomm = $this->get_active_recomm_list()) {
            return $o_recomm->get_link();
        }
        return \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::generate_page_navigation_url();
    }
    /**
     * Adds page number parameter to current Url and returns formatted url
     *
     * @param string $sUrl  url to append page numbers
     * @param int    $iPage current page number
     * @param int    $iLang requested language
     *
     * @return string
     */
    protected function add_page_nr_param($s_url, $i_page, $i_lang = null)
    {
        if (Registry::get_utils()->seo_is_active() && $o_recomm = $this->get_active_recomm_list()) {
            if ($i_page) {
                // only if page number > 0
                $s_url = $o_recomm->get_base_seo_link($i_lang, $i_page);
            }
        } else {
            $s_url = \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::add_page_nr_param($s_url, $i_page, $i_lang);
        }
        return $s_url;
    }
    /**
     * Template variable getter. Returns additional params for url
     *
     * @return string
     */
    public function get_additional_params()
    {
        $s_add_params = \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::get_additional_params();
        if ($o_recomm = $this->get_active_recomm_list()) {
            $s_add_params .= '&amp;recommid=' . $o_recomm->get_id();
        }
        if ($s_search = $this->get_recomm_search()) {
            $s_add_params .= '&amp;searchrecomm=' . rawurlencode($s_search);
        }
        return $s_add_params;
    }
    /**
     * get link of current view
     *
     * @param int $iLang requested language
     *
     * @return string
     */
    public function get_link($i_lang = null)
    {
        if ($o_recomm = $this->get_active_recomm_list()) {
            $s_link = $o_recomm->get_link($i_lang);
        } else {
            $s_link = \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::get_link($i_lang);
        }
        $s_search = Registry::get_request()->get_request_escaped_parameter('searchrecomm');
        if ($s_search) {
            $s_link .= (!str_contains($s_link, '?') ? '?' : '&amp;') . "searchrecomm={$s_search}";
        }
        return $s_link;
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
        $a_path['title'] = Registry::get_lang()->translate_string('LISTMANIA', $i_base_language, false);
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
        if ($a_active_list = $this->get_active_recomm_list()) {
            $s_translated_string = $o_lang->translate_string('LIST_BY', $o_lang->get_base_language(), false);
            $s_title_field = 'oxrecommlists__oxtitle';
            $s_author_field = 'oxrecommlists__oxauthor';
            return $a_active_list->{$s_title_field}->value . ' (' . $s_translated_string . ' ' . $a_active_list->{$s_author_field}->value . ')';
        }
        $s_translated_string = $o_lang->translate_string('HITS_FOR', $o_lang->get_base_language(), false);
        return $this->get_article_count() . ' ' . $s_translated_string . ' "' . $this->get_search_for_html() . '"';
    }
}