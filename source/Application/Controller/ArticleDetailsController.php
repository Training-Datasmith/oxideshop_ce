<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Application\Model\Category;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Bridge_Interface;
/**
 * Article details information page.
 * Collects detailed article information, possible variants, such information
 * as crosselling, similarlist, picture gallery list, etc.
 * OXID eShop -> (Any chosen product).
 */
class Article_Details_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class default template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/details/details';
    /**
     * Current product parent article object
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_parent_prod;
    /**
     * Parent article name
     *
     * @var string
     */
    protected $_s_parent_name;
    /**
     * Parent article url
     *
     * @var string
     */
    protected $_s_parent_url;
    /**
     * Picture gallery
     *
     * @var array
     */
    protected $_a_pic_gallery;
    /**
     * Select lists
     *
     * @var array
     */
    protected $_a_select_lists;
    /**
     * Reviews of current article
     *
     * @var array
     */
    protected $_a_reviews;
    /**
     * CrossSelling article list
     *
     * @var object
     */
    protected $_o_cross_selling;
    /**
     * Similar products article list
     *
     * @var object
     */
    protected $_o_similar_products;
    /**
     * Accessories of current article
     *
     * @var object
     */
    protected $_o_accessoires;
    /**
     * List of customer also bought these products
     *
     * @var object
     */
    protected $_a_also_bought_arts;
    /**
     * Search title
     *
     * @var string
     */
    protected $_s_search_title;
    /**
     * Marker if active product was fully initialized before returning it
     * (see details::getProduct())
     *
     * @var bool
     */
    protected $_bl_is_initialized = false;
    /**
     * Current view link type
     *
     * @var int
     */
    protected $_i_link_type;
    /**
     * Bid price.
     *
     * @var string
     */
    protected $_s_bid_price;
    /**
     * Price alarm status.
     *
     * @var integer
     */
    protected $_i_price_alarm_status;
    /**
     * Search parameter for Html
     *
     * @var string
     */
    protected $_s_search_param_for_html;
    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_bl_show_sorting = true;
    /**
     * Returns current product parent article object if it is available
     *
     * @param string $parentId parent product id
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function get_parent_product($parent_id)
    {
        if ($parent_id && $this->_o_parent_prod === null) {
            $this->_o_parent_prod = false;
            $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($article->load($parent_id)) {
                $this->process_product($article);
                $this->_o_parent_prod = $article;
            }
        }
        return $this->_o_parent_prod;
    }
    /**
     * In case list type is "search" returns search parameters which will be added to product details link
     *
     * @return string|null
     */
    protected function get_add_dyn_url_params()
    {
        if ($this->get_list_type() == 'search') {
            return $this->get_dyn_url_params();
        }
    }
    /**
     * Returns array of params => values which are used in hidden forms and as additional url params.
     * NOTICE: this method SHOULD return raw (non encoded into entities) parameters, because values
     * are processed by htmlentities() to avoid security and broken templates problems
     * This exact fix is added for article details to parse variant selection properly for widgets.
     *
     * @return array
     */
    public function get_navigation_params()
    {
        $parameters = parent::get_navigation_params();
        $variant_selection_list_id = Registry::get_request()->get_request_escaped_parameter('varselid');
        $select_list_parameters = Registry::get_request()->get_request_escaped_parameter('sel');
        if (!$variant_selection_list_id && !$select_list_parameters) {
            return $parameters;
        }
        if (is_array($variant_selection_list_id)) {
            foreach ($variant_selection_list_id as $key => $value) {
                $parameters["varselid[{$key}]"] = $value;
            }
        }
        if (is_array($select_list_parameters)) {
            foreach ($select_list_parameters as $key => $value) {
                $parameters["sel[{$key}]"] = $value;
            }
        }
        return $parameters;
    }
    /**
     * Processes product by setting link type and in case list type is search adds search parameters to details link
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article Product to process
     */
    protected function process_product($article)
    {
        $article->set_link_type($this->get_link_type());
        if ($dynamic_parameters = $this->get_add_dyn_url_params()) {
            $article->append_link($dynamic_parameters);
        }
    }
    /**
     * Generates current view id.
     *
     * @return string
     */
    protected function generate_view_id()
    {
        return parent::generate_view_id() . '|' . Registry::get_request()->get_request_escaped_parameter('anid') . '|';
    }
    /**
     * If possible loads additional article info (\OxidEsales\Eshop\Application\Model\Article::getCrossSelling(),
     * \OxidEsales\Eshop\Application\Model\Article::getAccessoires(), \OxidEsales\Eshop\Application\Model\Article::getReviews(), \OxidEsales\Eshop\Application\Model\Article::GetSimilarProducts(),
     * \OxidEsales\Eshop\Application\Model\Article::GetCustomerAlsoBoughtThisProducts()), forms variants details
     * navigation URLs
     * loads select lists (\OxidEsales\Eshop\Application\Model\Article::GetSelectLists()), prepares HTML meta data
     * (details::_convertForMetaTags()). Returns name of template file
     * details::_sThisTemplate
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        $article = $this->get_product();
        if ($article->oxarticles__oxtemplate->value) {
            $this->_s_this_template = $article->oxarticles__oxtemplate->value;
        }
        if ($template_name = Registry::get_request()->get_request_escaped_parameter('tpl')) {
            $this->_s_this_template = 'custom/' . basename((string) $template_name);
        }
        parent::render();
        $render_partial = Registry::get_request()->get_request_escaped_parameter('renderPartial');
        $this->add_tpl_param('renderPartial', $render_partial);
        switch ($render_partial) {
            case 'productInfo':
                return 'page/details/ajax/fullproductinfo';
            case 'detailsMain':
                return 'page/details/ajax/productmain';
            default:
                // can not be removed, as it is used for breadcrumb loading
                $locator = ox_new('oxLocator', $this->get_list_type());
                $locator->set_locator_data($article, $this);
                return $this->_s_this_template;
        }
    }
    /**
     * Returns current view meta data
     * If $meta parameter comes empty, sets to it article title and description.
     * It happens if current view has no meta data defined in oxcontent table
     *
     * @param string $meta           User defined description, description content or empty value
     * @param int    $length         Max length of result, -1 for no truncation
     * @param bool   $descriptionTag If true - performs additional duplicate cleaning
     *
     * @return string
     */
    protected function prepare_meta_description($meta, $length = 200, $description_tag = false)
    {
        if (!$meta) {
            $article = $this->get_product();
            $meta = $article->get_long_description()->value;
            if ($meta == '') {
                $meta = $article->oxarticles__oxshortdesc->value;
            }
            $meta = $article->oxarticles__oxtitle->value . ' - ' . $meta;
        }
        return parent::prepare_meta_description($meta, $length, $description_tag);
    }
    /**
     * Returns current view keywords seperated by comma
     * If $keywords parameter comes empty, sets to it article title and description.
     * It happens if current view has no meta data defined in oxcontent table
     *
     * @param string $keywords              User defined keywords, keywords content or empty value
     * @param bool   $removeDuplicatedWords Remove duplicated words
     *
     * @return string
     */
    protected function prepare_meta_keyword($keywords, $remove_duplicated_words = true)
    {
        if (!$keywords) {
            $article = $this->get_product();
            $keywords = trim((string) $this->get_title());
            if ($category_tree = $this->get_category_tree()) {
                foreach ($category_tree->get_path() as $category) {
                    $keywords .= ', ' . trim((string) $category->oxcategories__oxtitle->value);
                }
            }
            // Adding search keys info
            if ($search_keys = trim((string) $article->oxarticles__oxsearchkeys->value)) {
                $keywords .= ', ' . $search_keys;
            }
            $keywords = parent::prepare_meta_keyword($keywords, $remove_duplicated_words);
        }
        return $keywords;
    }
    /**
     * Saves user ratings and review text (oxReview object)
     */
    public function save_review(): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if ($this->can_accept_form_data() && ($user = $this->get_user()) && $article = $this->get_product()) {
            $article_rating = Registry::get_request()->get_request_escaped_parameter('artrating');
            if ($article_rating !== null) {
                $article_rating = (int) $article_rating;
            }
            //save rating
            if ($article_rating !== null && $article_rating >= 1 && $article_rating <= 5) {
                $rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
                if ($rating->allow_rating($user->get_id(), 'oxarticle', $article->get_id())) {
                    $rating->oxratings__oxuserid = new Field($user->get_id());
                    $rating->oxratings__oxtype = new Field('oxarticle');
                    $rating->oxratings__oxobjectid = new Field($article->get_id());
                    $rating->oxratings__oxrating = new Field($article_rating);
                    $rating->save();
                    $article->add_to_rating_average($article_rating);
                }
            }
            if ($review_text = trim((string) Registry::get_request()->get_request_parameter('rvw_txt'))) {
                $review = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
                $review->oxreviews__oxobjectid = new Field($article->get_id());
                $review->oxreviews__oxtype = new Field('oxarticle');
                $review->oxreviews__oxtext = new Field($review_text, Field::T_RAW);
                $review->oxreviews__oxlang = new Field(Registry::get_lang()->get_base_language());
                $review->oxreviews__oxuserid = new Field($user->get_id());
                $review->oxreviews__oxrating = new Field($article_rating ?? 0);
                $review->save();
            }
        }
    }
    /**
     * Adds article to selected recommendation list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     */
    public function add_to_recomm(): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if (!$this->get_view_config()->get_show_listmania()) {
            return;
        }
        $recommendation_text = trim((string) Registry::get_request()->get_request_escaped_parameter('recomm_txt'));
        $recommendation_list_id = Registry::get_request()->get_request_escaped_parameter('recomm');
        $article_id = $this->get_product()->get_id();
        if ($article_id) {
            $recommendation_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
            $recommendation_list->load($recommendation_list_id);
            $recommendation_list->add_article($article_id, $recommendation_text);
        }
    }
    /**
     * Returns active product id to load its seo meta info
     *
     * @return string
     */
    protected function get_seo_object_id()
    {
        if ($article = $this->get_product()) {
            return $article->get_id();
        }
    }
    /**
     * Returns current product
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_product()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($this->_o_product === null) {
            //this option is only for lists and we must reset value
            //as blLoadVariants = false affect "ab price" functionality
            $config->set_config_param('blLoadVariants', true);
            $article_id = Registry::get_request()->get_request_escaped_parameter('anid');
            // object is not yet loaded
            $this->_o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if (!$this->_o_product->load($article_id)) {
                unset($_GET, $_POST);
                $config->drop_last_active_view();
                error_404_handler($_SERVER['REQUEST_URI']);
            }
            $variant_selection_id = Registry::get_request()->get_request_escaped_parameter('varselid');
            $variant_selections = $this->_o_product->get_variant_selections($variant_selection_id);
            if ($variant_selections && $variant_selections['oActiveVariant'] && $variant_selections['blPerfectFit']) {
                $this->_o_product = $variant_selections['oActiveVariant'];
            }
        }
        // additional checks
        if (!$this->_bl_is_initialized) {
            $this->additional_checks_for_article();
        }
        return $this->_o_product;
    }
    /**
     * Runs additional checks for article.
     */
    protected function additional_checks_for_article()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        $should_continue = true;
        if (!$this->_o_product->is_visible()) {
            $should_continue = false;
        } elseif ($this->_o_product->oxarticles__oxparentid->value) {
            $parent_article = $this->get_parent_product($this->_o_product->oxarticles__oxparentid->value);
            if (!$parent_article || !$parent_article->is_visible()) {
                $should_continue = false;
            }
        }
        if (!$should_continue) {
            $utils->redirect($config->get_shop_home_url());
            $utils->show_message_and_exit('');
        }
        $this->process_product($this->_o_product);
        $this->_bl_is_initialized = true;
    }
    /**
     * Returns current view link type
     *
     * @return int
     */
    public function get_link_type()
    {
        if ($this->_i_link_type === null) {
            $list_type = Registry::get_request()->get_request_escaped_parameter('listtype');
            if ('vendor' == $list_type) {
                $this->_i_link_type = OXARTICLE_LINKTYPE_VENDOR;
            } elseif ('manufacturer' == $list_type) {
                $this->_i_link_type = OXARTICLE_LINKTYPE_MANUFACTURER;
                // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            } elseif ('recommlist' == $list_type) {
                $this->_i_link_type = OXARTICLE_LINKTYPE_RECOMM;
                // END deprecated
            } else {
                $this->_i_link_type = OXARTICLE_LINKTYPE_CATEGORY;
                // price category has own type..
                $active_category = $this->get_active_category();
                if ($active_category && $active_category->is_price_category()) {
                    $this->_i_link_type = OXARTICLE_LINKTYPE_PRICECATEGORY;
                }
            }
        }
        return $this->_i_link_type;
    }
    /**
     * Template variable getter. Returns if draw parent url
     *
     * @return bool
     */
    public function draw_parent_url()
    {
        return $this->get_product()->is_variant();
    }
    /**
     * Template variable getter. Returns picture gallery of current article
     *
     * @return array
     */
    public function get_picture_gallery()
    {
        if ($this->_a_pic_gallery === null) {
            //get picture gallery
            $this->_a_pic_gallery = $this->get_pictures_product()->get_picture_gallery();
        }
        return $this->_a_pic_gallery;
    }
    /**
     * Template variable getter. Returns active picture
     *
     * @return object
     */
    public function get_act_picture()
    {
        return $this->get_picture_gallery()['activeMedia']?->get_detail_url();
    }
    /**
     * Template variable getter. Returns selectLists of current article
     *
     * @return array
     */
    public function get_select_lists()
    {
        if ($this->_a_select_lists === null) {
            $this->_a_select_lists = false;
            if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadSelectLists')) {
                $this->_a_select_lists = $this->get_product()->get_select_lists();
            }
        }
        return $this->_a_select_lists;
    }
    /**
     * Template variable getter. Returns reviews of current article
     *
     * @return array
     */
    public function get_reviews()
    {
        if ($this->_a_reviews === null) {
            $this->_a_reviews = false;
            if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadReviews')) {
                $this->_a_reviews = $this->get_product()->get_reviews();
            }
        }
        return $this->_a_reviews;
    }
    /**
     * Template variable getter. Returns cross selling
     *
     * @return object
     */
    public function get_cross_selling()
    {
        if ($this->_o_cross_selling === null) {
            $this->_o_cross_selling = false;
            if ($article = $this->get_product()) {
                $this->_o_cross_selling = $article->get_cross_selling();
            }
        }
        return $this->_o_cross_selling;
    }
    /**
     * Template variable getter. Returns similar article list
     *
     * @return object
     */
    public function get_similar_products()
    {
        if ($this->_o_similar_products === null) {
            $this->_o_similar_products = false;
            if ($article = $this->get_product()) {
                $this->_o_similar_products = $article->get_similar_products();
            }
        }
        return $this->_o_similar_products;
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
            if ($article = $this->get_product()) {
                $this->_a_similar_recomm_list_ids = [$article->get_id()];
            }
        }
        return $this->_a_similar_recomm_list_ids;
    }
    /**
     * Template variable getter. Returns accessories of article
     *
     * @return object
     */
    public function get_accessoires()
    {
        if ($this->_o_accessoires === null) {
            $this->_o_accessoires = false;
            if ($article = $this->get_product()) {
                $this->_o_accessoires = $article->get_accessoires();
            }
        }
        return $this->_o_accessoires;
    }
    /**
     * Template variable getter. Returns list of customer also bought these products
     *
     * @return \OxidEsales\Eshop\Application\Model\ArticleList|false
     */
    public function get_also_bought_these_products()
    {
        if ($this->_a_also_bought_arts === null) {
            $this->_a_also_bought_arts = false;
            if ($article = $this->get_product()) {
                $this->_a_also_bought_arts = $article->get_customer_also_bought_this_products();
            }
        }
        return $this->_a_also_bought_arts;
    }
    /**
     * Template variable getter. Returns if price alarm is enabled
     *
     * @return bool
     */
    public function is_price_alarm()
    {
        return $this->get_product()->is_price_alarm();
    }
    /**
     * returns object, associated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $languageId language id
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function get_subject($language_id)
    {
        return $this->get_product();
    }
    /**
     * Returns search title. It will be set in oxLocator
     *
     * @return string
     */
    public function get_search_title()
    {
        return $this->_s_search_title;
    }
    /**
     * Returns search title setter
     *
     * @param string $title search title
     */
    public function set_search_title($title): void
    {
        $this->_s_search_title = $title;
    }
    /**
     * Active category path setter.
     *
     * @param string $activeCategoryPath Category tree path
     */
    public function set_cat_tree_path($active_category_path): void
    {
        $this->_s_cat_tree_path = $active_category_path;
    }
    /**
     * If product details are accessed by vendor url
     * view must not be indexable
     *
     * @return int
     */
    public function no_index()
    {
        $list_type = Registry::get_request()->get_request_escaped_parameter('listtype');
        if ($list_type && ('vendor' == $list_type || 'manufacturer' == $list_type)) {
            return $this->_i_view_index_state = VIEW_INDEXSTATE_NOINDEXFOLLOW;
        }
        return parent::no_index();
    }
    /**
     * Returns current view title. Default is null
     */
    public function get_title()
    {
        if ($article = $this->get_product()) {
            $article_title = $article->oxarticles__oxtitle->value;
            $variant_selection_id = $article->oxarticles__oxvarselect->value;
            $variant_selection_value = $variant_selection_id ? ' ' . $variant_selection_id : '';
            return $article_title . $variant_selection_value;
        }
    }
    /**
     * Returns view canonical url
     *
     * @return string
     */
    public function get_canonical_url()
    {
        if ($article = $this->get_product()) {
            if ($article->oxarticles__oxparentid->value) {
                $article = $this->get_parent_product($article->oxarticles__oxparentid->value);
            }
            $utils_url = Registry::get_utils_url();
            if (Registry::get_utils()->seo_is_active()) {
                return $utils_url->prepare_canonical_url($article->get_base_seo_link($article->get_language(), true));
            }
            return $utils_url->prepare_canonical_url($article->get_base_std_link($article->get_language()));
        }
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        if ('search' == $this->get_list_type()) {
            $paths = $this->get_search_bread_crumb();
            // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        } elseif ('recommlist' == $this->get_list_type()) {
            $paths = $this->_get_recommendation_list_bred_crumb();
            // END deprecated
        } elseif ('vendor' == $this->get_list_type()) {
            $paths = $this->get_vendor_bread_crumb();
        } else {
            $paths = $this->get_category_bread_crumb();
        }
        return $paths;
    }
    /**
     * Validates email address.
     * If email address is OK - creates price alarm object and saves it (oxPriceAlarm::save()).
     * If email is wrong - returns false.
     * Sends price alarm notification mail to shop owner.
     */
    public function add_me(): void
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        $parameters = Registry::get_request()->get_request_escaped_parameter('pa');
        $email_validator = Container_Facade::get(Email_Validator_Service_Bridge_Interface::class);
        if (!isset($parameters['email']) || !$email_validator->is_email_valid($parameters['email'])) {
            $this->_i_price_alarm_status = 0;
            return;
        }
        $parameters['aid'] = $this->get_product()->get_id();
        $active_currency = $config->get_act_shop_currency_object();
        // convert currency to default
        $price = $utils->currency2Float($parameters['price']);
        $price_alarm = ox_new(\Oxid_Esales\Eshop\Application\Model\Price_Alarm::class);
        $price_alarm->oxpricealarm__oxuserid = new Field(Registry::get_session()->get_variable('usr'));
        $price_alarm->oxpricealarm__oxemail = new Field($parameters['email']);
        $price_alarm->oxpricealarm__oxartid = new Field($parameters['aid']);
        $price_alarm->oxpricealarm__oxprice = new Field($utils->f_round($price, $active_currency));
        $price_alarm->oxpricealarm__oxshopid = new Field($config->get_shop_id());
        $price_alarm->oxpricealarm__oxcurrency = new Field($active_currency->name);
        $price_alarm->oxpricealarm__oxlang = new Field(Registry::get_lang()->get_base_language());
        $price_alarm->save();
        // Send Email
        $email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
        $this->_i_price_alarm_status = (int) $email->send_pricealarm_notification($parameters, $price_alarm);
    }
    /**
     * Return price alarm status (if it was send)
     *
     * @return integer
     */
    public function get_price_alarm_status()
    {
        return $this->_i_price_alarm_status;
    }
    /**
     * Template variable getter. Returns bid price
     *
     * @return string
     */
    public function get_bid_price()
    {
        if ($this->_s_bid_price === null) {
            $this->_s_bid_price = false;
            $parameters = Registry::get_request()->get_request_escaped_parameter('pa');
            $active_currency = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $price = \Oxid_Esales\Eshop\Core\Registry::get_utils()->currency2Float($parameters['price']);
            $this->_s_bid_price = \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($price, $active_currency);
        }
        return $this->_s_bid_price;
    }
    /**
     * Returns variant selection
     *
     * @return \OxidEsales\Eshop\Application\Model\VariantSelectList
     */
    public function get_variant_selections()
    {
        $article = $this->get_product();
        $variant_selection_list_id = Registry::get_request()->get_request_escaped_parameter('varselid');
        if ($article_parent = $this->get_parent_product($article->oxarticles__oxparentid->value)) {
            return $article_parent->get_variant_selections($variant_selection_list_id, $article->get_id());
        }
        return $article->get_variant_selections($variant_selection_list_id);
    }
    /**
     * Returns pictures product object
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_pictures_product()
    {
        $variant_selections = $this->get_variant_selections();
        if ($variant_selections && $variant_selections['oActiveVariant'] && !$variant_selections['blPerfectFit']) {
            return $variant_selections['oActiveVariant'];
        }
        return $this->get_product();
    }
    /**
     * Template variable getter. Returns search parameter for Html
     *
     * @return string
     */
    public function get_search_param_for_html()
    {
        if ($this->_s_search_param_for_html === null) {
            $this->_s_search_param_for_html = Registry::get_request()->get_request_escaped_parameter('searchparam');
        }
        return $this->_s_search_param_for_html;
    }
    /**
     * Returns if page has rdfa
     *
     * @return bool
     */
    public function show_rdfa()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blRDFaEmbedding');
    }
    /**
     * Sets normalized rating
     *
     * @return array
     */
    public function get_rd_fa_normalized_rating()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $min_rating = $config->get_config_param('iRDFaMinRating');
        $max_rating = $config->get_config_param('iRDFaMaxRating');
        $article = $this->get_product();
        $count = $article->oxarticles__oxratingcnt->value;
        if (isset($min_rating) && isset($max_rating) && $max_rating != '' && $min_rating != '' && $count > 0) {
            $normalized_rating = [];
            $value = 4 * ($article->oxarticles__oxrating->value - $min_rating) / ($max_rating - $min_rating) + 1;
            $normalized_rating['count'] = $count;
            $normalized_rating['value'] = round($value, 2);
            return $normalized_rating;
        }
        return false;
    }
    /**
     * Sets and returns validity period of given object
     *
     * @param string $configVariableName object name
     *
     * @return array
     */
    public function get_rd_fa_validity_period($config_variable_name)
    {
        if ($config_variable_name) {
            $validity = [];
            $days = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param($config_variable_name);
            $from = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
            $through = $from + $days * 24 * 60 * 60;
            $validity['from'] = date('Y-m-d\TH:i:s', $from) . 'Z';
            $validity['through'] = date('Y-m-d\TH:i:s', $through) . 'Z';
            return $validity;
        }
        return false;
    }
    /**
     * Gets business function of the gr:Offering
     *
     * @return string
     */
    public function get_rd_fa_business_fnc()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sRDFaBusinessFnc');
    }
    /**
     * Gets the types of customers for which the given gr:Offering is valid
     *
     * @return array
     */
    public function get_rd_fa_customers()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aRDFaCustomers');
    }
    /**
     * Gets information whether prices include vat
     *
     * @return int
     */
    public function get_rd_fa_vat()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iRDFaVAT');
    }
    /**
     * Gets a generic description of product condition
     *
     * @return string
     */
    public function get_rd_fa_generic_condition()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iRDFaCondition');
    }
    /**
     * Returns bundle product
     *
     * @return \OxidEsales\Eshop\Application\Model\Article|false
     */
    public function get_bundle_article()
    {
        $article = $this->get_product();
        if ($article && $article->oxarticles__oxbundleid->value) {
            $bundle = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $bundle->load($article->oxarticles__oxbundleid->value);
            return $bundle;
        }
        return false;
    }
    /**
     * Gets accepted payment methods
     *
     * @return \OxidEsales\Eshop\Application\Model\PaymentList
     */
    public function get_rd_fa_payment_methods()
    {
        $price = $this->get_product()->get_price()->get_brutto_price();
        $payment_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment_List::class);
        $payment_list->load_rd_fa_payment_list($price);
        return $payment_list;
    }
    /**
     * Returns delivery methods with assigned delivery sets.
     *
     * @return \OxidEsales\Eshop\Application\Model\DeliverySetList
     */
    public function get_rd_fa_delivery_set_methods()
    {
        $delivery_set_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set_List::class);
        $delivery_set_list->load_rd_fa_delivery_set_list();
        return $delivery_set_list;
    }
    /**
     * Template variable getter. Returns delivery list for current product
     *
     * @return \OxidEsales\Eshop\Application\Model\DeliveryList
     */
    public function get_products_delivery_list()
    {
        $article = $this->get_product();
        $delivery_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_List::class);
        $delivery_list->load_delivery_list_for_product($article);
        return $delivery_list;
    }
    /**
     * Gets content id of delivery information page
     *
     * @return string
     */
    public function get_rd_fa_delivery_charge_spec_loc()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sRDFaDeliveryChargeSpecLoc');
    }
    /**
     * Gets content id of payments
     *
     * @return string
     */
    public function get_rd_fa_payment_charge_spec_loc()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sRDFaPaymentChargeSpecLoc');
    }
    /**
     * Gets content id of company info page (About Us)
     *
     * @return string
     */
    public function get_rd_fa_business_entity_loc()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sRDFaBusinessEntityLoc');
    }
    /**
     * Returns if to show products left stock
     *
     * @return string
     */
    public function show_rd_fa_product_stock()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowRDFaProductStock');
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
     * Returns default category sorting for selected category
     *
     * @return array
     */
    public function get_default_sorting()
    {
        $sorting = parent::get_default_sorting();
        $active_category = $this->get_active_category();
        if ($this->get_list_type() != 'search' && $active_category && $active_category instanceof Category) {
            if ($category_sorting = $active_category->get_default_sorting()) {
                $sorting_direction = $active_category->get_default_sorting_mode() ? 'desc' : 'asc';
                $sorting = ['sortby' => $category_sorting, 'sortdir' => $sorting_direction];
            }
        }
        return $sorting;
    }
    /**
     * Returns sorting parameters separated by "|"
     *
     * @return string
     */
    public function get_sorting_parameters()
    {
        $sorting = $this->get_sorting($this->get_sort_ident());
        if (!is_array($sorting)) {
            return null;
        }
        return implode('|', $sorting);
    }
    /**
     * Vendor bread crumb
     *
     * @return array
     */
    protected function get_vendor_bread_crumb()
    {
        $paths = [];
        $vendor_path = [];
        $vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        $vendor->load('root');
        $vendor_path['link'] = $vendor->get_link();
        $vendor_path['title'] = $vendor->oxvendor__oxtitle->value;
        $paths[] = $vendor_path;
        $vendor = $this->get_act_vendor();
        if ($vendor instanceof \Oxid_Esales\Eshop\Application\Model\Vendor) {
            $vendor_path['link'] = $vendor->get_link();
            $vendor_path['title'] = $vendor->oxvendor__oxtitle->value;
            $paths[] = $vendor_path;
        }
        return $paths;
    }
    /**
     * Recommendation list bread crumb
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array
     */
    protected function _get_recommendation_list_bred_crumb()
    {
        $paths = [];
        $recomm_list_path = [];
        $base_language_id = Registry::get_lang()->get_base_language();
        $recomm_list_path['title'] = Registry::get_lang()->translate_string('LISTMANIA', $base_language_id, false);
        $paths[] = $recomm_list_path;
        return $paths;
    }
    /**
     * Search bread crumb
     *
     * @return array
     */
    protected function get_search_bread_crumb()
    {
        $paths = [];
        $search_path = [];
        $base_language_id = Registry::get_lang()->get_base_language();
        $translated_string = Registry::get_lang()->translate_string('SEARCH_RESULT', $base_language_id, false);
        $self_link = $this->get_view_config()->get_self_link();
        $session_token = Registry::get_session()->get_variable('sess_stoken');
        $search_path['title'] = sprintf($translated_string, $this->get_search_param_for_html());
        $search_path['link'] = $self_link . 'stoken=' . $session_token . '&amp;cl=search&amp;' . 'searchparam=' . $this->get_search_param_for_html();
        $paths[] = $search_path;
        return $paths;
    }
    /**
     * Category bread crumb
     *
     * @return array
     */
    protected function get_category_bread_crumb()
    {
        $paths = [];
        $category_tree = $this->get_cat_tree_path();
        if ($category_tree) {
            foreach ($category_tree as $category) {
                /** @var Category $category */
                $category_path = [];
                $category_path['link'] = $category->get_link();
                $category_path['title'] = $category->oxcategories__oxtitle->value;
                $paths[] = $category_path;
            }
        }
        return $paths;
    }
}