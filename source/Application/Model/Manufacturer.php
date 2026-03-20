<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Contract\I_Url;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Model\Multi_Language_Model;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Utils_File;
use Oxid_Esales\Eshop\Core\Utils_Pic;
use Symfony\Component\Filesystem\Path;
/**
 * Manufacturer manager
 */
class Manufacturer extends Multi_Language_Model implements I_Url
{
    protected static $_a_root_manufacturer = [];
    /**
     * @var string Name of current class
     */
    protected $_s_class_name = 'oxmanufacturer';
    /**
     * Marker to load manufacturer article count info
     *
     * @var bool
     */
    protected $_bl_show_article_cnt = false;
    /**
     * Manufacturer article count (default is -1, which means not calculated)
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
     * Visibility of a manufacturer
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
        $this->set_show_article_cnt(Registry::get_config()->get_config_param('bl_perfShowActionCatArticleCnt'));
        parent::__construct();
        $this->init('oxmanufacturers');
    }
    /**
     * Extra getter to guarantee compatibility with templates
     *
     * @param string $sName name of variable to return
     *
     * @return mixed
     */
    public function __get($s_name)
    {
        return match ($s_name) {
            'oxurl', 'openlink', 'closelink', 'link' => $this->get_link(),
            'iArtCnt' => $this->get_nr_of_articles(),
            'isVisible' => $this->get_is_visible(),
            'hasVisibleSubCats' => $this->get_has_visible_sub_cats(),
            default => parent::__get($s_name),
        };
    }
    /**
     * Marker to load manufacturer article count info setter
     *
     * @param bool $blShowArticleCount Marker to load manufacturer article count
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
        // manufacturer article count is stored in cache
        if ($this->_bl_show_article_cnt && !$this->is_admin()) {
            $this->_i_nr_of_articles = Registry::get_utils_count()->get_manufacturer_article_count($this->get_id());
        }
        $this->oxmanufacturers__oxnrofarticles = new Field($this->_i_nr_of_articles, Field::T_RAW);
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
     * Sets root manufacturer data. Returns true
     *
     * @return bool
     */
    protected function set_root_object_data()
    {
        $this->set_id('root');
        $this->oxmanufacturers__oxtitle = new Field(Registry::get_lang()->translate_string('BY_MANUFACTURER', $this->get_language(), false), Field::T_RAW);
        $this->oxmanufacturers__oxshortdesc = new Field('', Field::T_RAW);
        $this->oxmanufacturers__oxicon = new Field('', Field::T_RAW);
        return true;
    }
    /**
     * Returns raw manufacturer seo url
     *
     * @param int $iLang language id
     * @param int $iPage page number [optional]
     *
     * @return string
     */
    public function get_base_seo_link($i_lang, $i_page = 0)
    {
        $o_encoder = Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Manufacturer::class);
        if (!$i_page) {
            return $o_encoder->get_manufacturer_url($this, $i_lang);
        }
        return $o_encoder->get_manufacturer_page_url($this, $i_page, $i_lang);
    }
    /**
     * Returns manufacturer link Url
     *
     * @param int $iLang language id [optional]
     *
     * @return string
     */
    public function get_link($i_lang = null)
    {
        if (!Registry::get_utils()->seo_is_active()) {
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
            $s_url = Registry::get_config()->get_shop_url($i_lang, false);
        }
        return $s_url . 'index.php?cl=manufacturerlist' . ($bl_add_id ? '&amp;mnid=' . $this->get_id() : '');
    }
    /**
     * Returns standard URL to manufacturer
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
        return Registry::get_utils_url()->process_url($this->get_base_std_link($i_lang), true, $a_params, $i_lang);
    }
    /**
     * returns number or articles of this manufacturer
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
     * returns the visibility of a manufacturer
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
     * @param bool $blVisible manufacturers visibility status setter
     */
    public function set_is_visible($bl_visible): void
    {
        $this->_bl_is_visible = $bl_visible;
    }
    /**
     * returns if a manufacturer has visible sub categories
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
     * sets the state of has visible sub manufacturers
     *
     * @param bool $blHasVisibleSubcats marker if manufacturer has visible subcategories
     */
    public function set_has_visible_sub_cats($bl_has_visible_subcats): void
    {
        $this->_bl_has_visible_sub_cats = $bl_has_visible_subcats;
    }
    /**
     * Empty method, called in templates when manufacturer is used in same code like category
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
        if (!$oxid) {
            $oxid = $this->get_id();
        }
        $this->load($oxid);
        if (parent::delete($oxid)) {
            Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Manufacturer::class)->on_delete_manufacturer($this);
            $this->delete_picture($this->oxmanufacturers__oxicon->value, 'MICO', 'oxicon');
            $this->delete_picture($this->oxmanufacturers__oxicon_alt->value, 'MICO', 'oxicon_alt');
            $this->delete_picture($this->oxmanufacturers__oxpicture->value, 'MPIC', 'oxpicture');
            $this->delete_picture($this->oxmanufacturers__oxthumbnail->value, 'MTHU', 'oxthumbnail');
            $this->delete_picture($this->oxmanufacturers__oxpromotion_icon->value, 'MPICO', 'oxpromotion_icon');
            return true;
        }
        return false;
    }
    public function get_image_type(string $field_name): ?string
    {
        return match ($field_name) {
            'oxicon', 'oxicon_alt' => 'MICO',
            'oxpicture' => 'MPIC',
            'oxthumbnail' => 'MTHU',
            'oxpromotion_icon' => 'MPICO',
            default => null,
        };
    }
    public function get_icon_url(): string
    {
        $image_name = $this->oxmanufacturers__oxicon->value;
        return $this->get_image_url($image_name, 'sManufacturerIconsize', 'icon') ?? '';
    }
    public function get_icon_alt_url(): string
    {
        $image_name = $this->oxmanufacturers__oxicon_alt->value;
        return $this->get_image_url($image_name, 'sManufacturerIconsize', 'icon') ?? '';
    }
    public function get_picture_url(): string
    {
        $image_name = $this->oxmanufacturers__oxpicture->value;
        return $this->get_image_url($image_name, 'sManufacturerPicturesize', 'picture') ?? '';
    }
    public function get_thumbnail_url(): string
    {
        $image_name = $this->oxmanufacturers__oxthumbnail->value;
        return $this->get_image_url($image_name, 'sManufacturerThumbnailsize', 'thumb') ?? '';
    }
    public function get_promotion_icon_url(): string
    {
        $image_name = $this->oxmanufacturers__oxpromotion_icon->value;
        return $this->get_image_url($image_name, 'sManufacturerPromotionsize', 'promo_icon') ?? '';
    }
    /**
     * Returns false, because manufacturer has not thumbnail
     * @return false
     * @deprecated since v7.0.0 (2023-03-16). Please use Manufacturer::getThumbnailUrl() instead.
     */
    public function get_thumb_url()
    {
        return false;
    }
    /**
     * Returns manufacturer title
     *
     * @return string
     */
    public function get_title()
    {
        return $this->oxmanufacturers__oxtitle->value;
    }
    /**
     * Returns short description
     *
     * @return string
     */
    public function get_short_description()
    {
        return $this->oxmanufacturers__oxshortdesc->value;
    }
    public function delete_picture(string $picture_name, string $picture_type, string $picture_field_name): void
    {
        /** @var UtilsPic $utilsPic */
        $utils_pic = Registry::get_utils_pic();
        /** @var UtilsFile $utilsFile */
        $utils_file = Registry::get_utils_file();
        $picture_directory = Registry::get_config()->get_picture_dir(false);
        $utils_pic->safe_picture_delete($picture_name, Path::join($picture_directory, $utils_file->get_image_dir_by_type($picture_type)), 'oxmanufacturers', $picture_field_name);
    }
    private function get_image_url(mixed $image_name, string $param_name, string $directory_name): string
    {
        $config = Registry::get_config();
        if (empty($size = $config->get_config_param($param_name))) {
            $size = $config->get_config_param('sIconsize');
        }
        $path = 'manufacturer' . DIRECTORY_SEPARATOR . $directory_name . DIRECTORY_SEPARATOR;
        if ($url = Registry::get_picture_handler()->get_pic_url($path, $image_name, $size)) {
            return $url;
        }
        return '';
    }
}