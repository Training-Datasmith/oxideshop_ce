<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Vendor manager
 */
class Vendor extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model implements \Oxid_Esales\Eshop\Core\Contract\I_Url
{
    protected static $_a_root_vendor = [];
    /**
     * @var string Name of current class
     */
    protected $_s_class_name = 'oxvendor';
    /**
     * Marker to load vendor article count info
     *
     * @var bool
     */
    protected $_bl_show_article_cnt = false;
    /**
     * Vendor article count (default is -1, which means not calculated)
     *
     * @var int
     */
    protected $_i_nr_of_articles = -1;
    /**
     * Marks that current object is managed by SEO
     *
     * @var bool
     */
    protected $_bl_is_seo_object = true;
    /**
     * Visibility of a vendor
     *
     * @var int
     */
    protected $_bl_is_visible;
    /**
     * has visible endors state of a category
     *
     * @var int
     */
    protected $_bl_has_visible_sub_cats;
    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_a_seo_urls = [];
    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        $this->set_show_article_cnt(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfShowActionCatArticleCnt'));
        parent::__construct();
        $this->init('oxvendor');
    }
    /**
     * Marker to load vendor article count info setter
     *
     * @param bool $blShowArticleCount Marker to load vendor article count
     */
    public function set_show_article_cnt($bl_show_article_count = false): void
    {
        $this->_bl_show_article_cnt = $bl_show_article_count;
    }
    /**
     * Assigns to $this object some base parameters/values.
     *
     * @param array $dbRecord parameters/values
     */
    public function assign($db_record): void
    {
        parent::assign($db_record);
        // vendor article count is stored in cache
        if ($this->_bl_show_article_cnt && !$this->is_admin()) {
            $this->_i_nr_of_articles = \Oxid_Esales\Eshop\Core\Registry::get_utils_count()->get_vendor_article_count($this->get_id());
        }
        $this->oxvendor__oxnrofarticles = new \Oxid_Esales\Eshop\Core\Field($this->_i_nr_of_articles, \Oxid_Esales\Eshop\Core\Field::T_RAW);
    }
    /**
     * Loads object data from DB (object data ID is passed to method). Returns
     * true on success.
     *
     * @param string $sOxid object id
     *
     * @return bool
     */
    public function load($s_oxid)
    {
        if ($s_oxid == 'root') {
            return $this->set_root_object_data();
        }
        return parent::load($s_oxid);
    }
    /**
     * Sets root vendor data. Returns true
     *
     * @return bool
     */
    protected function set_root_object_data()
    {
        $this->set_id('root');
        $this->oxvendor__oxicon = new \Oxid_Esales\Eshop\Core\Field('', \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxvendor__oxtitle = new \Oxid_Esales\Eshop\Core\Field(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('BY_VENDOR', $this->get_language(), false), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxvendor__oxshortdesc = new \Oxid_Esales\Eshop\Core\Field('', \Oxid_Esales\Eshop\Core\Field::T_RAW);
        return true;
    }
    /**
     * Returns raw content seo url
     *
     * @param int $iLang language id
     * @param int $iPage page number [optional]
     *
     * @return string
     */
    public function get_base_seo_link($i_lang, $i_page = 0)
    {
        $o_encoder = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Vendor::class);
        if (!$i_page) {
            return $o_encoder->get_vendor_url($this, $i_lang);
        }
        return $o_encoder->get_vendor_page_url($this, $i_page, $i_lang);
    }
    /**
     * Returns vendor link Url
     *
     * @param int $iLang language id [optional]
     *
     * @return string
     */
    public function get_link($i_lang = null)
    {
        if (!\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active()) {
            return $this->get_std_link($i_lang);
        }
        if ($i_lang === null) {
            $i_lang = $this->get_language();
        }
        if (!isset($this->_a_seo_urls[$i_lang])) {
            $this->_a_seo_urls[$i_lang] = $this->get_base_seo_link($i_lang);
        }
        return $this->_a_seo_urls[$i_lang];
    }
    /**
     * Returns base dynamic url: shopurl/index.php?cl=details
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function get_base_std_link($i_lang, $bl_add_id = true, $bl_full = true)
    {
        $s_url = '';
        if ($bl_full) {
            //always returns shop url, not admin
            $s_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url($i_lang, false);
        }
        return $s_url . 'index.php?cl=vendorlist' . ($bl_add_id ? '&amp;cnid=v_' . $this->get_id() : '');
    }
    /**
     * Returns standard URL to vendor
     *
     * @param int   $iLang   language
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function get_std_link($i_lang = null, $a_params = [])
    {
        if ($i_lang === null) {
            $i_lang = $this->get_language();
        }
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->process_url($this->get_base_std_link($i_lang), true, $a_params, $i_lang);
    }
    /**
     * returns number or articles of this vendor
     *
     * @return integer
     */
    public function get_nr_of_articles()
    {
        if (!$this->_bl_show_article_cnt || $this->is_admin()) {
            return -1;
        }
        return $this->_i_nr_of_articles;
    }
    /**
     * returns the sub category array
     */
    public function get_sub_cats()
    {
    }
    /**
     * returns the visibility of a vendor
     *
     * @return bool
     */
    public function get_is_visible()
    {
        return $this->_bl_is_visible;
    }
    /**
     * sets the visibilty of a category
     *
     * @param bool $blVisible vendors visibility status setter
     */
    public function set_is_visible($bl_visible): void
    {
        $this->_bl_is_visible = $bl_visible;
    }
    /**
     * returns if a vendor has visible sub categories
     *
     * @return bool
     */
    public function get_has_visible_sub_cats()
    {
        if (!isset($this->_bl_has_visible_sub_cats)) {
            $this->_bl_has_visible_sub_cats = false;
        }
        return $this->_bl_has_visible_sub_cats;
    }
    /**
     * sets the state of has visible sub vendors
     *
     * @param bool $blHasVisibleSubcats marker if vendor has visible subcategories
     */
    public function set_has_visible_sub_cats($bl_has_visible_subcats): void
    {
        $this->_bl_has_visible_sub_cats = $bl_has_visible_subcats;
    }
    /**
     * Empty method, called in templates when vendor is used in same code like category
     */
    public function get_content_cats()
    {
    }
    /**
     * Delete this object from the database, returns true on success.
     *
     * @param string $oxid Object ID(default null)
     *
     * @return bool
     */
    public function delete($oxid = null)
    {
        if ($oxid) {
            $this->load($oxid);
        } else {
            $oxid = $this->get_id();
        }
        if (parent::delete($oxid)) {
            \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Vendor::class)->on_delete_vendor($this);
            return true;
        }
        return false;
    }
    /**
     * Returns article picture
     *
     * @return string
     */
    public function get_icon_url()
    {
        if ($s_icon = $this->oxvendor__oxicon->value) {
            $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            $s_size = $o_config->get_config_param('sManufacturerIconsize');
            if (!isset($s_size)) {
                $s_size = $o_config->get_config_param('sIconsize');
            }
            return \Oxid_Esales\Eshop\Core\Registry::get_picture_handler()->get_pic_url('vendor/icon/', $s_icon, $s_size);
        }
    }
    /**
     * Returns category thumbnail picture url if exist, false - if not
     *
     * @return mixed
     */
    public function get_thumb_url()
    {
        return false;
    }
    /**
     * Returns vendor title
     *
     * @return string
     */
    public function get_title()
    {
        return $this->oxvendor__oxtitle->value;
    }
    /**
     * Returns short description
     *
     * @return string
     */
    public function get_short_description()
    {
        return $this->oxvendor__oxshortdesc->value;
    }
}