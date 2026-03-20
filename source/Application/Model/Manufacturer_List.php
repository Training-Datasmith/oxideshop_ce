<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Manufacturer list manager.
 * Collects list of manufacturers according to collection rules (activ, etc.).
 */
class Manufacturer_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Manufacturer root.
     *
     * @var \stdClass
     */
    protected $_o_root;
    /**
     * Manufacturer tree path.
     *
     * @var array
     */
    protected $_a_path = [];
    /**
     * To show manufacturer article count or not
     *
     * @var bool
     */
    protected $_bl_show_manufacturer_article_cnt = false;
    /**
     * Active manufacturer object
     *
     * @var \OxidEsales\Eshop\Application\Model\Manufacturer
     */
    protected $_o_clicked_manufacturer;
    /**
     * Calls parent constructor and defines if Article vendor count is shown
     */
    public function __construct()
    {
        $this->set_show_manufacturer_article_cnt(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfShowActionCatArticleCnt'));
        parent::__construct('oxmanufacturer');
    }
    /**
     * Enables/disables manufacturer article count calculation
     *
     * @param bool $blShowManufacturerArticleCnt to show article count or not
     */
    public function set_show_manufacturer_article_cnt($bl_show_manufacturer_article_cnt = false): void
    {
        $this->_bl_show_manufacturer_article_cnt = $bl_show_manufacturer_article_cnt;
    }
    /**
     * Loads simple manufacturer list
     */
    public function load_manufacturer_list(): void
    {
        $o_base_object = $this->get_base_object();
        $s_field_list = $o_base_object->get_select_fields();
        $s_view_name = $o_base_object->get_view_name();
        $this->get_base_object()->set_show_article_cnt($this->_bl_show_manufacturer_article_cnt);
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
     * Creates fake root for manufacturer tree, and ads category list fileds for each manufacturer item
     *
     * @param string $sLinkTarget  Name of class, responsible for category rendering
     * @param string $sActCat      Active category
     * @param string $sShopHomeUrl base shop url ($myConfig->getShopHomeUrl())
     */
    public function build_manufacturer_tree($s_link_target, $s_act_cat, $s_shop_home_url): void
    {
        //Load manufacturer list
        $this->load_manufacturer_list();
        //Create fake manufacturer root category
        $this->_o_root = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
        $this->_o_root->load('root');
        //category fields
        $this->add_category_fields($this->_o_root);
        $this->_a_path[] = $this->_o_root;
        foreach ($this as $s_vnd_id => $o_manufacturer) {
            // storing active manufacturer object
            if ((string) $s_vnd_id === $s_act_cat) {
                $this->set_click_manufacturer($o_manufacturer);
            }
            $this->add_category_fields($o_manufacturer);
            if ($s_act_cat == $o_manufacturer->oxmanufacturers__oxid->value) {
                $this->_a_path[] = $o_manufacturer;
            }
        }
        $this->seo_set_manufacturer_data();
    }
    /**
     * Root manufacturer list node (which usually is a manually prefilled object) getter
     *
     * @return \OxidEsales\Eshop\Application\Model\Manufacturer
     */
    public function get_root_cat()
    {
        return $this->_o_root;
    }
    /**
     * Returns manufacturer path array
     *
     * @return array
     */
    public function get_path()
    {
        return $this->_a_path;
    }
    /**
     * Adds category specific fields to manufacturer object
     *
     * @param object $oManufacturer manufacturer object
     */
    protected function add_category_fields($o_manufacturer)
    {
        $o_manufacturer->oxcategories__oxid = new \Oxid_Esales\Eshop\Core\Field($o_manufacturer->oxmanufacturers__oxid->value);
        $o_manufacturer->oxcategories__oxicon = $o_manufacturer->oxmanufacturers__oxicon;
        $o_manufacturer->oxcategories__oxtitle = $o_manufacturer->oxmanufacturers__oxtitle;
        $o_manufacturer->oxcategories__oxdesc = $o_manufacturer->oxmanufacturers__oxshortdesc;
        $o_manufacturer->set_is_visible(true);
        $o_manufacturer->set_has_visible_sub_cats(false);
    }
    /**
     * Sets active (open) manufacturer object
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer active manufacturer
     */
    public function set_click_manufacturer($o_manufacturer): void
    {
        $this->_o_clicked_manufacturer = $o_manufacturer;
    }
    /**
     * returns active (open) manufacturer object
     *
     * @return \OxidEsales\Eshop\Application\Model\Manufacturer
     */
    public function get_click_manufacturer()
    {
        return $this->_o_clicked_manufacturer;
    }
    /**
     * Processes manufacturer category URLs
     */
    protected function seo_set_manufacturer_data()
    {
        // only when SEO id on and in front end
        if (\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active() && !$this->is_admin()) {
            $o_encoder = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Manufacturer::class);
            // preparing root manufacturer category
            if ($this->_o_root) {
                $o_encoder->get_manufacturer_url($this->_o_root);
            }
            // encoding manufacturer category
            foreach ($this as $s_vnd_id => $value) {
                $o_encoder->get_manufacturer_url($this->_a_array[$s_vnd_id]);
            }
        }
    }
}