<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Vendor list manager.
 * Collects list of vendors according to collection rules (activ, etc.).
 */
class Vendor_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Vendor root.
     *
     * @var \stdClass
     */
    protected $_o_root;
    /**
     * Vendor tree path.
     *
     * @var array
     */
    protected $_a_path = [];
    /**
     * To show vendor article count or not
     *
     * @var bool
     */
    protected $_bl_show_vendor_article_cnt = false;
    /**
     * Active vendor object
     *
     * @var \OxidEsales\Eshop\Application\Model\Vendor
     */
    protected $_o_clicked_vendor;
    /**
     * Calls parent constructor and defines if Article vendor count is shown
     */
    public function __construct()
    {
        $this->set_show_vendor_article_cnt(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfShowActionCatArticleCnt'));
        parent::__construct('oxvendor');
    }
    /**
     * Enables/disables vendor article count calculation
     *
     * @param bool $blShowVendorArticleCnt to show article count or not
     */
    public function set_show_vendor_article_cnt($bl_show_vendor_article_cnt = false): void
    {
        $this->_bl_show_vendor_article_cnt = $bl_show_vendor_article_cnt;
    }
    /**
     * Loads simple vendor list
     */
    public function load_vendor_list(): void
    {
        $o_base_object = $this->get_base_object();
        $s_field_list = $o_base_object->get_select_fields();
        $s_view_name = $o_base_object->get_view_name();
        $this->get_base_object()->set_show_article_cnt($this->_bl_show_vendor_article_cnt);
        $s_where = '';
        if (!$this->is_admin()) {
            $s_where = $o_base_object->get_sql_active_snippet();
            $s_where = $s_where ? " where {$s_where} and " : ' where ';
            $s_where .= "{$s_view_name}.oxtitle != '' ";
        }
        $s_select = "select {$s_field_list} from {$s_view_name} {$s_where} order by {$s_view_name}.oxtitle";
        $this->select_string($s_select);
    }
    /**
     * Creates fake root for vendor tree, and ads category list fileds for each vendor item
     *
     * @param string $sLinkTarget  Name of class, responsible for category rendering
     * @param string $sActCat      Active category
     * @param string $sShopHomeUrl base shop url ($myConfig->getShopHomeUrl())
     */
    public function build_vendor_tree($s_link_target, $s_act_cat, $s_shop_home_url): void
    {
        $s_act_cat = str_replace('v_', '', $s_act_cat);
        //Load vendor list
        $this->load_vendor_list();
        //Create fake vendor root category
        $this->_o_root = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        $this->_o_root->load('root');
        //category fields
        $this->add_category_fields($this->_o_root);
        $this->_a_path[] = $this->_o_root;
        foreach ($this as $s_vnd_id => $o_vendor) {
            // storing active vendor object
            if ($s_vnd_id == $s_act_cat) {
                $this->set_click_vendor($o_vendor);
            }
            $this->add_category_fields($o_vendor);
            if ($s_act_cat == $o_vendor->oxvendor__oxid->value) {
                $this->_a_path[] = $o_vendor;
            }
        }
        $this->seo_set_vendor_data();
    }
    /**
     * Root vendor list node (which usually is a manually prefilled object) getter
     *
     * @return \OxidEsales\Eshop\Application\Model\Vendor
     */
    public function get_root_cat()
    {
        return $this->_o_root;
    }
    /**
     * Returns vendor path array
     *
     * @return array
     */
    public function get_path()
    {
        return $this->_a_path;
    }
    /**
     * Adds category specific fields to vendor object
     *
     * @param object $oVendor vendor object
     */
    protected function add_category_fields($o_vendor)
    {
        $o_vendor->oxcategories__oxid = new \Oxid_Esales\Eshop\Core\Field('v_' . $o_vendor->oxvendor__oxid->value);
        $o_vendor->oxcategories__oxicon = $o_vendor->oxvendor__oxicon;
        $o_vendor->oxcategories__oxtitle = $o_vendor->oxvendor__oxtitle;
        $o_vendor->oxcategories__oxdesc = $o_vendor->oxvendor__oxshortdesc;
        $o_vendor->set_is_visible(true);
        $o_vendor->set_has_visible_sub_cats(false);
    }
    /**
     * Sets active (open) vendor object
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $oVendor active vendor
     */
    public function set_click_vendor($o_vendor): void
    {
        $this->_o_clicked_vendor = $o_vendor;
    }
    /**
     * returns active (open) vendor object
     *
     * @return \OxidEsales\Eshop\Application\Model\Vendor
     */
    public function get_click_vendor()
    {
        return $this->_o_clicked_vendor;
    }
    /**
     * Processes vendor category URLs
     */
    protected function seo_set_vendor_data()
    {
        // only when SEO id on and in front end
        if (\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active() && !$this->is_admin()) {
            $o_encoder = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Vendor::class);
            // preparing root vendor category
            if ($this->_o_root) {
                $o_encoder->get_vendor_url($this->_o_root);
            }
            // encoding vendor category
            foreach ($this as $s_vnd_id => $value) {
                $o_encoder->get_vendor_url($this->_a_array[$s_vnd_id]);
            }
        }
    }
}