<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

use Oxid_Esales\Eshop\Application\Model\Article;
use Oxid_Esales\Eshop\Application\Model\Article_List;
use Oxid_Esales\Eshop\Application\Model\Manufacturer;
use Oxid_Esales\Eshop\Application\Model\Simple_Variant_List;
use Oxid_Esales\Eshop\Application\Model\Vendor;
use Oxid_Esales\Eshop\Core\Config;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Utils;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Core\Sorting_Validator;
use stdClass;
/**
 * Article detailed information widget.
 */
class Article_Details extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * List of article variants.
     *
     * @var array
     */
    protected $_a_variant_list;
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     *
     * @var array
     */
    protected $_a_component_names = ['oxcmp_cur' => 1, 'oxcmp_shop' => 1, 'oxcmp_basket' => 1, 'oxcmp_user' => 1];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/product/details';
    /**
     * Current product parent article object.
     *
     * @var Article
     */
    protected $_o_parent_prod;
    /**
     * Marker if user can rate current product.
     *
     * @var bool
     */
    protected $_bl_can_rate;
    /**
     * Media files.
     *
     * @var array
     */
    protected $_a_media_files;
    /**
     * History (last seen) products.
     *
     * @var array
     */
    protected $_a_last_products;
    /**
     * Current product's vendor.
     *
     * @var Vendor
     */
    protected $_o_vendor;
    /**
     * Current product's manufacturer.
     *
     * @var Manufacturer
     */
    protected $_o_manufacturer;
    /**
     * Current product's category.
     *
     * @var object
     */
    protected $_o_category;
    /**
     * Current product's attributes.
     *
     * @var array
     */
    protected $_a_attributes;
    /**
     * Picture gallery.
     *
     * @var array
     */
    protected $_a_pic_gallery;
    /**
     * Reviews of current article.
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
     * Similar products article list.
     *
     * @var object
     */
    protected $_o_similar_products;
    /**
     * Accessories of current article.
     *
     * @var object
     */
    protected $_o_accessoires;
    /**
     * List of customer also bought these products.
     *
     * @var object
     */
    protected $_a_also_bought_arts;
    /**
     * Search title.
     *
     * @var string
     */
    protected $_s_search_title;
    /**
     * Marker if active product was fully initialized before returning it.
     * (see details::getProduct())
     *
     * @var bool
     */
    protected $_bl_is_initialized = false;
    /**
     * Current view link type.
     *
     * @var int
     */
    protected $_i_link_type;
    /**
     * Is multi dimension variant view.
     *
     * @var bool
     */
    protected $_bl_md_view;
    /**
     * Rating value.
     *
     * @var double
     */
    protected $_d_rating_value;
    /**
     * Rating count.
     *
     * @var integer
     */
    protected $_i_rating_cnt;
    /**
     * Bid price.
     *
     * @var string
     */
    protected $_s_bid_price;
    /**
     * Marked which defines if current view is sortable or not.
     *
     * @var bool
     */
    protected $_bl_show_sorting = true;
    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * Template variable getter. Returns active zoom picture id.
     *
     * @return int
     */
    public function get_act_zoom_pic()
    {
        return 1;
    }
    /**
     * Returns current product parent article object if it is available.
     *
     * @param string $sParentId parent product id
     *
     * @return Article
     */
    protected function get_parent_product($s_parent_id)
    {
        if ($s_parent_id && $this->_o_parent_prod === null) {
            $this->_o_parent_prod = false;
            $o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($o_product->load($s_parent_id)) {
                $this->process_product($o_product);
                $this->_o_parent_prod = $o_product;
            }
        }
        return $this->_o_parent_prod;
    }
    /**
     * In case list type is "search" returns search parameters which will be added to product details link.
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
     * Processes product by setting link type and in case list type is search adds search parameters to details link.
     *
     * @param object $oProduct Product to process.
     */
    protected function process_product($o_product)
    {
        $o_product->set_link_type($this->get_link_type());
        if ($s_add_params = $this->get_add_dyn_url_params()) {
            $o_product->append_link($s_add_params);
        }
    }
    /**
     * Checks if rating functionality is active.
     *
     * @return bool
     */
    public function rating_is_active()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadReviews');
    }
    /**
     * Checks if rating functionality is on and allowed to user.
     *
     * @return bool
     */
    public function can_rate()
    {
        if ($this->_bl_can_rate === null) {
            $this->_bl_can_rate = false;
            if ($this->rating_is_active() && $o_user = $this->get_user()) {
                $o_rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
                $this->_bl_can_rate = $o_rating->allow_rating($o_user->get_id(), 'oxarticle', $this->get_product()->get_id());
            }
        }
        return $this->_bl_can_rate;
    }
    /**
     * Loading full list of attributes.
     *
     * @return array
     */
    public function get_attributes()
    {
        if ($this->_a_attributes === null) {
            // all attributes this article has
            $a_art_attributes = $this->get_product()->get_attributes();
            //making a new array for backward compatibility
            $this->_a_attributes = false;
            if (count($a_art_attributes)) {
                foreach ($a_art_attributes as $s_key => $o_attribute) {
                    $this->_a_attributes[$s_key] = new stdClass();
                    $this->_a_attributes[$s_key]->title = $o_attribute->oxattribute__oxtitle->value;
                    $this->_a_attributes[$s_key]->value = $o_attribute->oxattribute__oxvalue->value;
                }
            }
        }
        return $this->_a_attributes;
    }
    /**
     * Returns current view link type.
     *
     * @return int
     */
    public function get_link_type()
    {
        if ($this->_i_link_type === null) {
            $s_list_type = Registry::get_request()->get_request_escaped_parameter('listtype');
            if ('vendor' == $s_list_type) {
                $this->_i_link_type = OXARTICLE_LINKTYPE_VENDOR;
            } elseif ('manufacturer' == $s_list_type) {
                $this->_i_link_type = OXARTICLE_LINKTYPE_MANUFACTURER;
                // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
            } elseif ('recommlist' == $s_list_type) {
                $this->_i_link_type = OXARTICLE_LINKTYPE_RECOMM;
                // END deprecated
            } else {
                $this->_i_link_type = OXARTICLE_LINKTYPE_CATEGORY;
                // price category has own type..
                if (($o_cat = $this->get_active_category()) && $o_cat->is_price_category()) {
                    $this->_i_link_type = OXARTICLE_LINKTYPE_PRICECATEGORY;
                }
            }
        }
        return $this->_i_link_type;
    }
    /**
     * Returns variant lists of current product
     * excludes currently viewed product.
     *
     * @return array|SimpleVariantList|ArticleList
     */
    public function get_variant_list_except_current()
    {
        $o_list = $this->get_variant_list();
        if (is_object($o_list)) {
            $o_list = clone $o_list;
        }
        $s_ox_id = $this->get_product()->get_id();
        if (isset($o_list[$s_ox_id])) {
            unset($o_list[$s_ox_id]);
        }
        return $o_list;
    }
    /**
     * Loading full list of variants,
     * if we are child and do not have any variants then let's load all parent variants as ours.
     *
     * @return array|SimpleVariantList|ArticleList
     */
    public function load_variant_information()
    {
        if ($this->_a_variant_list === null) {
            $o_product = $this->get_product();
            //if we are child and do not have any variants then let's load all parent variants as ours
            if ($o_parent = $o_product->get_parent_article()) {
                $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
                $o_parent->set_no_variant_loading(false);
                $this->_a_variant_list = $o_parent->get_full_variants(false);
                //lets additionally add parent article if it is sellable
                if (count($this->_a_variant_list) && $my_config->get_config_param('blVariantParentBuyable')) {
                    //#1104S if parent is buyable load select lists too
                    $o_parent->enable_price_load();
                    $o_parent->a_selectlist = $o_parent->get_select_lists();
                    $this->_a_variant_list = array_merge([$o_parent], $this->_a_variant_list->get_array());
                }
            } else {
                //loading full list of variants
                $this->_a_variant_list = $o_product->get_full_variants(false);
            }
            // setting link type for variants ..
            foreach ($this->_a_variant_list as $o_variant) {
                $this->process_product($o_variant);
            }
        }
        return $this->_a_variant_list;
    }
    /**
     * Returns variant lists of current product.
     *
     * @return array|SimpleVariantList|ArticleList
     */
    public function get_variant_list()
    {
        return $this->load_variant_information();
    }
    /**
     * Template variable getter. Returns media files of current product.
     *
     * @return array
     */
    public function get_media_files()
    {
        if ($this->_a_media_files === null) {
            $a_media_files = $this->get_product()->get_media_urls();
            $this->_a_media_files = count($a_media_files) ? $a_media_files : false;
        }
        return $this->_a_media_files;
    }
    /**
     * Template variable getter. Returns last seen products.
     *
     * @param int $iCnt product count
     *
     * @return array
     */
    public function get_last_products($i_cnt = 4)
    {
        if ($this->_a_last_products === null) {
            //last seen products for #768CA
            $o_product = $this->get_product();
            $s_parent_id_field = 'oxarticles__oxparentid';
            $s_art_id = $o_product->{$s_parent_id_field}->value ?: $o_product->get_id();
            $o_history_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $o_history_art_list->load_history_articles($s_art_id, $i_cnt);
            $this->_a_last_products = $o_history_art_list;
        }
        return $this->_a_last_products;
    }
    /**
     * Template variable getter. Returns product's vendor.
     *
     * @return object
     */
    public function get_manufacturer()
    {
        if ($this->_o_manufacturer === null) {
            $this->_o_manufacturer = $this->get_product()->get_manufacturer(false);
        }
        return $this->_o_manufacturer;
    }
    /**
     * Template variable getter. Returns product's vendor.
     *
     * @return object
     */
    public function get_vendor()
    {
        if ($this->_o_vendor === null) {
            $this->_o_vendor = $this->get_product()->get_vendor(false);
        }
        return $this->_o_vendor;
    }
    /**
     * Template variable getter. Returns product's root category.
     *
     * @return object
     */
    public function get_category()
    {
        if ($this->_o_category === null) {
            $this->_o_category = $this->get_product()->get_category();
        }
        return $this->_o_category;
    }
    /**
     * Template variable getter. Returns picture gallery of current article.
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
    public function has_multiple_images(): bool
    {
        return $this->get_picture_gallery()['hasMultipleImages'];
    }
    public function get_media_items(): array
    {
        return $this->get_picture_gallery()['mediaItems'];
    }
    /**
     * Template variable getter. Returns reviews of current article.
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
     * Template variable getter. Returns cross selling.
     *
     * @return object
     */
    public function get_cross_selling()
    {
        if ($this->_o_cross_selling === null) {
            $this->_o_cross_selling = false;
            if ($o_product = $this->get_product()) {
                $this->_o_cross_selling = $o_product->get_cross_selling();
            }
        }
        return $this->_o_cross_selling;
    }
    /**
     * Template variable getter. Returns similar article list.
     *
     * @return object
     */
    public function get_similar_products()
    {
        if ($this->_o_similar_products === null) {
            $this->_o_similar_products = false;
            if ($o_product = $this->get_product()) {
                $this->_o_similar_products = $o_product->get_similar_products();
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
            if ($o_product = $this->get_product()) {
                $this->_a_similar_recomm_list_ids = [$o_product->get_id()];
            }
        }
        return $this->_a_similar_recomm_list_ids;
    }
    /**
     * Template variable getter. Returns accessories of article.
     *
     * @return object
     */
    public function get_accessoires()
    {
        if ($this->_o_accessoires === null) {
            $this->_o_accessoires = false;
            if ($o_product = $this->get_product()) {
                $this->_o_accessoires = $o_product->get_accessoires();
            }
        }
        return $this->_o_accessoires;
    }
    /**
     * Template variable getter. Returns list of customer also bought these products.
     *
     * @return object
     */
    public function get_also_bought_these_products()
    {
        if ($this->_a_also_bought_arts === null) {
            $this->_a_also_bought_arts = false;
            if ($o_product = $this->get_product()) {
                $this->_a_also_bought_arts = $o_product->get_customer_also_bought_this_products();
            }
        }
        return $this->_a_also_bought_arts;
    }
    /**
     * Template variable getter. Returns if price alarm is enabled.
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
     * @param int $iLang language id
     *
     * @return object
     */
    protected function get_subject($i_lang)
    {
        return $this->get_product();
    }
    /**
     * Returns search title. It will be set in Locator.
     *
     * @return string
     */
    public function get_search_title()
    {
        return $this->_s_search_title;
    }
    /**
     * Returns search title setter.
     *
     * @param string $sTitle search title
     */
    public function set_search_title($s_title): void
    {
        $this->_s_search_title = $s_title;
    }
    /**
     * active category path setter
     *
     * @param string $sActCatPath category tree path.
     */
    public function set_cat_tree_path($s_act_cat_path): void
    {
        $this->_s_cat_tree_path = $s_act_cat_path;
    }
    /**
     * Checks should persistent parameter input field be displayed.
     *
     * @return bool
     */
    public function is_pers_param()
    {
        $o_product = $this->get_product();
        return $o_product->oxarticles__oxisconfigurable->value;
    }
    /**
     * Template variable getter. Returns rating value.
     *
     * @return double
     */
    public function get_rating_value()
    {
        if ($this->_d_rating_value === null) {
            $this->_d_rating_value = 0.0;
            if ($this->is_review_active() && $o_details_product = $this->get_product()) {
                $bl_show_variants_reviews = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowVariantReviews');
                $this->_d_rating_value = round($o_details_product->get_article_rating_average($bl_show_variants_reviews), 1);
            }
        }
        return (float) $this->_d_rating_value;
    }
    /**
     * Template variable getter. Returns if review module is on.
     *
     * @return bool
     */
    public function is_review_active()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadReviews');
    }
    /**
     * Template variable getter. Returns rating count.
     *
     * @return integer
     */
    public function get_rating_count()
    {
        if ($this->_i_rating_cnt === null) {
            $this->_i_rating_cnt = false;
            if ($this->is_review_active() && $o_details_product = $this->get_product()) {
                $bl_show_variants_reviews = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowVariantReviews');
                $this->_i_rating_cnt = $o_details_product->get_article_rating_count($bl_show_variants_reviews);
            }
        }
        return $this->_i_rating_cnt;
    }
    /**
     * Return price alarm status (if it was send).
     *
     * @return integer
     */
    public function get_price_alarm_status()
    {
        return $this->get_view_parameter('iPriceAlarmStatus');
    }
    /**
     * Template variable getter. Returns bid price.
     *
     * @return string
     */
    public function get_bid_price()
    {
        if ($this->_s_bid_price === null) {
            $this->_s_bid_price = false;
            $a_params = Registry::get_request()->get_request_escaped_parameter('pa');
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $i_price = \Oxid_Esales\Eshop\Core\Registry::get_utils()->currency2Float($a_params['price']);
            $this->_s_bid_price = \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($i_price, $o_cur);
        }
        return $this->_s_bid_price;
    }
    /**
     * Returns variant selection.
     *
     * @return array
     */
    public function get_variant_selections()
    {
        // finding parent
        $o_product = $this->get_product();
        $s_parent_id_field = 'oxarticles__oxparentid';
        if ($o_parent = $this->get_parent_product($o_product->{$s_parent_id_field}->value)) {
            $s_var_sel_id = Registry::get_request()->get_request_escaped_parameter('varselid');
            return $o_parent->get_variant_selections($s_var_sel_id, $o_product->get_id());
        }
        return $o_product->get_variant_selections(Registry::get_request()->get_request_escaped_parameter('varselid'));
    }
    /**
     * Returns pictures product object.
     *
     * @return Article
     */
    public function get_pictures_product()
    {
        $a_variant_selections = $this->get_variant_selections();
        if ($a_variant_selections && $a_variant_selections['oActiveVariant'] && !$a_variant_selections['blPerfectFit']) {
            return $a_variant_selections['oActiveVariant'];
        }
        return $this->get_product();
    }
    /**
     * Get product.
     *
     * @return Article
     */
    public function get_product()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        if ($this->_o_product === null) {
            if ($this->get_view_parameter('_object')) {
                $this->_o_product = $this->get_view_parameter('_object');
            } else {
                //this option is only for lists and we must reset value
                //as blLoadVariants = false affect "ab price" functionality
                $my_config->set_config_param('blLoadVariants', true);
                $s_oxid = Registry::get_request()->get_request_escaped_parameter('anid');
                // object is not yet loaded
                $this->_o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                if (!$this->_o_product->load($s_oxid)) {
                    $my_utils->redirect($my_config->get_shop_home_url());
                    $my_utils->show_message_and_exit('');
                }
                $s_var_sel_id = Registry::get_request()->get_request_escaped_parameter('varselid');
                $a_var_selections = $this->_o_product->get_variant_selections($s_var_sel_id);
                if ($a_var_selections && $a_var_selections['oActiveVariant'] && $a_var_selections['blPerfectFit']) {
                    $this->_o_product = $a_var_selections['oActiveVariant'];
                }
            }
        }
        if (!$this->_bl_is_initialized) {
            $this->additional_checks_for_article($my_utils, $my_config);
        }
        return $this->_o_product;
    }
    /**
     * Set item sorting for widget based of retrieved parameters.
     */
    protected function set_sorting_parameters()
    {
        $s_sorting_parameters = $this->get_view_parameter('sorting');
        if ($s_sorting_parameters) {
            [$sort_by, $sort_order] = explode('|', $s_sorting_parameters);
            if ((new Sorting_Validator())->is_valid($sort_by, $sort_order)) {
                $this->set_item_sorting($this->get_sort_ident(), $sort_by, $sort_order);
            }
        }
    }
    /**
     * Executes parent::render().
     * Returns name of template file to render.
     *
     * @return string $this->_sThisTemplate current template file name
     */
    public function render()
    {
        $o_product = $this->get_product();
        parent::render();
        $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        // if category parameter is not found, use category from product
        $s_cat_id = $this->get_view_parameter('cnid');
        if (!$s_cat_id && $o_product->get_category()) {
            $o_category = $o_product->get_category();
        } else {
            $o_category->load($s_cat_id);
        }
        $this->set_sorting_parameters();
        $this->set_active_category($o_category);
        $o_locator = ox_new(\Oxid_Esales\Eshop\Application\Component\Locator::class, $this->get_list_type());
        $o_locator->set_locator_data($o_product, $this);
        $this->_a_view_data['config'] = Registry::get_config();
        Registry::get_config();
        $this->_a_view_data['preview'] = Registry::get_request()->get_request_escaped_parameter('preview');
        $this->_a_view_data['altImageUrl'] = Container_Facade::get_parameter('oxid_esales.alternative_image_url');
        $this->_a_view_data['SSLAltImageUrl'] = Container_Facade::get_parameter('oxid_esales.alternative_image_url');
        return $this->_s_this_template;
    }
    /**
     * Should we show MD variant selection? - Not for 1 dimension variants.
     *
     * @return bool
     */
    public function is_md_variant_view()
    {
        if ($this->_bl_md_view === null) {
            $this->_bl_md_view = false;
            if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blUseMultidimensionVariants')) {
                $i_max_md_depth = $this->get_product()->get_md_variants()->get_max_depth();
                $this->_bl_md_view = $i_max_md_depth > 1;
            }
        }
        return $this->_bl_md_view;
    }
    /**
     * Runs additional checks for article.
     *
     * @param Utils  $myUtils  General utils.
     * @param Config $myConfig Main shop configuration.
     */
    protected function additional_checks_for_article($my_utils, $my_config)
    {
        $bl_continue = true;
        if (!$this->_o_product->is_visible()) {
            $bl_continue = false;
        } elseif ($this->_o_product->oxarticles__oxparentid->value) {
            $o_parent = $this->get_parent_product($this->_o_product->oxarticles__oxparentid->value);
            if (!$o_parent || !$o_parent->is_visible()) {
                $bl_continue = false;
            }
        }
        if (!$bl_continue) {
            $my_utils->redirect($my_config->get_shop_home_url());
            $my_utils->show_message_and_exit('');
        }
        $this->process_product($this->_o_product);
        $this->_bl_is_initialized = true;
    }
    /**
     * Returns default category sorting for selected category.
     *
     * @return array
     */
    public function get_default_sorting()
    {
        $a_sorting = parent::get_default_sorting();
        $o_category = $this->get_active_category();
        if ($this->get_list_type() != 'search' && $o_category && $o_category instanceof \Oxid_Esales\Eshop\Application\Model\Category) {
            if ($s_sort_by = $o_category->get_default_sorting()) {
                $s_sort_dir = $o_category->get_default_sorting_mode() ? 'desc' : 'asc';
                $a_sorting = ['sortby' => $s_sort_by, 'sortdir' => $s_sort_dir];
            }
        }
        return $a_sorting;
    }
}