<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Content manager.
 * Base object for content pages
 */
class Content extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model implements \Oxid_Esales\Eshop\Core\Contract\I_Url
{
    /**
     * Current class name.
     *
     * @var string
     */
    protected $_s_class_name = 'oxcontent';
    /**
     * Seo article urls for languages.
     *
     * @var array
     */
    protected $_a_seo_urls = [];
    /**
     * Content parent category id
     *
     * @var string
     */
    protected $_s_parent_cat_id;
    /**
     * Expanded state of a content category.
     *
     * @var bool
     */
    protected $_bl_expanded;
    /**
     * Marks that current object is managed by SEO.
     *
     * @var bool
     */
    protected $_bl_is_seo_object = true;
    /**
     * Category id.
     *
     * @var string
     */
    protected $_s_category_id;
    /**
     * Extra getter to guarantee compatibility with templates.
     *
     * @param string $sName parameter name
     *
     * @return mixed
     */
    public function __get($s_name)
    {
        return match ($s_name) {
            'expanded' => $this->get_expanded(),
            default => parent::__get($s_name),
        };
    }
    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxcontents');
    }
    /**
     * Returns the expanded state of the content category.
     *
     * @return bool
     */
    public function get_expanded()
    {
        if (!isset($this->_bl_expanded)) {
            $this->_bl_expanded = $this->get_id() == Registry::get_request()->get_request_escaped_parameter('oxcid');
        }
        return $this->_bl_expanded;
    }
    /**
     * Sets category id.
     *
     * @param string $sCategoryId
     */
    public function set_category_id($s_category_id): void
    {
        $this->oxcontents__oxcatid = new Field($s_category_id);
    }
    /**
     * Returns category id.
     *
     * @return string
     */
    public function get_category_id()
    {
        return $this->oxcontents__oxcatid->value;
    }
    /**
     * Get data from db.
     *
     * @param string $sLoadId id
     *
     * @return array
     */
    protected function load_from_db($s_load_id)
    {
        $s_table = $this->get_view_name();
        $s_shop_id = $this->get_shop_id();
        $a_params = [$s_table . '.oxloadid' => $s_load_id, $s_table . '.oxshopid' => $s_shop_id];
        $s_select = $this->build_select_string($a_params);
        //Loads "credits" content object and its text (first available)
        if ($s_load_id == 'oxcredits') {
            // fetching column names
            $s_col_q = "SHOW COLUMNS FROM oxcontents WHERE field LIKE  'oxcontent%'";
            $a_cols = Database_Provider::get_db()->get_all($s_col_q);
            // building subquery
            $s_pattern = "IF ( %s != '', %s, %s ) ";
            $i_count = count($a_cols) - 1;
            $s_cont_q = "SELECT {$s_pattern}";
            foreach ($a_cols as $i_key => $a_col) {
                $s_cont_q = sprintf($s_cont_q, $a_col[0], $a_col[0], $i_count != $i_key ? $s_pattern : "''");
            }
            $s_cont_q .= " FROM oxcontents WHERE oxloadid = '{$s_load_id}' AND oxshopid = '{$s_shop_id}'";
            $s_select = $this->build_select_string($a_params);
            $s_select = str_replace("`{$s_table}`.`oxcontent`", "( {$s_cont_q} ) as oxcontent", $s_select);
        }
        return Database_Provider::get_db()->get_row($s_select);
    }
    /**
     * Loads Content by using field oxloadid instead of oxid.
     *
     * @param string $loadId     content load ID
     * @param string|false $onlyActive selection state - active/inactive
     *
     * @return bool
     */
    public function load_by_ident($load_id, $only_active = false)
    {
        return $this->assign_content_data($this->load_from_db($load_id), $only_active);
    }
    /**
     * Assign content data, filter inactive if needed.
     *
     * @param array $fetchedContent Item data to assign
     * @param bool  $onlyActive     Only assign if item is active
     *
     * @return bool
     */
    protected function assign_content_data($fetched_content, $only_active = false)
    {
        $filtered_content = $this->filter_inactive($fetched_content, $only_active);
        if (!is_null($filtered_content)) {
            $this->assign($filtered_content);
            return true;
        }
        return false;
    }
    /**
     * Decide if content item can be loaded by checking item activity if needed
     *
     * @param array $data
     * @param bool  $checkIfActive
     *
     * @return array|null
     */
    protected function filter_inactive($data, $check_if_active = false)
    {
        return $data && (!$check_if_active || ($check_if_active && $data['OXACTIVE']) == '1') ? $data : null;
    }
    /**
     * Returns unique object id.
     *
     * @return string
     */
    public function get_load_id()
    {
        return $this->oxcontents__oxloadid->value;
    }
    /**
     * Returns unique object id.
     *
     * @return string
     */
    public function is_active()
    {
        return $this->oxcontents__oxactive->value;
    }
    /**
     * Replace the "&amp;" into "&" and call base class.
     *
     * @param array $dbRecord database record
     */
    public function assign($db_record): void
    {
        parent::assign($db_record);
        // workaround for firefox showing &lang= as &9001;= entity, mantis#0001272
        if ($this->oxcontents__oxcontent) {
            $this->oxcontents__oxcontent->set_value(str_replace('&lang=', '&amp;lang=', $this->oxcontents__oxcontent->value), Field::T_RAW);
        }
    }
    /**
     * Returns raw content seo url
     *
     * @param int $iLang language id
     *
     * @return string
     */
    public function get_base_seo_link($i_lang)
    {
        return Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Content::class)->get_content_url($this, $i_lang);
    }
    /**
     * getLink returns link for this content in the frontend.
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
        if ($this->oxcontents__oxloadid->value === 'oxcredits') {
            $s_url .= 'index.php?cl=credits';
        } else {
            $s_url .= 'index.php?cl=content';
        }
        $s_url .= '&amp;oxloadid=' . $this->get_load_id();
        if ($bl_add_id) {
            $s_url .= '&amp;oxcid=' . $this->get_id();
            // adding parent category if if available
            if ($this->_s_parent_cat_id !== false && $this->oxcontents__oxcatid->value && $this->oxcontents__oxcatid->value != 'oxrootid') {
                if ($this->_s_parent_cat_id === null) {
                    $this->_s_parent_cat_id = false;
                    $o_db = Database_Provider::get_db();
                    $s_parent_id = $o_db->get_one('select oxparentid from oxcategories where oxid = :oxid', ['oxid' => $this->oxcontents__oxcatid->value]);
                    if ($s_parent_id && 'oxrootid' != $s_parent_id) {
                        $this->_s_parent_cat_id = $s_parent_id;
                    }
                }
                if ($this->_s_parent_cat_id) {
                    $s_url .= '&amp;cnid=' . $this->_s_parent_cat_id;
                }
            }
        }
        //always returns shop url, not admin
        return $s_url;
    }
    /**
     * Returns standard URL to product.
     *
     * @param integer $iLang   language
     * @param array   $aParams additional params to use [optional]
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
     * Sets data field value.
     *
     * @param string $sFieldName index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     */
    protected function set_field_data($s_field_name, $s_value, $i_data_type = Field::T_TEXT)
    {
        $s_lowered_field_name = strtolower($s_field_name);
        if ('oxcontent' === $s_lowered_field_name || 'oxcontents__oxcontent' === $s_lowered_field_name) {
            $i_data_type = Field::T_RAW;
        }
        return parent::set_field_data($s_field_name, $s_value, $i_data_type);
    }
    /**
     * Delete this object from the database, returns true on success.
     *
     * @param string $sOXID Object ID(default null)
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        if (parent::delete($s_oxid)) {
            Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Content::class)->on_delete_content($s_oxid);
            return true;
        }
        return false;
    }
    /**
     * Save this Object to database, insert or update as needed.
     *
     * @return mixed
     */
    public function save()
    {
        $bl_saved = parent::save();
        if ($bl_saved && $this->get_field_data('oxloadid') === 'oxagb') {
            $s_shop_id = Registry::get_config()->get_shop_id();
            $s_version = $this->oxcontents__oxtermversion->value;
            $o_db = Database_Provider::get_db();
            // dropping expired..
            $o_db->execute('delete from oxacceptedterms where oxshopid = :oxshopid and oxtermversion != :notoxtermversion', ['oxshopid' => $s_shop_id, 'notoxtermversion' => $s_version]);
        }
        return $bl_saved;
    }
    /**
     * Returns latest terms version id.
     *
     * @return string
     */
    public function get_terms_version()
    {
        if ($this->load_by_ident('oxagb')) {
            return $this->oxcontents__oxtermversion->value;
        }
    }
    /**
     * Set type of content.
     *
     * @param string $sValue type value
     */
    public function set_type($s_value): void
    {
        $this->set_field_data('oxcontents__oxtype', $s_value);
    }
    /**
     * Return type of content
     *
     * @return integer
     */
    public function get_type()
    {
        return (int) $this->get_field_data('oxcontents__oxtype');
    }
    /**
     * Set title of content
     *
     * @param string $sValue title value
     */
    public function set_title($s_value): void
    {
        $this->set_field_data('oxcontents__oxtitle', $s_value);
    }
    /**
     * Return title of content
     *
     * @return string
     */
    public function get_title()
    {
        return (string) $this->get_field_data('oxcontents__oxtitle');
    }
}