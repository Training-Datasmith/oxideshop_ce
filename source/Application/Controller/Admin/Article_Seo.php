<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Article seo config class
 */
class Article_Seo extends \Oxid_Esales\Eshop\Application\Controller\Admin\Object_Seo
{
    /**
     * Chosen category id
     *
     * @var string
     */
    protected $_s_act_cat_id;
    /**
     * Product selections (categories, vendors etc assigned)
     *
     * @var array
     */
    protected $_a_selection_list;
    /**
     * Returns active selection type - oxcategory, oxmanufacturer, oxvendor
     *
     * @return string
     */
    public function get_act_cat_type()
    {
        $s_type = false;
        $a_data = Registry::get_request()->get_request_escaped_parameter('aSeoData');
        if ($a_data && isset($a_data['oxparams'])) {
            $o_str = Str::get_str();
            $i_end_pos = $o_str->strpos($a_data['oxparams'], '#');
            $s_type = $o_str->substr($a_data['oxparams'], 0, $i_end_pos);
        } elseif ($a_list = $this->get_selection_list()) {
            $s_type = array_key_first($a_list);
        }
        return $s_type;
    }
    /**
     * Returns active category (manufacturer/vendor) language id
     *
     * @return int
     */
    public function get_act_cat_lang()
    {
        if (Registry::get_request()->get_request_escaped_parameter('editlanguage') !== null) {
            return $this->_i_edit_lang;
        }
        $i_lang = false;
        $a_data = Registry::get_request()->get_request_escaped_parameter('aSeoData');
        if ($a_data && isset($a_data['oxparams'])) {
            $o_str = Str::get_str();
            $i_start_pos = $o_str->strpos($a_data['oxparams'], '#');
            $i_end_pos = $o_str->strpos($a_data['oxparams'], '#', $i_start_pos + 1);
            $i_lang = $o_str->substr($a_data['oxparams'], $i_end_pos + 1);
        } elseif ($a_list = $this->get_selection_list()) {
            $a_list = reset($a_list);
            $i_lang = key($a_list);
        }
        return (int) $i_lang;
    }
    /**
     * Returns active category (manufacturer/vendor) id
     *
     * @return false|string
     */
    public function get_act_cat_id()
    {
        $s_id = false;
        $a_data = Registry::get_request()->get_request_escaped_parameter('aSeoData');
        if ($a_data && isset($a_data['oxparams'])) {
            $o_str = Str::get_str();
            $i_start_pos = $o_str->strpos($a_data['oxparams'], '#');
            $i_end_pos = $o_str->strpos($a_data['oxparams'], '#', $i_start_pos + 1);
            $i_len = $o_str->strlen($a_data['oxparams']);
            $s_id = $o_str->substr($a_data['oxparams'], $i_start_pos + 1, $i_end_pos - $i_len);
        } elseif ($a_list = $this->get_selection_list()) {
            $o_item = reset($a_list[$this->get_act_cat_type()][$this->get_act_cat_lang()]);
            $s_id = $o_item->get_id();
        }
        return $s_id;
    }
    /**
     * Returns product selections array [type][language] (categories, vendors etc assigned)
     *
     * @return array
     */
    public function get_selection_list()
    {
        if ($this->_a_selection_list === null) {
            $this->_a_selection_list = [];
            $o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_product->load($this->get_edit_object_id());
            if ($o_cat_list = $this->get_category_list($o_product)) {
                $this->_a_selection_list['oxcategory'][$this->_i_edit_lang] = $o_cat_list;
            }
            if ($o_vnd_list = $this->get_vendor_list($o_product)) {
                $this->_a_selection_list['oxvendor'][$this->_i_edit_lang] = $o_vnd_list;
            }
            if ($o_man_list = $this->get_manufacturer_list($o_product)) {
                $this->_a_selection_list['oxmanufacturer'][$this->_i_edit_lang] = $o_man_list;
            }
        }
        return $this->_a_selection_list;
    }
    /**
     * Returns array of product categories
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article object
     *
     * @return array
     */
    protected function get_category_list($article)
    {
        $main_category_id = false;
        if ($main_category = $article->get_category()) {
            $main_category_id = $main_category->get_id();
        }
        $category_list = [];
        $language = $this->get_edit_lang();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $view = $table_view_name_generator->get_view_name('oxobject2category');
        $query_for_price_categories = $article->get_sql_for_price_categories('oxid');
        $query = "select oxobject2category.oxcatnid as oxid from {$view} as oxobject2category " . 'where oxobject2category.oxobjectid = :oxobjectid union ' . $query_for_price_categories;
        $categories_ids = Database_Provider::get_db()->get_col($query, ['oxobjectid' => $article->get_id()]);
        foreach ($categories_ids as $category_id) {
            $category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            if ($category->load_in_lang($language, $category_id)) {
                if ($main_category_id == $category->get_id()) {
                    $suffix = Registry::get_lang()->translate_string('(main category)', $this->get_edit_lang());
                    $title_field = 'oxcategories__oxtitle';
                    $title = $category->{$title_field}->get_raw_value() . ' ' . $suffix;
                    $category->{$title_field} = new Field($title, Field::T_RAW);
                }
                $category_list[] = $category;
            }
        }
        return $category_list;
    }
    /**
     * Returns array containing product vendor object
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article object
     *
     * @return array
     */
    protected function get_vendor_list($o_article)
    {
        if ($o_article->oxarticles__oxvendorid->value) {
            $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
            if ($o_vendor->load_in_lang($this->get_edit_lang(), $o_article->oxarticles__oxvendorid->value)) {
                return [$o_vendor];
            }
        }
    }
    /**
     * Returns array containing product manufacturer object
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article object
     *
     * @return array
     */
    protected function get_manufacturer_list($o_article)
    {
        if ($o_article->oxarticles__oxmanufacturerid->value) {
            $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
            if ($o_manufacturer->load_in_lang($this->get_edit_lang(), $o_article->oxarticles__oxmanufacturerid->value)) {
                return [$o_manufacturer];
            }
        }
    }
    /**
     * Returns active category object, used for seo url getter
     *
     * @return \OxidEsales\Eshop\Application\Model\Category|null
     */
    public function get_act_category()
    {
        $o_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        return $o_cat->load($this->get_act_cat_id()) ? $o_cat : null;
    }
    /**
     * Returns active vendor object if available
     *
     * @return \OxidEsales\Eshop\Application\Model\Vendor|null
     */
    public function get_act_vendor()
    {
        $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        return $this->get_act_cat_type() == 'oxvendor' && $o_vendor->load($this->get_act_cat_id()) ? $o_vendor : null;
    }
    /**
     * Returns active manufacturer object if available
     *
     * @return \OxidEsales\Eshop\Application\Model\Manufacturer|null
     */
    public function get_act_manufacturer()
    {
        $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
        $bl_loaded = $this->get_act_cat_type() == 'oxmanufacturer' && $o_manufacturer->load($this->get_act_cat_id());
        return $bl_loaded ? $o_manufacturer : null;
    }
    /**
     * Returns list type for current seo url
     *
     * @return string
     */
    public function get_list_type()
    {
        switch ($this->get_act_cat_type()) {
            case 'oxvendor':
                return 'vendor';
            case 'oxmanufacturer':
                return 'manufacturer';
        }
    }
    /**
     * Returns editable object language id
     *
     * @return int
     */
    public function get_edit_lang()
    {
        return $this->get_act_cat_lang();
    }
    /**
     * Returns alternative seo entry id
     */
    protected function get_alt_seo_entry_id()
    {
        return $this->get_edit_object_id();
    }
    /**
     * Returns url type
     *
     * @return string
     */
    protected function get_type()
    {
        return 'oxarticle';
    }
    /**
     * Processes parameter before writing to db
     *
     * @param string $sParam parameter to process
     *
     * @return string
     */
    public function process_param($s_param)
    {
        return $this->get_act_cat_id();
    }
    /**
     * Returns current object type seo encoder object
     *
     * @return \OxidEsales\Eshop\Application\Model\SeoEncoderArticle
     */
    protected function get_encoder()
    {
        return Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Article::class);
    }
    /**
     * Returns seo uri
     *
     * @return string
     */
    public function get_entry_uri()
    {
        $product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        if ($product->load($this->get_edit_object_id())) {
            $seo_encoder = $this->get_encoder();
            switch ($this->get_act_cat_type()) {
                case 'oxvendor':
                    return $seo_encoder->get_article_vendor_uri($product, $this->get_edit_lang());
                case 'oxmanufacturer':
                    return $seo_encoder->get_article_manufacturer_uri($product, $this->get_edit_lang());
                default:
                    if ($this->get_act_cat_id()) {
                        return $seo_encoder->get_article_uri($product, $this->get_edit_lang());
                    }
                    return $seo_encoder->get_article_main_uri($product, $this->get_edit_lang());
            }
        }
    }
    /**
     * Returns TRUE, as this view support category selector
     *
     * @return bool
     */
    public function show_cat_select()
    {
        return true;
    }
    /**
     * Returns TRUE if current seo entry has fixed state
     *
     * @return bool
     */
    public function is_entry_fixed()
    {
        \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_id = $this->get_save_object_id();
        $i_lang = (int) $this->get_edit_lang();
        $i_shop_id = Registry::get_config()->get_shop_id();
        $s_param = $this->process_param($this->get_act_cat_id());
        $s_q = 'select oxfixed from oxseo where
                   oxseo.oxobjectid = :oxobjectid and
                   oxseo.oxshopid = :oxshopid and oxseo.oxlang = :oxlang and oxparams = :oxparams';
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (bool) \Oxid_Esales\Eshop\Core\Database_Provider::get_master()->get_one($s_q, ['oxobjectid' => $s_id, 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang, 'oxparams' => $s_param]);
    }
}