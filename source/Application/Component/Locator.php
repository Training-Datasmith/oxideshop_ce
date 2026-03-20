<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

use function Get_Logger;
use Oxid_Esales\Eshop\Application\Controller\Frontend_Controller;
use Oxid_Esales\Eshop\Application\Model\Article;
use Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Manufacturer;
use Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Vendor;
use Oxid_Esales\Eshop\Core\Model\List_Model;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Locator controller for: category, vendor, manufacturers and search lists.
 */
class Locator extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Locator type
     */
    protected string $_s_type = 'list';
    /**
     * Next product to currently loaded
     */
    protected $_o_next_product;
    /**
     * Previous product to currently loaded
     */
    protected $_o_back_product;
    /**
     * search handle
     */
    protected $_s_search_handle;
    /**
     * error message
     */
    protected $_s_error_message;
    /**
     * Class constructor - sets locator type and parameters posted or loaded
     * from GET/Session
     *
     * @param string $sType locator type
     */
    public function __construct($s_type = null)
    {
        // setting locator type
        if ($s_type) {
            $this->_s_type = trim($s_type);
        }
    }
    /**
     * Executes locator method according locator type
     *
     * @param Article            $oCurrArticle   current article
     * @param FrontendController $oLocatorTarget FrontendController object
     */
    public function set_locator_data($o_curr_article, $o_locator_target): void
    {
        $s_locfnc = "set{$this->_s_type}LocatorData";
        try {
            call_user_func([$this, $s_locfnc], $o_locator_target, $o_curr_article);
        } catch (\Exception) {
            get_logger()->warning("Locator Type is wrong {$this->_s_type}");
            $this->_s_type = '';
        }
        // passing list type to view
        $o_locator_target->set_list_type($this->_s_type);
    }
    /**
     * Sets details locator data for articles that came from regular list.
     *
     * @param FrontendController $oLocatorTarget view object
     * @param Article            $oCurrArticle   current article
     */
    protected function set_list_locator_data($o_locator_target, $o_curr_article)
    {
        // if no active category is loaded - lets check for category passed by post/get
        if ($o_category = $o_locator_target->get_active_category()) {
            $s_order_by = $o_locator_target->get_sorting_sql($o_locator_target->get_sort_ident());
            $o_id_list = $this->load_ids_in_list($o_category, $o_curr_article, $s_order_by);
            //page number
            $i_page = $this->find_act_page_number($o_locator_target->get_act_page(), $o_id_list, $o_curr_article);
            // setting product position in list, amount of articles etc
            $o_category->i_cnt_of_prod = $o_id_list->count();
            $o_category->i_product_pos = $this->get_product_pos($o_curr_article, $o_id_list, $o_locator_target);
            if (Registry::get_utils()->seo_is_active() && $i_page) {
                /** @var \OxidEsales\Eshop\Application\Model\SeoEncoderCategory $oSeoEncoderCategory */
                $o_seo_encoder_category = Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class);
                $o_category->to_list_link = $o_seo_encoder_category->get_category_page_url($o_category, $i_page);
            } else {
                $o_category->to_list_link = $this->make_link($o_category->get_link(), $this->get_page_number($i_page));
            }
            $o_next_product = $this->_o_next_product;
            $o_back_product = $this->_o_back_product;
            $o_category->next_product_link = $o_next_product ? $this->make_link($o_next_product->get_link(), '') : null;
            $o_category->prev_product_link = $o_back_product ? $this->make_link($o_back_product->get_link(), '') : null;
            // active category
            $o_locator_target->set_active_category($o_category);
            // category path
            if ($o_cat_tree = $o_locator_target->get_category_tree()) {
                $o_locator_target->set_cat_tree_path($o_cat_tree->get_path());
            }
        }
    }
    /**
     * Sets details locator data for articles that came from vendor list.
     *
     * @param FrontendController $oLocatorTarget FrontendController object
     * @param Article            $oCurrArticle   current article
     */
    protected function set_vendor_locator_data($o_locator_target, $o_curr_article)
    {
        if ($o_vendor = $o_locator_target->get_act_vendor()) {
            $s_vendor_id = $o_vendor->get_id();
            $my_utils = Registry::get_utils();
            $bl_seo = $my_utils->seo_is_active();
            // loading data for article navigation
            $o_id_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $o_id_list->set_custom_sorting($o_locator_target->get_sorting_sql($o_locator_target->get_sort_ident()));
            $o_id_list->load_vendor_ids($s_vendor_id);
            //page number
            $i_page = $this->find_act_page_number($o_locator_target->get_act_page(), $o_id_list, $o_curr_article);
            $s_add = null;
            if (!$bl_seo) {
                $s_add = 'listtype=vendor&amp;cnid=v_' . $s_vendor_id;
            }
            // setting product position in list, amount of articles etc
            $o_vendor->i_cnt_of_prod = $o_id_list->count();
            $o_vendor->i_product_pos = $this->get_product_pos($o_curr_article, $o_id_list, $o_locator_target);
            if ($bl_seo && $i_page) {
                $o_vendor->to_list_link = Registry::get(Seo_Encoder_Vendor::class)->get_vendor_page_url($o_vendor, $i_page);
            } else {
                $o_vendor->to_list_link = $this->make_link($o_vendor->get_link(), $this->get_page_number($i_page));
            }
            $o_next_product = $this->_o_next_product;
            $o_back_product = $this->_o_back_product;
            $o_vendor->next_product_link = $o_next_product ? $this->make_link($o_next_product->get_link(), $s_add) : null;
            $o_vendor->prev_product_link = $o_back_product ? $this->make_link($o_back_product->get_link(), $s_add) : null;
        }
    }
    /**
     * Sets details locator data for articles that came from Manufacturer list.
     *
     * @param FrontendController $oLocatorTarget FrontendController object
     * @param Article            $oCurrArticle   current article
     */
    protected function set_manufacturer_locator_data($o_locator_target, $o_curr_article)
    {
        if ($o_manufacturer = $o_locator_target->get_act_manufacturer()) {
            $s_manufacturer_id = $o_manufacturer->get_id();
            $my_utils = Registry::get_utils();
            $bl_seo = $my_utils->seo_is_active();
            // loading data for article navigation
            $o_id_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $o_id_list->set_custom_sorting($o_locator_target->get_sorting_sql($o_locator_target->get_sort_ident()));
            $o_id_list->load_manufacturer_ids($s_manufacturer_id);
            //page number
            $i_page = $this->find_act_page_number($o_locator_target->get_act_page(), $o_id_list, $o_curr_article);
            $s_add = null;
            if (!$bl_seo) {
                $s_add = 'listtype=manufacturer&amp;mnid=' . $s_manufacturer_id;
            }
            // setting product position in list, amount of articles etc
            $o_manufacturer->i_cnt_of_prod = $o_id_list->count();
            $o_manufacturer->i_product_pos = $this->get_product_pos($o_curr_article, $o_id_list, $o_locator_target);
            if ($bl_seo && $i_page) {
                /** @var SeoEncoderManufacturer $oSeoEncoderManufacturer */
                $o_seo_encoder_manufacturer = Registry::get(Seo_Encoder_Manufacturer::class);
                $o_manufacturer->to_list_link = $o_seo_encoder_manufacturer->get_manufacturer_page_url($o_manufacturer, $i_page);
            } else {
                $o_manufacturer->to_list_link = $this->make_link($o_manufacturer->get_link(), $this->get_page_number($i_page));
            }
            $o_next_product = $this->_o_next_product;
            $o_back_product = $this->_o_back_product;
            $o_manufacturer->next_product_link = $o_next_product ? $this->make_link($o_next_product->get_link(), $s_add) : null;
            $o_manufacturer->prev_product_link = $o_back_product ? $this->make_link($o_back_product->get_link(), $s_add) : null;
            // active Manufacturer
            $o_locator_target->set_active_category($o_manufacturer);
            // Manufacturer path
            if ($o_manufacturer_tree = $o_locator_target->get_manufacturer_tree()) {
                $o_locator_target->set_cat_tree_path($o_manufacturer_tree->get_path());
            }
        }
    }
    /**
     * Sets details locator data for articles that came from search list.
     *
     * @param FrontendController $oLocatorTarget FrontendController object
     * @param Article            $oCurrArticle   current article
     */
    protected function set_search_locator_data($o_locator_target, $o_curr_article)
    {
        if ($o_search_cat = $o_locator_target->get_act_search()) {
            // #1834/1184M - specialchar search
            $s_search_param = Registry::get_request()->get_request_parameter('searchparam');
            $s_search_form_param = Registry::get_request()->get_request_escaped_parameter('searchparam');
            $s_search_link_param = rawurlencode((string) $s_search_param);
            $s_search_cat = Registry::get_request()->get_request_escaped_parameter('searchcnid');
            $s_search_cat = $s_search_cat ? rawurldecode((string) $s_search_cat) : $s_search_cat;
            $s_search_vendor = Registry::get_request()->get_request_escaped_parameter('searchvendor');
            $s_search_vendor = $s_search_vendor ? rawurldecode((string) $s_search_vendor) : $s_search_vendor;
            $s_search_manufacturer = Registry::get_request()->get_request_escaped_parameter('searchmanufacturer');
            $s_search_manufacturer = $s_search_manufacturer ? rawurldecode((string) $s_search_manufacturer) : $s_search_manufacturer;
            // loading data for article navigation
            $o_id_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $o_id_list->set_custom_sorting($o_locator_target->get_sorting_sql($o_locator_target->get_sort_ident()));
            $o_id_list->load_search_ids($s_search_param, $s_search_cat, $s_search_vendor, $s_search_manufacturer);
            //page number
            $i_page = $this->find_act_page_number($o_locator_target->get_act_page(), $o_id_list, $o_curr_article);
            $s_add_search = "searchparam={$s_search_link_param}";
            $s_add_search .= '&amp;listtype=search';
            if ($s_search_cat !== null) {
                $s_add_search .= "&amp;searchcnid={$s_search_cat}";
            }
            if ($s_search_vendor !== null) {
                $s_add_search .= "&amp;searchvendor={$s_search_vendor}";
            }
            if ($s_search_manufacturer !== null) {
                $s_add_search .= "&amp;searchmanufacturer={$s_search_manufacturer}";
            }
            // setting product position in list, amount of articles etc
            $o_search_cat->i_cnt_of_prod = $o_id_list->count();
            $o_search_cat->i_product_pos = $this->get_product_pos($o_curr_article, $o_id_list, $o_locator_target);
            $s_page_nr = $this->get_page_number($i_page);
            $s_params = $s_page_nr . ($s_page_nr ? '&amp;' : '') . $s_add_search;
            $o_search_cat->to_list_link = $this->make_link($o_search_cat->link, $s_params);
            $o_next_prod = $this->_o_next_product;
            $o_back_prod = $this->_o_back_product;
            $o_search_cat->next_product_link = $o_next_prod ? $this->make_link($o_next_prod->get_link(), $s_add_search) : null;
            $o_search_cat->prev_product_link = $o_back_prod ? $this->make_link($o_back_prod->get_link(), $s_add_search) : null;
            $s_format = Registry::get_lang()->translate_string('SEARCH_RESULT');
            $o_locator_target->set_search_title(sprintf($s_format, $s_search_form_param));
            $o_locator_target->set_active_category($o_search_cat);
        }
    }
    /**
     * Sets details locator data for articles that came from recommlist.
     *
     * Template variables:
     * <b>sSearchTitle</b>, <b>searchparamforhtml</b>
     *
     * @param FrontendController $oLocatorTarget FrontendController object
     * @param Article            $oCurrArticle   current article
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     */
    protected function _set_recommlist_locator_data($o_locator_target, $o_curr_article)
    {
        if ($o_recomm_list = $o_locator_target->get_active_recomm_list()) {
            // loading data for article navigation
            $o_id_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $o_id_list->load_recomm_article_ids($o_recomm_list->get_id(), null);
            //page number
            $i_page = $this->find_act_page_number($o_locator_target->get_act_page(), $o_id_list, $o_curr_article);
            $s_search_recomm = Registry::get_request()->get_request_parameter('searchrecomm');
            if ($s_search_recomm !== null) {
                $s_search_form_recomm = Registry::get_request()->get_request_escaped_parameter('searchrecomm');
                $s_search_link_recomm = rawurlencode($s_search_recomm);
                $s_add_search = 'searchrecomm=' . $s_search_link_recomm;
            }
            // setting product position in list, amount of articles etc
            $o_recomm_list->i_cnt_of_prod = $o_id_list->count();
            $o_recomm_list->i_product_pos = $this->get_product_pos($o_curr_article, $o_id_list, $o_locator_target);
            $bl_seo = Registry::get_utils()->seo_is_active();
            if ($bl_seo && $i_page) {
                /** @var \OxidEsales\Eshop\Application\Model\SeoEncoderRecomm $oSeoEncoderRecomm */
                $o_seo_encoder_recomm = Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Recomm::class);
                $o_recomm_list->to_list_link = $o_seo_encoder_recomm->get_recomm_page_url($o_recomm_list, $i_page);
            } else {
                $o_recomm_list->to_list_link = $this->make_link($o_recomm_list->get_link(), $this->get_page_number($i_page));
            }
            $o_recomm_list->to_list_link = $this->make_link($o_recomm_list->to_list_link, $s_add_search);
            $s_add = '';
            if (!$bl_seo) {
                $s_add = 'recommid=' . $o_recomm_list->get_id() . '&amp;listtype=recommlist' . ($s_add_search ? '&amp;' : '');
            }
            $s_add .= $s_add_search;
            $o_next_product = $this->_o_next_product;
            $o_back_product = $this->_o_back_product;
            $o_recomm_list->next_product_link = $o_next_product ? $this->make_link($o_next_product->get_link(), $s_add) : null;
            $o_recomm_list->prev_product_link = $o_back_product ? $this->make_link($o_back_product->get_link(), $s_add) : null;
            $o_lang = Registry::get_lang();
            $s_title = $o_lang->translate_string('RECOMMLIST');
            if ($s_search_recomm !== null) {
                $s_title .= ' / ' . $o_lang->translate_string('RECOMMLIST_SEARCH') . ' "' . $s_search_form_recomm . '"';
            }
            $o_locator_target->set_search_title($s_title);
            $o_locator_target->set_active_category($o_recomm_list);
        }
    }
    /**
     * Setting product position in list, amount of articles etc
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory    active category id
     * @param object                                       $oCurrArticle current article
     * @param string                                       $sOrderBy     order by fields
     *
     * @return object
     */
    protected function load_ids_in_list($o_category, $o_curr_article, $s_order_by = null)
    {
        $o_id_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_id_list->set_custom_sorting($s_order_by);
        // additionally check if this category is loaded and is price category ?
        if ($o_category->is_price_category()) {
            $o_id_list->load_price_ids($o_category->oxcategories__oxpricefrom->value, $o_category->oxcategories__oxpriceto->value);
        } else {
            $s_act_cat = $o_category->get_id();
            $o_id_list->load_category_i_ds($s_act_cat, Registry::get_session()->get_variable('session_attrfilter'));
            // if not found - reloading with empty filter
            if (!isset($o_id_list[$o_curr_article->get_id()])) {
                $o_id_list->load_category_i_ds($s_act_cat, null);
            }
        }
        return $o_id_list;
    }
    /**
     * Appends urs with currently passed parameters
     *
     * @param string $sLink   url to add parameters
     * @param string $sParams parameters to add to url
     *
     * @return string
     */
    protected function make_link($s_link, $s_params)
    {
        if ($s_params) {
            $s_link .= (str_contains($s_link, '?') ? '&amp;' : '?') . $s_params;
        }
        return $s_link;
    }
    /**
     * If page number is not passed trying to fetch it from list of ids. To search
     * for position in list, article ids list and current article id must be passed
     *
     * @param int       $iPageNr  current page number (user defined or passed by request)
     * @param ListModel $oIdList  list of article ids (optional)
     * @param Article   $oArticle active article id (optional)
     *
     * @return int
     */
    protected function find_act_page_number($i_page_nr, $o_id_list = null, $o_article = null)
    {
        //page number
        $i_page_nr = (int) $i_page_nr;
        // maybe there is no page number passed, but we still can find the position in id's list
        if (!$i_page_nr && $o_id_list && $o_article) {
            $i_nrof_cat_articles = (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
            $i_nrof_cat_articles = $i_nrof_cat_articles ?: 1;
            $s_parent_id_field = 'oxarticles__oxparentid';
            $s_article_id = $o_article->{$s_parent_id_field}->value ?: $o_article->get_id();
            $i_pos = Registry::get_utils()->array_string_search($s_article_id, $o_id_list->array_keys());
            $i_page_nr = floor($i_pos / $i_nrof_cat_articles);
        }
        return $i_page_nr;
    }
    /**
     * Gets current page number.
     *
     * @param int $iPageNr page number
     *
     * @return string $sPageNum
     */
    protected function get_page_number($i_page_nr)
    {
        //page number
        $i_page_nr = (int) $i_page_nr;
        return $i_page_nr > 0 ? "pgNr={$i_page_nr}" : '';
    }
    /**
     * Searches for current article in article list and sets previous/next product ids
     *
     * @param Article            $oArticle       current Article
     * @param object             $oIdList        articles list containing only fake article objects !!!
     * @param FrontendController $oLocatorTarget FrontendController object
     *
     * @return integer
     */
    protected function get_product_pos($o_article, $o_id_list, $o_locator_target)
    {
        // variant handling
        $s_oxid = $o_article->oxarticles__oxparentid->value ?: $o_article->get_id();
        if ($o_id_list->count() && isset($o_id_list[$s_oxid])) {
            $a_ids = $o_id_list->array_keys();
            $i_pos = Registry::get_utils()->array_string_search($s_oxid, $a_ids);
            if (array_key_exists($i_pos - 1, $a_ids)) {
                $o_back_product = ox_new(Article::class);
                $o_back_product->modify_cache_key('_locator');
                $o_back_product->set_no_variant_loading(true);
                if ($o_back_product->load($a_ids[$i_pos - 1])) {
                    $o_back_product->set_link_type($o_locator_target->get_link_type());
                    $this->_o_back_product = $o_back_product;
                }
            }
            if (array_key_exists($i_pos + 1, $a_ids)) {
                $o_next_product = ox_new(Article::class);
                $o_next_product->modify_cache_key('_locator');
                $o_next_product->set_no_variant_loading(true);
                if ($o_next_product->load($a_ids[$i_pos + 1])) {
                    $o_next_product->set_link_type($o_locator_target->get_link_type());
                    $this->_o_next_product = $o_next_product;
                }
            }
            return $i_pos + 1;
        }
        return 0;
    }
    /**
     * Template variable getter. Returns error message
     *
     * @return string
     */
    public function get_error_message()
    {
        return $this->_s_error_message;
    }
}