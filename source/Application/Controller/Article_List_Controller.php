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
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
/**
 * List of articles for a selected product group.
 * Collects list of articles, according to it generates links for list gallery,
 * meta tags (for search engines). Result - "list" template.
 * OXID eShop -> (Any selected shop product category).
 */
class Article_List_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
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
    protected $_i_cnt_pages = 0;
    /**
     * Current class default template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/list/list';
    /**
     * New layout list template
     *
     * @deprecated will be removed in v8.0
     *
     * @var string
     */
    protected $_s_this_more_template = 'page/list/morecategories';
    /**
     * Category path string
     *
     * @var string
     */
    protected $_s_cat_path_string;
    /**
     * Marked which defines if current view is sortable or not
     *
     * @var bool
     */
    protected $_bl_show_sorting = true;
    /**
     * Category attributes.
     *
     * @var array
     */
    protected $_a_attributes;
    /**
     * Category article list
     *
     * @var array
     */
    protected $_a_cat_art_list;
    /**
     * If category has subcategories
     *
     * @var bool
     */
    protected $_bl_has_visible_sub_cats;
    /**
     * List of category's subcategories
     *
     * @var array
     */
    protected $_a_sub_cat_list;
    /**
     * Page navigation
     *
     * @var object
     */
    protected $_o_page_navigation;
    /**
     * Active object is category.
     *
     * @var bool
     */
    protected $_bl_is_cat;
    /**
     * Recomendation list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_o_recomm_list;
    /**
     * Category title
     *
     * @var string
     */
    protected $_s_cat_title;
    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = false;
    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * Generates (if not generated yet) and returns view ID (for
     * template engine caching).
     *
     * @return string   $this->_sViewId view id
     */
    protected function generate_view_id()
    {
        $category_id = Registry::get_request()->get_request_escaped_parameter('cnid');
        $active_page = $this->get_act_page();
        $articles_per_page = Registry::get_session()->get_variable('_artperpage');
        $list_display_type = $this->get_article_list_display_type();
        $parent_view_id = parent::generate_view_id();
        return md5($parent_view_id . '|' . $category_id . '|' . $active_page . '|' . $articles_per_page . '|' . $list_display_type);
    }
    /**
     * Executes parent::render(), loads active category, prepares article
     * list sorting rules. According to category type loads list of
     * articles - regular (oxArticleList::LoadCategoryArticles()) or price
     * dependent (oxArticleList::LoadPriceArticles()). Generates page navigation data
     * such as previous/next window URL, number of available pages, generates
     * meta tags info (\OxidEsales\Eshop\Application\Controller\FrontendController::_convertForMetaTags()) and returns
     * name of template to render. Also checks if actual pages count does not exceed real
     * articles page count. If yes - calls error_404_handler().
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $category = $this->get_category_to_render();
        $is_category_active = $category && (bool) $category->oxcategories__oxactive->value;
        if (!$is_category_active) {
            Registry::get_utils()->redirect($config->get_shop_url() . 'index.php', true, 302);
        }
        //checking if actual pages count does not exceed real articles page count
        $this->get_article_list();
        if ($this->_bl_is_cat) {
            $this->check_requested_page();
        }
        parent::render();
        // processing list articles
        $this->process_list_articles();
        return $this->get_template_name();
    }
    /**
     * Returns category, which should be rendered.
     * In case of 'more categories' page is viewed, sets 'more categories' template,
     * sets empty category as active category and returns it.
     *
     * @return Category
     */
    protected function get_category_to_render()
    {
        $this->_bl_is_cat = false;
        // A. checking for fake "more" category
        // @deprecated oxmore feature will be removed in v8.0
        if ('oxmore' == Registry::get_request()->get_request_escaped_parameter('cnid')) {
            // overriding some standard value and parameters
            $this->_s_this_template = $this->_s_this_more_template;
            $category = ox_new(Category::class);
            $category->oxcategories__oxactive = new Field(1, Field::T_RAW);
            $this->set_active_category($category);
            // END deprecated
        } elseif ($category = $this->get_active_category()) {
            $this->_bl_is_cat = true;
            $this->_bl_bargain_action = true;
        }
        return $category;
    }
    /**
     * Checks if requested page is valid and:
     * - redirecting to first page in case requested page does not exist
     * or
     * - displays 404 error if category has no products
     */
    protected function check_requested_page()
    {
        $page_count = $this->get_page_count();
        $current_page_number = $this->get_act_page();
        // redirecting to first page in case requested page does not exist
        if ($page_count && $page_count - 1 < $current_page_number) {
            Registry::get_utils()->redirect($this->get_active_category()->get_link(), false);
        }
        if (!$page_count && $current_page_number) {
            // display error if category has no products, but page number is entered
            $this->_i_act_page = 0;
            error_404_handler($this->get_active_category()->get_link());
        }
    }
    /**
     * Iterates through list articles and performs list view specific tasks:
     *  - sets type of link which needs to be generated (Manufacturer link)
     */
    protected function process_list_articles()
    {
        if ($article_list = $this->get_article_list()) {
            $link_type = $this->get_product_link_type();
            $dynamic_parameters = $this->get_add_url_params();
            $seo_parameters = $this->get_add_seo_url_params();
            foreach ($article_list as $article) {
                /** @var \OxidEsales\Eshop\Application\Model\Article $article */
                $article->set_link_type($link_type);
                if ($dynamic_parameters) {
                    $article->append_std_link($dynamic_parameters);
                }
                if ($seo_parameters) {
                    $article->append_link($seo_parameters);
                }
            }
        }
    }
    /**
     * Returns additional URL parameters which must be added to list products dynamic urls
     *
     * @return string
     */
    public function get_add_url_params()
    {
        $dynamic_parameters = parent::get_add_url_params();
        if (!Registry::get_utils()->seo_is_active()) {
            $page_number = (int) Registry::get_request()->get_request_escaped_parameter('pgNr');
            if ($page_number > 0) {
                $dynamic_parameters .= ($dynamic_parameters ? '&amp;' : '') . "pgNr={$page_number}";
            }
        }
        return $dynamic_parameters;
    }
    /**
     * Returns additional URL parameters which must be added to list products seo urls
     *
     * @return string
     */
    public function get_add_seo_url_params()
    {
        return '';
    }
    /**
     * Returns product link type:
     *  - OXARTICLE_LINKTYPE_PRICECATEGORY - when active category is price category
     *  - OXARTICLE_LINKTYPE_CATEGORY - when active category is regular category
     *
     * @return int
     */
    protected function get_product_link_type()
    {
        if (($category = $this->get_active_category()) && $category->is_price_category()) {
            return OXARTICLE_LINKTYPE_PRICECATEGORY;
        }
        return OXARTICLE_LINKTYPE_CATEGORY;
    }
    /**
     * Stores chosen category filter into session.
     *
     * Session variables:
     * <b>session_attrfilter</b>
     */
    public function executefilter(): void
    {
        $base_language_id = Registry::get_lang()->get_base_language();
        // store this into session
        $attribute_filter = Registry::get_request()->get_request_parameter('attrfilter');
        $active_category = Registry::get_request()->get_request_escaped_parameter('cnid');
        if (!empty($attribute_filter)) {
            $session_filter = Registry::get_session()->get_variable('session_attrfilter');
            //fix for #2904 - if language will be changed attributes of this category will be deleted from session
            //and new filters for active language set.
            $session_filter[$active_category] = null;
            $session_filter[$active_category][$base_language_id] = $attribute_filter;
            Registry::get_session()->set_variable('session_attrfilter', $session_filter);
        }
    }
    /**
     * Reset filter.
     */
    public function reset_filter(): void
    {
        $active_category = Registry::get_request()->get_request_escaped_parameter('cnid');
        $session_filter = Registry::get_session()->get_variable('session_attrfilter');
        unset($session_filter[$active_category]);
        Registry::get_session()->set_variable('session_attrfilter', $session_filter);
    }
    /**
     * Loads and returns article list of active category.
     *
     * @param Category $category category object
     *
     * @return \OxidEsales\Eshop\Application\Model\ArticleList
     */
    protected function load_articles($category)
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $number_of_category_articles = (int) $config->get_config_param('iNrofCatArticles');
        $number_of_category_articles = $number_of_category_articles ?: 1;
        // load only articles which we show on screen
        $article_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $article_list->set_sql_limit($number_of_category_articles * $this->get_request_page_nr(), $number_of_category_articles);
        $article_list->set_custom_sorting($this->get_sorting_sql($this->get_sort_ident()));
        if ($category->is_price_category()) {
            $price_from = $category->oxcategories__oxpricefrom->value;
            $price_to = $category->oxcategories__oxpriceto->value;
            $this->_i_all_art_cnt = $article_list->load_price_articles($price_from, $price_to, $category);
        } else {
            $session_filter = Registry::get_session()->get_variable('session_attrfilter');
            $active_category_id = $category->get_id();
            $this->_i_all_art_cnt = $article_list->load_category_articles($active_category_id, $session_filter);
        }
        $this->_i_cnt_pages = ceil($this->_i_all_art_cnt / $number_of_category_articles);
        return $article_list;
    }
    /**
     * Get actual page number.
     *
     * @return int
     */
    public function get_act_page()
    {
        //Fake oxmore category has no subpages so we can set the page number to zero
        // @deprecated oxmore feature will be removed in v8.0
        if ('oxmore' == Registry::get_request()->get_request_escaped_parameter('cnid')) {
            return 0;
        }
        // END deprecated
        return $this->get_request_page_nr();
    }
    /**
     * Calls parent::getActPage();
     *
     * @todo this function is a temporary solution and should be rmeoved as
     * soon product list loading is refactored
     *
     * @return int
     */
    protected function get_request_page_nr()
    {
        return parent::get_act_page();
    }
    /**
     * Get list display type
     *
     * @return null|string
     */
    protected function get_article_list_display_type()
    {
        $list_display_type = Registry::get_session()->get_variable('ldtype');
        if (is_null($list_display_type)) {
            return Registry::get_config()->get_config_param('sDefaultListDisplayType');
        }
        return $list_display_type;
    }
    /**
     * Returns active product id to load its seo meta info
     *
     * @return string
     */
    protected function get_seo_object_id()
    {
        if ($category = $this->get_active_category()) {
            return $category->get_id();
        }
    }
    /**
     * Returns string built from category titles
     *
     * @return string
     */
    protected function get_cat_path_string()
    {
        if ($this->_s_cat_path_string === null) {
            // marking as already set
            $this->_s_cat_path_string = false;
            //fetching category path
            if (is_array($category_tree_path = $this->get_cat_tree_path())) {
                $string_modifier = Str::get_str();
                $this->_s_cat_path_string = '';
                foreach ($category_tree_path as $category) {
                    if ($this->_s_cat_path_string) {
                        $this->_s_cat_path_string .= ', ';
                    }
                    $this->_s_cat_path_string .= $string_modifier->strtolower($category->oxcategories__oxtitle->value);
                }
            }
        }
        return $this->_s_cat_path_string;
    }
    /**
     * Returns current view meta description data.
     *
     * @param string $meta           Category path.
     * @param int    $length         Max length of result, -1 for no truncation.
     * @param bool   $descriptionTag If true - performs additional duplicate cleaning.
     *
     * @return  string
     */
    protected function prepare_meta_description($meta, $length = 1024, $description_tag = false)
    {
        $description = '';
        // appending parent title
        if ($active_category = $this->get_active_category()) {
            if ($parent_category = $active_category->get_parent_category()) {
                $description .= " {$parent_category->oxcategories__oxtitle->value} -";
            }
            // adding category title
            $description .= " {$active_category->oxcategories__oxtitle->value}.";
        }
        // and final component ..
        //changed for #2776
        if ($suffix = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_shop()->oxshops__oxtitleprefix->value) {
            $description .= " {$suffix}";
        }
        // making safe for output
        $str = Str::get_str();
        $description = $str->html_entity_decode($description);
        $description = $str->strip_tags($description);
        $description = $str->clean_str($description);
        $description = $str->htmlspecialchars($description);
        return trim((string) $description);
    }
    /**
     * Template variable getter. Returns meta description
     *
     * @return string
     */
    public function get_meta_description()
    {
        $meta = parent::get_meta_description();
        if ($title_page_suffix = $this->get_title_page_suffix()) {
            if ($meta) {
                $meta .= ', ';
            }
            $meta .= $title_page_suffix;
        }
        return $meta;
    }
    /**
     * Meta tags - description and keywords - generator for search
     * engines. Uses string passed by parameters, cleans HTML tags,
     * string duplicates, special chars. Also removes strings defined
     * in $config->aSkipTags (Admin area).
     *
     * @param string $meta           Category path
     * @param int    $length         Max length of result, -1 for no truncation
     * @param bool   $descriptionTag If true - performs additional duplicate cleaning
     *
     * @return  string
     */
    protected function collect_meta_description($meta, $length = 1024, $description_tag = false)
    {
        //formatting description tag
        $category = $this->get_active_category();
        $additional_text = $category instanceof Category ? $this->collect_category_meta_description($category) : '';
        if (!$additional_text) {
            $additional_text = $this->collect_product_meta_description();
        }
        if (!$meta) {
            $meta = trim($this->get_cat_path_string());
        }
        $meta = $meta ? "{$meta} - {$additional_text}" : $additional_text;
        return parent::prepare_meta_description($meta, $length, $description_tag);
    }
    private function collect_category_meta_description(Category $category): string
    {
        if (isset($category->oxcategories__oxlongdesc) && $category->oxcategories__oxlongdesc instanceof Field) {
            $active_language_id = Registry::get_lang()->get_tpl_language();
            $oxid = $category->get_id() . $category->get_language();
            return trim($this->get_renderer()->render_fragment($category->oxcategories__oxlongdesc->get_raw_value(), "ox:{$oxid}{$active_language_id}", $this->get_view_data()));
        }
        return '';
    }
    private function get_renderer(): Template_Renderer_Interface
    {
        return Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
    }
    private function collect_product_meta_description(): string
    {
        $article_list = $this->get_article_list();
        if ($article_list && $article_list->count()) {
            $article_titles = [];
            foreach ($article_list as $article) {
                $article_titles[] = $article->oxarticles__oxtitle->value;
            }
            return implode(', ', $article_titles);
        }
        return '';
    }
    /**
     * Returns current view keywords separated by comma
     *
     * @param string $keywords              Data to use as keywords
     * @param bool   $removeDuplicatedWords Remove duplicated words
     *
     * @return string
     */
    protected function prepare_meta_keyword($keywords, $remove_duplicated_words = true)
    {
        $keywords = '';
        if ($active_category = $this->get_active_category()) {
            $keywords_list = [];
            if ($category_tree = $this->get_category_tree()) {
                foreach ($category_tree->get_path() as $category) {
                    $keywords_list[] = trim((string) $category->oxcategories__oxtitle->value);
                }
            }
            $sub_categories = $active_category->get_sub_cats();
            if (is_array($sub_categories)) {
                foreach ($sub_categories as $sub_category) {
                    $keywords_list[] = $sub_category->oxcategories__oxtitle->value;
                }
            }
            if (count($keywords_list) > 0) {
                $keywords = implode(', ', $keywords_list);
            }
        }
        $keywords = parent::prepare_meta_description($keywords, -1, $remove_duplicated_words);
        return trim($keywords);
    }
    /**
     * Creates a string of keyword filtered by the function prepareMetaDescription and without any duplicates
     * additional the admin defined strings are removed
     *
     * @param string $keywords category path
     *
     * @return string
     */
    protected function collect_meta_keyword($keywords)
    {
        $max_text_length = 60;
        $text = '';
        if (count($article_list = $this->get_article_list())) {
            $string_modifier = Str::get_str();
            foreach ($article_list as $article) {
                /** @var \OxidEsales\Eshop\Application\Model\Article $article */
                $description = $string_modifier->strip_tags(trim((string) $string_modifier->strtolower($article->get_long_description()->value)));
                //removing dots from string (they are not cleaned up during general string cleanup)
                $description = $string_modifier->preg_replace("/\\./", ' ', $description);
                if ($string_modifier->strlen($description) > $max_text_length) {
                    $mid_text = $string_modifier->substr($description, 0, $max_text_length);
                    $description = $string_modifier->substr($mid_text, 0, $string_modifier->strlen($mid_text) - $string_modifier->strpos(strrev((string) $mid_text), ' '));
                }
                if ($text) {
                    $text .= ', ';
                }
                $text .= $description;
            }
        }
        if (!$keywords) {
            $keywords = $this->get_cat_path_string();
        }
        if ($keywords) {
            $text = "{$keywords}, {$text}";
        }
        return parent::prepare_meta_keyword($text);
    }
    /**
     * Assigns Template name ($this->_sThisTemplate) for article list
     * preview. Name of template can be defined in admin or passed by
     * URL ("tpl" variable).
     *
     * @return string
     */
    public function get_template_name()
    {
        if ($template_name = Registry::get_request()->get_request_escaped_parameter('tpl')) {
            $this->_s_this_template = 'custom/' . basename((string) $template_name);
        } elseif (($category = $this->get_active_category()) && $category->get_field_data('oxtemplate')) {
            $this->_s_this_template = $category->oxcategories__oxtemplate->value;
        }
        return $this->_s_this_template;
    }
    /**
     * Adds page number parameter to current Url and returns formatted url
     *
     * @param string $url         Url to append page numbers
     * @param int    $currentPage Current page number
     * @param int    $languageId  Requested language
     *
     * @return string
     */
    protected function add_page_nr_param($url, $current_page, $language_id = null)
    {
        if (Registry::get_utils()->seo_is_active() && $category = $this->get_active_category()) {
            if ($current_page) {
                // only if page number > 0
                $url = $category->get_base_seo_link($language_id, $current_page);
            }
        } else {
            $url = parent::add_page_nr_param($url, $current_page, $language_id);
        }
        return $url;
    }
    /**
     * Returns true if we have category
     *
     * @return bool
     */
    protected function is_act_category()
    {
        return $this->_bl_is_cat;
    }
    /**
     * Generates Url for page navigation
     *
     * @return string
     */
    public function generate_page_navigation_url()
    {
        if (Registry::get_utils()->seo_is_active() && $category = $this->get_active_category()) {
            return $category->get_link();
        }
        return parent::generate_page_navigation_url();
    }
    /**
     * Returns default category sorting for selected category
     *
     * @return array
     */
    public function get_default_sorting()
    {
        $sorting = parent::get_default_sorting();
        $category = $this->get_active_category();
        if ($category && $category instanceof Category) {
            if ($default_sorting = $category->get_default_sorting()) {
                $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
                $article_view_name = $table_view_name_generator->get_view_name('oxarticles');
                $sort_by = $article_view_name . '.' . $default_sorting;
                $sort_direction = $category->get_default_sorting_mode() ? 'desc' : 'asc';
                $sorting = ['sortby' => $sort_by, 'sortdir' => $sort_direction];
            }
        }
        return $sorting;
    }
    /**
     * Returns title suffix used in template
     *
     * @return string
     */
    public function get_title_suffix()
    {
        if ($this->get_active_category()->oxcategories__oxshowsuffix->value) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_shop()->oxshops__oxtitlesuffix->value;
        }
    }
    /**
     * Returns title page suffix used in template
     *
     * @return string
     */
    public function get_title_page_suffix()
    {
        if ($active_page = $this->get_act_page()) {
            return Registry::get_lang()->translate_string('PAGE') . ' ' . ($active_page + 1);
        }
    }
    /**
     * Returns object, associated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $languageId Language id
     *
     * @return object
     */
    protected function get_subject($language_id)
    {
        return $this->get_active_category();
    }
    /**
     * Template variable getter. Returns array of attribute values
     * we do have here in this category
     *
     * @return array
     */
    public function get_attributes()
    {
        $this->_a_attributes = false;
        if ($category = $this->get_active_category()) {
            $attributes = $category->get_attributes();
            if (count($attributes)) {
                $this->_a_attributes = $attributes;
            }
        }
        return $this->_a_attributes;
    }
    /**
     * Template variable getter. Returns category's article list
     *
     * @return \OxidEsales\Eshop\Application\Model\ArticleList|null
     */
    public function get_article_list()
    {
        if ($this->_a_article_list === null) {
            if ($category = $this->get_active_category()) {
                $article_list = $this->load_articles($category);
                if (count($article_list)) {
                    $this->_a_article_list = $article_list;
                }
            }
        }
        return $this->_a_article_list;
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
            if ($category_articles_list = $this->get_article_list()) {
                $this->_a_similar_recomm_list_ids = $category_articles_list->array_keys();
            }
        }
        return $this->_a_similar_recomm_list_ids;
    }
    /**
     * Template variable getter. Returns category path
     *
     * @return array
     */
    public function get_cat_tree_path()
    {
        if ($this->_s_cat_tree_path === null) {
            $this->_s_cat_tree_path = false;
            // category path
            if ($category_tree = $this->get_category_tree()) {
                $this->_s_cat_tree_path = $category_tree->get_path();
            }
        }
        return $this->_s_cat_tree_path;
    }
    /**
     * Template variable getter. Returns category path array
     *
     * @return array
     */
    public function get_tree_path()
    {
        if ($category_tree = $this->get_category_tree()) {
            return $category_tree->get_path();
        }
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $paths = [];
        // @deprecated oxmore feature will be removed in v8.0
        if ('oxmore' == Registry::get_request()->get_request_escaped_parameter('cnid')) {
            $path = [];
            $path['title'] = Registry::get_lang()->translate_string('CATEGORY_OVERVIEW', Registry::get_lang()->get_base_language(), false);
            $path['link'] = $this->get_link();
            $paths[] = $path;
            return $paths;
        }
        // END deprecated
        if (($category_tree = $this->get_category_tree()) && $category_paths = $category_tree->get_path()) {
            foreach ($category_paths as $category) {
                /** @var Category $category */
                $category_path = [];
                $category_path['link'] = $category->get_link();
                $category_path['title'] = $category->oxcategories__oxtitle->value;
                $paths[] = $category_path;
            }
        }
        return $paths;
    }
    /**
     * Template variable getter. Returns true if category has active
     * subcategories.
     *
     * @return bool
     */
    public function has_visible_sub_cats()
    {
        if ($this->_bl_has_visible_sub_cats === null) {
            $this->_bl_has_visible_sub_cats = false;
            if ($active_category = $this->get_active_category()) {
                $this->_bl_has_visible_sub_cats = $active_category->get_has_visible_sub_cats();
            }
        }
        return $this->_bl_has_visible_sub_cats;
    }
    /**
     * Template variable getter. Returns list of subcategories.
     *
     * @return array
     */
    public function get_sub_cat_list()
    {
        if ($this->_a_sub_cat_list === null) {
            $this->_a_sub_cat_list = [];
            if ($active_category = $this->get_active_category()) {
                $this->_a_sub_cat_list = $active_category->get_sub_cats();
            }
        }
        return $this->_a_sub_cat_list;
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
     * Template variable getter. Returns category title.
     *
     * @return string
     */
    public function get_title()
    {
        if ($this->_s_cat_title === null) {
            $this->_s_cat_title = false;
            // @deprecated oxmore feature will be removed in v8.0
            if ($this->get_category_id() == 'oxmore') {
                $language = Registry::get_lang();
                $base_language_id = $language->get_base_language();
                $this->_s_cat_title = $language->translate_string('CATEGORY_OVERVIEW', $base_language_id, false);
                // END deprecated
            } elseif ($category = $this->get_active_category()) {
                $this->_s_cat_title = $category->oxcategories__oxtitle->value;
            }
        }
        return $this->_s_cat_title;
    }
    /**
     * Template variable getter. Returns bargain article list
     *
     * @return array
     */
    public function get_bargain_article_list()
    {
        if ($this->_a_bargain_article_list === null) {
            $this->_a_bargain_article_list = [];
            if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadAktion') && $this->is_act_category()) {
                $article_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
                $article_list->load_action_articles('OXBARGAIN');
                if ($article_list->count()) {
                    $this->_a_bargain_article_list = $article_list;
                }
            }
        }
        return $this->_a_bargain_article_list;
    }
    /**
     * Template variable getter. Returns active search
     *
     * @return Category
     */
    public function get_active_category()
    {
        if ($this->_o_act_category === null) {
            $this->_o_act_category = false;
            $category = ox_new(Category::class);
            if ($category->load($this->get_category_id())) {
                $this->_o_act_category = $category;
            }
        }
        return $this->_o_act_category;
    }
    /**
     * Returns view canonical url
     *
     * @return string
     */
    public function get_canonical_url()
    {
        if ($category = $this->get_active_category()) {
            $utils_url = Registry::get_utils_url();
            if (Registry::get_utils()->seo_is_active()) {
                return $utils_url->prepare_canonical_url($category->get_base_seo_link($category->get_language(), $this->get_act_page()));
            }
            return $utils_url->prepare_canonical_url($category->get_base_std_link($category->get_language(), $this->get_act_page()));
        }
    }
    /**
     * Returns config parameters blShowListDisplayType value
     *
     * @return boolean
     */
    public function can_select_display_type()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowListDisplayType');
    }
    /**
     * Get list articles pages count
     *
     * @return int
     */
    public function get_page_count()
    {
        return $this->_i_cnt_pages;
    }
}