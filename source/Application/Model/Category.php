<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Category manager.
 * Collects category information (articles, etc.), performs insertion/deletion
 * of categories nodes. By recursion methods are set structure of category.
 */
#[\Allow_Dynamic_Properties]
class Category extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model implements \Oxid_Esales\Eshop\Core\Contract\I_Url
{
    /**
     * Subcategories array.
     *
     * @var array
     */
    protected $_a_sub_cats = [];
    /**
     * Content category array.
     *
     * @var array
     */
    protected $_a_content_cats = [];
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxcategory';
    /**
     * number of articles in the current category
     *
     * @var int
     */
    protected $_i_nr_of_articles;
    /**
     * visibility of a category
     *
     * @var int
     */
    protected $_bl_is_visible;
    /**
     * expanded state of a category
     *
     * @var int
     */
    protected $_bl_expanded;
    /**
     * visibility of a category
     *
     * @var int
     */
    protected $_bl_has_sub_cats;
    /**
     * has visible sub categories state of a category
     *
     * @var int
     */
    protected $_bl_has_visible_sub_cats;
    /**
     * Marks that current object is managed by SEO
     *
     * @var bool
     */
    protected $_bl_is_seo_object = true;
    /**
     * Set $_blUseLazyLoading to true if you want to load only actually used fields not full object, depending on views.
     *
     * @var bool
     */
    protected $_bl_use_lazy_loading = false;
    /**
     * Dyn image dir
     *
     * @var string
     */
    protected $_s_dyn_image_dir;
    /**
     * Top category marker
     *
     * @var bool
     */
    protected $_bl_top_category;
    /**
     * Standard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_a_std_urls = [];
    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_a_seo_urls = [];
    /**
     * Category attributes cache
     *
     * @var array
     */
    protected static $_a_cat_attributes = [];
    /**
     * Parent category object container.
     *
     * @var \OxidEsales\Eshop\Application\Model\Category
     */
    protected $_o_parent;
    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxcategories');
    }
    /**
     * Gets default sorting value
     */
    public function get_default_sorting()
    {
        return $this->get_field_data('oxdefsort');
    }
    /**
     * Gets default sorting mode value
     *
     * @return string
     */
    public function get_default_sorting_mode()
    {
        return $this->oxcategories__oxdefsortmode->value;
    }
    /**
     * Extra getter to guarantee compatibility with templates
     *
     * @param string $sName name of variable to get
     *
     * @return string
     */
    public function __get($s_name)
    {
        return match ($s_name) {
            'aSubCats' => $this->_a_sub_cats,
            'aContent' => $this->_a_content_cats,
            'iArtCnt' => $this->get_nr_of_articles(),
            'isVisible' => $this->get_is_visible(),
            'expanded' => $this->get_expanded(),
            'hasSubCats' => $this->get_has_sub_cats(),
            'hasVisibleSubCats' => $this->get_has_visible_sub_cats(),
            //case 'toListLink':
            //case 'noparamlink':
            'openlink', 'closelink', 'link' => $this->get_link(),
            'dimagedir' => $this->get_picture_url(),
            default => parent::__get($s_name),
        };
    }
    /**
     * Get data from db
     *
     * @param string $sOXID id
     *
     * @return array
     */
    protected function load_from_db($s_oxid)
    {
        $s_select = $this->build_select_string(["`{$this->get_view_name()}`.`oxid`" => $s_oxid]);
        return Database_Provider::get_db()->get_row($s_select);
    }
    /**
     * Load category data
     *
     * @param string $sOXID id
     *
     * @return bool
     */
    public function load($s_oxid)
    {
        $a_data = $this->load_from_db($s_oxid);
        if ($a_data) {
            $this->assign($a_data);
            $this->_is_loaded = true;
            return true;
        }
        return false;
    }
    /**
     * Loads and assigns object data from DB.
     *
     * @param mixed $dbRecord database record array
     */
    public function assign($db_record)
    {
        $this->_i_nr_of_articles = null;
        //clear seo urls
        $this->_a_seo_urls = [];
        return parent::assign($db_record);
    }
    /**
     * Delete empty categories, returns true on success.
     *
     * @param string $sOXID Object ID
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$this->get_id()) {
            $this->load($s_oxid);
        }
        $s_oxid ??= $this->get_id();
        $my_config = Registry::get_config();
        $o_db = Database_Provider::get_db();
        $bl_ret = false;
        if ($this->oxcategories__oxright->value == $this->oxcategories__oxleft->value + 1) {
            $my_utils_pic = Registry::get_utils_pic();
            $s_dir = $my_config->get_picture_dir(false);
            // only delete empty categories
            // #1173M - not all pic are deleted, after article is removed
            $my_utils_pic->safe_picture_delete($this->get_field_data('oxthumb'), $s_dir . Registry::get_utils_file()->get_image_dir_by_type('TC'), 'oxcategories', 'oxthumb');
            $my_utils_pic->safe_picture_delete($this->get_field_data('oxicon'), $s_dir . Registry::get_utils_file()->get_image_dir_by_type('CICO'), 'oxcategories', 'oxicon');
            $my_utils_pic->safe_picture_delete($this->get_field_data('oxpromoicon'), $s_dir . Registry::get_utils_file()->get_image_dir_by_type('PICO'), 'oxcategories', 'oxpromoicon');
            $query = 'UPDATE oxcategories SET OXLEFT = OXLEFT - 2
                      WHERE OXROOTID = :oxrootid AND
                            OXLEFT > :oxleft AND
                            OXSHOPID = :oxshopid';
            $o_db->execute($query, ['oxrootid' => $this->oxcategories__oxrootid->value, 'oxleft' => (int) $this->oxcategories__oxleft->value, 'oxshopid' => $this->get_shop_id()]);
            $query = 'UPDATE oxcategories SET OXRIGHT = OXRIGHT - 2
                      WHERE OXROOTID = :oxrootid AND
                            OXRIGHT > :oxright AND
                            OXSHOPID = :oxshopid';
            $o_db->execute($query, ['oxrootid' => $this->oxcategories__oxrootid->value, 'oxright' => (int) $this->oxcategories__oxright->value, 'oxshopid' => $this->get_shop_id()]);
            // delete entry
            $bl_ret = parent::delete($s_oxid);
            // delete links to articles
            $o_db->execute('delete from oxobject2category where oxobject2category.oxcatnid = :oxid', ['oxid' => $s_oxid]);
            // #657 ADDITIONAL delete links to attributes
            $o_db->execute('delete from oxcategory2attribute where oxcategory2attribute.oxobjectid = :oxid', ['oxid' => $s_oxid]);
            // A. removing assigned:
            // - deliveries
            $o_db->execute('delete from oxobject2delivery where oxobject2delivery.oxobjectid = :oxid', ['oxid' => $s_oxid]);
            // - discounts
            $o_db->execute('delete from oxobject2discount where oxobject2discount.oxobjectid = :oxid', ['oxid' => $s_oxid]);
            Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class)->on_delete_category($this);
        }
        return $bl_ret;
    }
    /**
     * returns the sub category array
     *
     * @return array
     */
    public function get_sub_cats()
    {
        return $this->_a_sub_cats;
    }
    /**
     * returns a specific sub category
     *
     * @param string $sKey the key of the category
     *
     * @return object
     */
    public function get_sub_cat($s_key)
    {
        return $this->_a_sub_cats[$s_key];
    }
    /**
     * Sets an array of sub categories, also handles parent hasVisibleSubCats
     *
     * @param array $aCats array of categories
     */
    public function set_sub_cats($a_cats): void
    {
        $this->_a_sub_cats = $a_cats;
        foreach ($a_cats as $o_cat) {
            // keeping ref. to parent
            $o_cat->set_parent_category($this);
            if ($o_cat->get_is_visible()) {
                $this->set_has_visible_sub_cats(true);
            }
        }
    }
    /**
     * sets a single category, handles sorting and parent hasVisibleSubCats
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat the category
     * @param string                                       $sKey (optional, default=null)  the key for that category,
     *                                                           without a key, the category is just added to the array
     */
    public function set_sub_cat($o_cat, $s_key = null): void
    {
        if ($s_key) {
            $this->_a_sub_cats[$s_key] = $o_cat;
        } else {
            $this->_a_sub_cats[] = $o_cat;
        }
        // keeping ref. to parent
        $o_cat->set_parent_category($this);
        if ($o_cat->get_is_visible()) {
            $this->set_has_visible_sub_cats(true);
        }
    }
    /**
     * returns the content category array
     *
     * @return array
     */
    public function get_content_cats()
    {
        return $this->_a_content_cats;
    }
    /**
     * Sets an array of content categories
     *
     * @param array $aContent array of content
     */
    public function set_content_cats($a_content): void
    {
        $this->_a_content_cats = $a_content;
    }
    /**
     * sets a single category
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oContent the category
     * @param string                                       $sKey     optional, the key for that category,
     *                                                               without a key, the category is just added to the array
     */
    public function set_content_cat($o_content, $s_key = null): void
    {
        if ($s_key) {
            $this->_a_content_cats[$s_key] = $o_content;
        } else {
            $this->_a_content_cats[] = $o_content;
        }
    }
    /**
     * returns number or articles in category
     *
     * @return integer
     */
    public function get_nr_of_articles()
    {
        $my_config = Registry::get_config();
        if (!isset($this->_i_nr_of_articles) && !$this->is_admin() && ($my_config->get_config_param('bl_perfShowActionCatArticleCnt') || $my_config->get_config_param('blDontShowEmptyCategories'))) {
            if ($this->is_price_category()) {
                $this->_i_nr_of_articles = Registry::get_utils_count()->get_price_cat_article_count($this->get_id(), $this->get_field_data('oxpricefrom'), $this->get_field_data('oxpriceto'));
            } else {
                $this->_i_nr_of_articles = Registry::get_utils_count()->get_cat_article_count($this->get_id());
            }
        }
        return (int) $this->_i_nr_of_articles;
    }
    /**
     * sets the number or articles in category
     *
     * @param int $iNum category product count setter
     */
    public function set_nr_of_articles($i_num): void
    {
        $this->_i_nr_of_articles = $i_num;
    }
    /**
     * returns the visibility of a category, handles hidden and empty categories
     *
     * @return bool
     */
    public function get_is_visible()
    {
        if (!isset($this->_bl_is_visible)) {
            if (Registry::get_config()->get_config_param('blDontShowEmptyCategories')) {
                $bl_empty = $this->get_nr_of_articles() < 1 && !$this->get_has_visible_sub_cats();
            } else {
                $bl_empty = false;
            }
            $this->_bl_is_visible = !($bl_empty || $this->oxcategories__oxhidden->value);
        }
        return $this->_bl_is_visible;
    }
    /**
     * sets the visibility of a category
     *
     * @param bool $blVisible category visibility status setter
     */
    public function set_is_visible($bl_visible): void
    {
        $this->_bl_is_visible = $bl_visible;
    }
    /**
     * Returns dyn image dir
     *
     * @return string
     */
    public function get_picture_url()
    {
        if ($this->_s_dyn_image_dir === null) {
            $s_this_shop = $this->oxcategories__oxshopid->value;
            $this->_s_dyn_image_dir = Registry::get_config()->get_picture_url(null, false, null, null, $s_this_shop);
        }
        return $this->_s_dyn_image_dir;
    }
    /**
     * Returns raw category seo url
     *
     * @param int $iLang language id
     * @param int $iPage page number [optional]
     *
     * @return string
     */
    public function get_base_seo_link($i_lang, $i_page = 0)
    {
        $o_encoder = Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class);
        if (!$i_page) {
            return $o_encoder->get_category_url($this, $i_lang);
        }
        return $o_encoder->get_category_page_url($this, $i_page, $i_lang);
    }
    /**
     * returns the url of the category
     *
     * @param int $iLang language id
     *
     * @return string
     */
    public function get_link($i_lang = null)
    {
        if (!Registry::get_utils()->seo_is_active() || $this->get_field_data('oxextlink')) {
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
     * sets the url of the category
     *
     * @param string $sLink category url
     */
    public function set_link($s_link): void
    {
        $i_lang = $this->get_language();
        if (Registry::get_utils()->seo_is_active()) {
            $this->_a_seo_urls[$i_lang] = $s_link;
        } else {
            $this->_a_std_urls[$i_lang] = $s_link;
        }
    }
    /**
     * Returns SQL select string with checks if items are available
     *
     * @param bool $blForceCoreTable forces core table usage (optional)
     *
     * @return string
     */
    public function get_sql_active_snippet($bl_force_core_table = null)
    {
        $s_q = parent::get_sql_active_snippet($bl_force_core_table);
        $s_table = $this->get_view_name($bl_force_core_table);
        $s_q .= (strlen($s_q) ? ' and ' : '') . " {$s_table}.oxhidden = '0' ";
        $s_q .= $this->get_additional_sql_filter($bl_force_core_table);
        return "( {$s_q} ) ";
    }
    /**
     * Additional SQL conditions for selecting articles snippet
     *
     * @param bool $forceCoreTable
     * @return string
     */
    protected function get_additional_sql_filter($force_core_table)
    {
        return '';
    }
    /**
     * Returns base dynamic url: shopUrl/index.php?cl=details
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function get_base_std_link($i_lang, $bl_add_id = true, $bl_full = true)
    {
        $external_link = $this->get_field_data('oxextlink');
        if ($external_link) {
            return $external_link;
        }
        $s_url = '';
        if ($bl_full) {
            //always returns shop url, not admin
            $s_url = Registry::get_config()->get_shop_url($i_lang, false);
        }
        //always returns shop url, not admin
        return $s_url . 'index.php?cl=alist' . ($bl_add_id ? '&amp;cnid=' . $this->get_id() : '');
    }
    /**
     * Returns standard URL to category
     *
     * @param int   $iLang   language
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function get_std_link($i_lang = null, $a_params = [])
    {
        $external_link = $this->get_field_data('oxextlink');
        if ($external_link) {
            return Registry::get_utils_url()->process_url($external_link);
        }
        if ($i_lang === null) {
            $i_lang = $this->get_language();
        }
        if (!isset($this->_a_std_urls[$i_lang])) {
            $this->_a_std_urls[$i_lang] = $this->get_base_std_link($i_lang);
        }
        return Registry::get_utils_url()->process_url($this->_a_std_urls[$i_lang], true, $a_params, $i_lang);
    }
    /**
     * returns the expanded state of the category
     *
     * @return bool
     */
    public function get_expanded()
    {
        return $this->_bl_expanded;
    }
    /**
     * set the expanded state of the category
     *
     * @param bool $blExpanded expanded status setter
     */
    public function set_expanded($bl_expanded): void
    {
        $this->_bl_expanded = $bl_expanded;
    }
    /**
     * returns if a category has sub categories
     *
     * @return bool
     */
    public function get_has_sub_cats()
    {
        if (!isset($this->_bl_has_sub_cats)) {
            $this->_bl_has_sub_cats = $this->oxcategories__oxright->value > $this->oxcategories__oxleft->value + 1;
        }
        return $this->_bl_has_sub_cats;
    }
    /**
     * returns if a category has visible sub categories
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
     * sets the state of has visible sub categories for the category
     *
     * @param bool $blHasVisibleSubcats marker if category has visible subcategories
     */
    public function set_has_visible_sub_cats($bl_has_visible_subcats): void
    {
        if ($bl_has_visible_subcats && !$this->_bl_has_visible_sub_cats) {
            unset($this->_bl_is_visible);
            if ($this->_o_parent instanceof \Oxid_Esales\Eshop\Application\Model\Category) {
                $this->_o_parent->set_has_visible_sub_cats(true);
            }
        }
        $this->_bl_has_visible_sub_cats = $bl_has_visible_subcats;
    }
    /**
     * Loads and returns attribute list associated with this category
     *
     * @return \OxidEsales\Eshop\Application\Model\AttributeList
     */
    public function get_attributes()
    {
        $s_act_cat = $this->get_id();
        $s_key = md5($s_act_cat . serialize(Registry::get_session()->get_variable('session_attrfilter')));
        if (!isset(self::$_a_cat_attributes[$s_key])) {
            $o_attr_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute_List::class);
            $o_attr_list->get_category_attributes($s_act_cat, $this->get_language());
            self::$_a_cat_attributes[$s_key] = $o_attr_list;
        }
        return self::$_a_cat_attributes[$s_key];
    }
    /**
     * Loads and returns category in base language
     *
     * @param object $oActCategory active category
     *
     * @return object
     */
    public function get_cat_in_lang($o_act_category = null)
    {
        $o_category_in_default_language = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        if ($this->is_price_category()) {
            // get it in base language
            $o_category_in_default_language->load_in_lang(0, $this->get_id());
        } else {
            $o_category_in_default_language->load_in_lang(0, $o_act_category->get_id());
        }
        return $o_category_in_default_language;
    }
    /**
     * Set parent category object for internal usage only.
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory parent category object
     */
    public function set_parent_category($o_category): void
    {
        $this->_o_parent = $o_category;
    }
    /**
     * Returns parent category object for current category (if it is available).
     *
     * @return \OxidEsales\Eshop\Application\Model\Category
     */
    public function get_parent_category()
    {
        $category = null;
        $parent_category_id = $this->get_field_data('oxparentid');
        if ($parent_category_id !== 'oxrootid') {
            // checking if object itself has ref to parent
            if ($this->_o_parent) {
                $category = $this->_o_parent;
            } else {
                $category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
                if (!$category->load($parent_category_id)) {
                    $category = null;
                } else {
                    $this->_o_parent = $category;
                }
            }
        }
        return $category;
    }
    /**
     * Returns root category id of a child category
     *
     * @param string $sCategoryId category id
     *
     * @return integer
     */
    public static function get_root_id($s_category_id)
    {
        if (!isset($s_category_id)) {
            return;
        }
        $o_db = Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        return $o_db->get_one('select oxrootid from ' . $table_view_name_generator->get_view_name('oxcategories') . ' where oxid = :oxid', ['oxid' => $s_category_id]);
    }
    /**
     * Before assigning the record from SQL it checks for viewable rights
     *
     * @param string $sSelect SQL select
     *
     * @return bool
     */
    public function assign_viewable_record($s_select)
    {
        if ($this->assign_record($s_select)) {
            return true;
        }
        return false;
    }
    /**
     * Inserts new category (and updates existing node oxLeft amd oxRight accordingly). Returns true on success.
     *
     * @return bool
     */
    protected function insert()
    {
        $parent_category_id = $this->get_field_data('oxparentid');
        if ($parent_category_id !== 'oxrootid') {
            // load parent
            $o_parent = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            //#M317 check if parent is loaded
            if (!$o_parent->load($parent_category_id)) {
                return false;
            }
            // update existing nodes
            $o_db = Database_Provider::get_db();
            $query = 'UPDATE oxcategories SET OXLEFT = OXLEFT + 2
                      WHERE OXROOTID = :oxrootid AND
                            OXLEFT > :oxleft AND
                            OXRIGHT >= :oxright AND
                            OXSHOPID = :oxshopid ';
            $o_db->execute($query, ['oxrootid' => $o_parent->oxcategories__oxrootid->value, 'oxleft' => (int) $o_parent->oxcategories__oxright->value, 'oxright' => (int) $o_parent->oxcategories__oxright->value, 'oxshopid' => $this->get_shop_id()]);
            $query = 'UPDATE oxcategories SET OXRIGHT = OXRIGHT + 2
                      WHERE OXROOTID = :oxrootid AND
                            OXRIGHT >= :oxright AND
                            OXSHOPID = :oxshopid';
            $o_db->execute($query, ['oxrootid' => $o_parent->oxcategories__oxrootid->value, 'oxright' => (int) $o_parent->oxcategories__oxright->value, 'oxshopid' => $this->get_shop_id()]);
            if (!$this->get_id()) {
                $this->set_id();
            }
            $this->oxcategories__oxrootid = new \Oxid_Esales\Eshop\Core\Field($o_parent->oxcategories__oxrootid->value, \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $this->oxcategories__oxleft = new \Oxid_Esales\Eshop\Core\Field($o_parent->oxcategories__oxright->value, \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $this->oxcategories__oxright = new \Oxid_Esales\Eshop\Core\Field($o_parent->oxcategories__oxright->value + 1, \Oxid_Esales\Eshop\Core\Field::T_RAW);
            return parent::insert();
        }
        // root entry
        if (!$this->get_id()) {
            $this->set_id();
        }
        $this->oxcategories__oxrootid = new \Oxid_Esales\Eshop\Core\Field($this->get_id(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxcategories__oxleft = new \Oxid_Esales\Eshop\Core\Field(1, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxcategories__oxright = new \Oxid_Esales\Eshop\Core\Field(2, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        return parent::insert();
    }
    /**
     * Updates category tree, returns true on success.
     *
     * @return bool
     */
    protected function update()
    {
        $this->set_update_seo(true);
        $this->set_update_seo_on_field_change('oxtitle');
        // Function is called from inside a transaction in Category::save (see ESDEV-3804 and ESDEV-3822).
        // No need to explicitly force master here.
        $database = Database_Provider::get_db();
        $s_old_parent_id = $database->get_one('select oxparentid from oxcategories where oxid = :oxid', ['oxid' => $this->get_id()]);
        if ($this->_bl_is_seo_object && $this->is_admin()) {
            Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class)->mark_related_as_expired($this);
        }
        $bl_res = parent::update();
        // #872C - need to update category tree oxleft and oxright values (nested sets),
        // then sub trees are moved inside one root, or to another root.
        // this is done in 3 basic steps
        // 1. increase oxleft and oxright values of target root tree by $iTreeSize, where oxleft>=$iMoveAfter , oxright>=$iMoveAfter
        // 2. modify current subtree, we want to move by adding $iDelta to it's oxleft and oxright,  where oxleft>=$sOldParentLeft and oxright<=$sOldParentRight values,
        //    in this step we also modify rootid's if they were changed
        // 3. decreasing oxleft and oxright values of current root tree, where oxleft >= $sOldParentRight+1 , oxright >= $sOldParentRight+1
        // did we change position in tree ?
        $parent_category_id = $this->get_field_data('oxparentid');
        if ($parent_category_id != $s_old_parent_id) {
            $s_old_parent_left = $this->oxcategories__oxleft->value;
            $s_old_parent_right = $this->oxcategories__oxright->value;
            $i_tree_size = $s_old_parent_right - $s_old_parent_left + 1;
            $s_new_root_id = $database->get_one('select oxrootid from oxcategories where oxid = :oxid', ['oxid' => $parent_category_id]);
            //If empty rootID, we set it to categorys oxid
            if ($s_new_root_id == '') {
                $s_new_root_id = $this->get_id();
            }
            $s_new_parent_left = $database->get_one('select oxleft from oxcategories where oxid = :oxid', ['oxid' => $parent_category_id]);
            $i_move_after = $s_new_parent_left + 1;
            //New parentid can not be set to it's child
            if ($s_new_parent_left > $s_old_parent_left && $s_new_parent_left < $s_old_parent_right && $this->oxcategories__oxrootid->value == $s_new_root_id) {
                //Restoring old parentid, stoping further actions
                $s_restore_old = 'UPDATE oxcategories SET OXPARENTID = :oxparentid WHERE oxid = :oxid';
                $database->execute($s_restore_old, ['oxparentid' => $s_old_parent_id, 'oxid' => $this->get_id()]);
                return false;
            }
            //Old parent will be shifted too, if it is in the same tree
            if ($s_old_parent_left > $i_move_after && $this->oxcategories__oxrootid->value == $s_new_root_id) {
                $s_old_parent_left += $i_tree_size;
                $s_old_parent_right += $i_tree_size;
            }
            $i_delta = $i_move_after - $s_old_parent_left;
            $s_add_old = " and oxshopid = '" . $this->get_shop_id() . "' and OXROOTID = " . $database->quote($this->oxcategories__oxrootid->value) . ';';
            $s_add_new = " and oxshopid = '" . $this->get_shop_id() . "' and OXROOTID = " . $database->quote($s_new_root_id) . ';';
            //Updating everything after new position
            $params = ['treeSize' => $i_tree_size, 'offset' => $i_move_after];
            $database->execute('UPDATE oxcategories SET OXLEFT = (OXLEFT + :treeSize) WHERE OXLEFT >= :offset' . $s_add_new, $params);
            $database->execute('UPDATE oxcategories SET OXRIGHT = (OXRIGHT + :treeSize) WHERE OXRIGHT >= :offset' . $s_add_new, $params);
            $s_change_root_id = '';
            if ($this->oxcategories__oxrootid->value != $s_new_root_id) {
                $s_change_root_id = ', OXROOTID=' . $database->quote($s_new_root_id);
            }
            //Updating subtree
            $query = 'UPDATE oxcategories SET OXLEFT = (OXLEFT + :delta), OXRIGHT = (OXRIGHT + :delta) ' . $s_change_root_id . 'WHERE OXLEFT >= :oxleft AND OXRIGHT <= :oxright' . $s_add_old;
            $database->execute($query, ['delta' => $i_delta, 'oxleft' => $s_old_parent_left, 'oxright' => $s_old_parent_right]);
            //Updating everything after old position
            $params = ['treeSize' => $i_tree_size, 'offset' => $s_old_parent_right + 1];
            $database->execute('UPDATE oxcategories SET OXLEFT = (OXLEFT - :treeSize) WHERE OXLEFT >= :offset' . $s_add_old, $params);
            $database->execute('UPDATE oxcategories SET OXRIGHT = (OXRIGHT - :treeSize) WHERE OXRIGHT >= :offset' . $s_add_old, $params);
        }
        if ($bl_res && $this->_bl_is_seo_object && $this->is_admin()) {
            Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class)->mark_related_as_expired($this);
        }
        return $bl_res;
    }
    /**
     * Sets data field value
     *
     * @param string $fieldName index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $value     value of data field
     * @param int    $dataType  field type
     */
    protected function set_field_data($field_name, $value, $data_type = \Oxid_Esales\Eshop\Core\Field::T_TEXT)
    {
        //preliminary quick check saves 3% of execution time in category lists by avoiding redundant strtolower() call
        $field_name_index2 = $field_name[2];
        if ($field_name_index2 === 'l' || $field_name_index2 === 'L' || isset($field_name[16]) && ($field_name[16] == 'l' || $field_name[16] == 'L')) {
            $lowered_field_name = strtolower($field_name);
            if ('oxlongdesc' === $lowered_field_name || 'oxcategories__oxlongdesc' === $lowered_field_name) {
                $data_type = \Oxid_Esales\Eshop\Core\Field::T_RAW;
            }
        }
        return parent::set_field_data($field_name, $value, $data_type);
    }
    /**
     * Returns category icon picture url if exist, false - if not
     *
     * @return mixed
     */
    public function get_icon_url()
    {
        if ($s_icon = $this->oxcategories__oxicon->value) {
            $o_config = Registry::get_config();
            $s_size = $o_config->get_config_param('sCatIconsize');
            if (!isset($s_size)) {
                $s_size = $o_config->get_config_param('sIconsize');
            }
            return Registry::get_picture_handler()->get_pic_url('category/icon/', $s_icon, $s_size);
        }
    }
    /**
     * Returns category thumbnail picture url if exist, false - if not
     *
     * @return mixed
     */
    public function get_thumb_url()
    {
        if ($s_icon = $this->oxcategories__oxthumb->value) {
            $s_size = Registry::get_config()->get_config_param('sCatThumbnailsize');
            return Registry::get_picture_handler()->get_pic_url('category/thumb/', $s_icon, $s_size);
        }
    }
    /**
     * Returns category promotion icon picture url if exist, false - if not
     *
     * @return mixed
     */
    public function get_promotion_icon_url()
    {
        if ($s_icon = $this->oxcategories__oxpromoicon->value) {
            $s_size = Registry::get_config()->get_config_param('sCatPromotionsize');
            return Registry::get_picture_handler()->get_pic_url('category/promo_icon/', $s_icon, $s_size);
        }
    }
    /**
     * Returns category picture url if exist, false - if not
     *
     * @param string $sPicName picture name
     * @param string $sPicType picture type related with picture dir: icon - icon; 0 - image
     *
     * @return mixed
     */
    public function get_picture_url_for_type($s_pic_name, $s_pic_type)
    {
        if ($s_pic_name) {
            return $this->get_picture_url() . $s_pic_type . '/' . $s_pic_name;
        }
        return false;
    }
    /**
     * Returns true if category parentid is 'oxrootid'
     *
     * @return bool
     */
    public function is_top_category()
    {
        if ($this->_bl_top_category == null) {
            $this->_bl_top_category = $this->get_field_data('oxparentid') === 'oxrootid';
        }
        return $this->_bl_top_category;
    }
    /**
     * Returns true if current category is price type ( ( oxpricefrom || oxpriceto ) > 0 )
     *
     * @return bool
     */
    public function is_price_category()
    {
        return $this->oxcategories__oxpricefrom->value || $this->oxcategories__oxpriceto->value;
    }
    /**
     * Returns short description
     *
     * @return string
     */
    public function get_short_description()
    {
        return $this->oxcategories__oxdesc->value;
    }
    /**
     * Returns category title
     *
     * @return string
     */
    public function get_title()
    {
        return $this->oxcategories__oxtitle->value;
    }
    /**
     * Gets one field from all of subcategories.
     * Default is set to 'OXID'
     *
     * @param string $sField field to be retrieved from each subcategory
     * @param string $sOXID  Cetegory ID
     *
     * @return array
     */
    public function get_field_from_sub_categories($s_field = 'OXID', $s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        if (!$s_oxid) {
            return false;
        }
        $s_table = $this->get_view_name();
        $s_field = "`{$s_table}`.`{$s_field}`";
        $s_sql = "SELECT {$s_field} FROM `{$s_table}` WHERE `OXROOTID` = :oxrootid AND `OXPARENTID` != 'oxrootid'";
        return Database_Provider::get_db()->get_col($s_sql, ['oxrootid' => $s_oxid]);
    }
}