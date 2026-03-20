<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use function basename;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
/**
 * CMS - loads pages and displays it
 */
class Content_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Content id.
     *
     * @var string
     */
    protected $_s_content_id;
    /**
     * Content object
     *
     * @var object
     */
    protected $_o_content;
    /**
     * Current view template
     *
     * @var string
     */
    protected $_s_this_template = 'page/info/content';
    /**
     * Current view plain template
     *
     * @var string
     */
    protected $_s_this_plain_template = 'page/info/content_plain';
    /**
     * Current view content category (if available)
     */
    protected $_o_content_cat;
    /**
     * Ids of contents which can be accessed without any restrictions when private sales is ON
     *
     * @var array
     */
    protected $_a_ps_allowed_contents = ['oxagb', 'oxrightofwithdrawal', 'oximpressum'];
    /**
     * Current view content title
     *
     * @var string
     */
    protected $_s_content_title;
    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = true;
    /**
     * Business entity data template
     *
     * @var string
     */
    protected $_s_business_template = 'rdfa/content/inc/business_entity';
    /**
     * Delivery charge data template
     *
     * @var string
     */
    protected $_s_delivery_template = 'rdfa/content/inc/delivery_charge';
    /**
     * Payment charge data template
     *
     * @var string
     */
    protected $_s_payment_template = 'rdfa/content/inc/payment_charge';
    /**
     * An array including all ShopConfVars which are used to extend business
     * entity data
     *
     * @var array
     */
    protected $_a_business_entity_extends = ['sRDFaLogoUrl', 'sRDFaLongitude', 'sRDFaLatitude', 'sRDFaGLN', 'sRDFaNAICS', 'sRDFaISIC', 'sRDFaDUNS'];
    /**
     * Returns prefix ID used by template engine.
     *
     * @return string    $this->_sViewId
     */
    public function get_view_id()
    {
        if (!isset($this->_s_view_id)) {
            $this->_s_view_id = parent::get_view_id() . '|' . Registry::get_request()->get_request_escaped_parameter('oxcid');
        }
        return $this->_s_view_id;
    }
    /** @inheritdoc  */
    public function render()
    {
        parent::render();
        $content = $this->get_content();
        if ($content && $content->get_load_id()) {
            $this->validate_content_access_permissions($content->get_load_id());
            $this->get_view_config()->set_view_config_param('oxloadid', $content->get_load_id());
        }
        $template_name = $this->get_tpl_name();
        if (!$template_name && (!$content || !$content->get_id())) {
            error_404_handler();
        }
        if ($this->show_plain_template()) {
            $this->_s_this_template = $this->_s_this_plain_template;
        } elseif ($template_name) {
            $this->_s_this_template = $template_name;
        }
        return $this->_s_this_template;
    }
    /**
     * Checks if content can be shown
     *
     * @param string $sContentIdent ident of content to display
     *
     * @return bool
     */
    protected function can_show_content($s_content_ident)
    {
        return !($this->is_enabled_private_sales() && !$this->get_user() && !in_array($s_content_ident, $this->_a_ps_allowed_contents));
    }
    /**
     * Returns current view meta data
     * If $sMeta parameter comes empty, sets to it current content title
     *
     * @param string $sMeta     category path
     * @param int    $iLength   max length of result, -1 for no truncation
     * @param bool   $blDescTag if true - performs additional duplicate cleaning
     *
     * @return string
     */
    protected function prepare_meta_description($s_meta, $i_length = 200, $bl_desc_tag = false)
    {
        if (!$s_meta) {
            $s_meta = $this->get_content()->oxcontents__oxtitle->value;
        }
        return parent::prepare_meta_description($s_meta, $i_length, $bl_desc_tag);
    }
    /**
     * Returns current view keywords seperated by comma
     * If $sKeywords parameter comes empty, sets to it current content title
     *
     * @param string $sKeywords               data to use as keywords
     * @param bool   $blRemoveDuplicatedWords remove duplicated words
     *
     * @return string
     */
    protected function prepare_meta_keyword($s_keywords, $bl_remove_duplicated_words = true)
    {
        if (!$s_keywords) {
            $s_keywords = $this->get_content()->oxcontents__oxtitle->value;
        }
        return parent::prepare_meta_keyword($s_keywords, $bl_remove_duplicated_words);
    }
    /**
     * If current content is assigned to category returns its object
     *
     * @return \OxidEsales\Eshop\Application\Model\Content
     */
    public function get_content_category()
    {
        if ($this->_o_content_cat === null) {
            // setting default status ..
            $this->_o_content_cat = false;
            if (($o_content = $this->get_content()) && $o_content->oxcontents__oxtype->value == 2) {
                $this->_o_content_cat = $o_content;
            }
        }
        return $this->_o_content_cat;
    }
    /**
     * Returns true if user forces to display plain template or
     * if private sales switched ON and user is not logged in
     *
     * @return bool
     */
    public function show_plain_template()
    {
        $bl_plain = (bool) Registry::get_request()->get_request_escaped_parameter('plain');
        if ($bl_plain === false) {
            $o_user = $this->get_user();
            if ($this->is_enabled_private_sales() && (!$o_user || $o_user && !$o_user->is_terms_accepted())) {
                $bl_plain = true;
            }
        }
        return $bl_plain;
    }
    /**
     * Returns active content id to load its seo meta info
     *
     * @return string
     */
    protected function get_seo_object_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('oxcid');
    }
    /**
     * Template variable getter. Returns active content id.
     * If no content id specified, uses "impressum" content id
     *
     * @return object
     */
    public function get_content_id()
    {
        if ($this->_s_content_id === null) {
            $s_content_id = Registry::get_request()->get_request_escaped_parameter('oxcid');
            $s_load_id = Registry::get_request()->get_request_escaped_parameter('oxloadid');
            $this->_s_content_id = false;
            $o_content = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
            if ($s_load_id) {
                $bl_res = $o_content->load_by_ident($s_load_id);
            } elseif ($s_content_id) {
                $bl_res = $o_content->load($s_content_id);
            } else {
                //get default content (impressum)
                $bl_res = $o_content->load_by_ident('oximpressum');
            }
            if ($bl_res && $o_content->oxcontents__oxactive->value) {
                $this->_s_content_id = $o_content->oxcontents__oxid->value;
                $this->_o_content = $o_content;
            }
        }
        return $this->_s_content_id;
    }
    /**
     * Template variable getter. Returns active content
     *
     * @return object
     */
    public function get_content()
    {
        if ($this->_o_content === null) {
            $this->_o_content = false;
            if ($this->get_content_id()) {
                return $this->_o_content;
            }
        }
        return $this->_o_content;
    }
    /**
     * returns object, assosiated with current view.
     * (the object that is shown in frontend)
     *
     * @param int $iLang language id
     *
     * @return object
     */
    protected function get_subject($i_lang)
    {
        return $this->get_content();
    }
    /**
     * Returns name of template
     *
     * @return string
     */
    protected function get_tpl_name()
    {
        $requested_template = Registry::get_request()->get_request_escaped_parameter('tpl');
        if (!$requested_template) {
            return null;
        }
        // security fix so that you can't access files from outside template dir
        $base_name = basename((string) $requested_template);
        return "message/{$base_name}";
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $o_content = $this->get_content();
        $a_paths = [];
        $a_path = [];
        $a_path['title'] = $o_content->oxcontents__oxtitle->value;
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Template variable getter. Returns tag title
     *
     * @return string
     */
    public function get_title()
    {
        if ($this->_s_content_title === null) {
            $o_content = $this->get_content();
            $this->_s_content_title = $o_content->oxcontents__oxtitle->value;
        }
        return $this->_s_content_title;
    }
    /**
     * Returns if page has rdfa
     *
     * @return bool
     */
    public function show_rdfa()
    {
        return Registry::get_config()->get_config_param('blRDFaEmbedding');
    }
    /**
     * Returns template name wich content page to specify:
     * business entity data, payment charge specifications or delivery charge
     *
     * @return array
     */
    public function get_content_page_tpl()
    {
        $a_template = [];
        $s_content_id = $this->get_content()->oxcontents__oxloadid->value;
        $my_config = Registry::get_config();
        if ($s_content_id == $my_config->get_config_param('sRDFaBusinessEntityLoc')) {
            $a_template[] = $this->_s_business_template;
        }
        if ($s_content_id == $my_config->get_config_param('sRDFaDeliveryChargeSpecLoc')) {
            $a_template[] = $this->_s_delivery_template;
        }
        if ($s_content_id == $my_config->get_config_param('sRDFaPaymentChargeSpecLoc')) {
            $a_template[] = $this->_s_payment_template;
        }
        return $a_template;
    }
    /**
     * Gets extended business entity data
     *
     * @return object
     */
    public function get_business_entity_extends()
    {
        $my_config = Registry::get_config();
        $a_extends = [];
        foreach ($this->_a_business_entity_extends as $s_extend) {
            $a_extends[$s_extend] = $my_config->get_config_param($s_extend);
        }
        return $a_extends;
    }
    /**
     * Returns an object including all payments which are not mapped to a
     * predefined GoodRelations payment method. This object is used for
     * defining new instances of gr:PaymentMethods at content pages.
     *
     * @return object
     */
    public function get_not_mapped_to_rd_fa_payments()
    {
        $o_payments = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment_List::class);
        $o_payments->load_non_rd_fa_payment_list();
        return $o_payments;
    }
    /**
     * Returns an object including all delivery sets which are not mapped to a
     * predefined GoodRelations delivery method. This object is used for
     * defining new instances of gr:DeliveryMethods at content pages.
     *
     * @return object
     */
    public function get_not_mapped_to_rd_fa_delivery_sets()
    {
        $o_del_sets = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set_List::class);
        $o_del_sets->load_non_rd_fa_delivery_set_list();
        return $o_del_sets;
    }
    /**
     * Returns delivery methods with assigned deliverysets.
     *
     * @return object
     */
    public function get_delivery_charge_specs()
    {
        $a_delivery_charge_specs = [];
        $o_delivery_charge_specs = $this->get_delivery_list();
        foreach ($o_delivery_charge_specs as $o_delivery_charge_spec) {
            if ($o_delivery_charge_spec->oxdelivery__oxaddsumtype->value == 'abs') {
                $o_del_sets = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set_List::class);
                $o_del_sets->load_rd_fa_delivery_set_list($o_delivery_charge_spec->get_id());
                $o_delivery_charge_spec->deliverysetmethods = $o_del_sets;
                $a_delivery_charge_specs[] = $o_delivery_charge_spec;
            }
        }
        return $a_delivery_charge_specs;
    }
    /**
     * Template variable getter. Returns delivery list
     *
     * @return object
     */
    public function get_delivery_list()
    {
        if ($this->_o_del_list === null) {
            $this->_o_del_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_List::class);
            $this->_o_del_list->get_list();
        }
        return $this->_o_del_list;
    }
    /**
     * Returns rdfa VAT
     *
     * @return bool
     */
    public function get_rdfa_vat()
    {
        return Registry::get_config()->get_config_param('iRDFaVAT');
    }
    /**
     * Returns rdfa VAT
     *
     * @return bool
     */
    public function get_rdfa_price_validity()
    {
        $i_days = Registry::get_config()->get_config_param('iRDFaPriceValidity');
        $i_from = Registry::get_utils_date()->get_time();
        $i_through = $i_from + $i_days * 24 * 60 * 60;
        $o_price_validity = [];
        $o_price_validity['validfrom'] = date('Y-m-d\TH:i:s', $i_from) . 'Z';
        $o_price_validity['validthrough'] = date('Y-m-d\TH:i:s', $i_through) . 'Z';
        return $o_price_validity;
    }
    /**
     * Returns content parsed through renderer
     *
     * @return string
     */
    public function get_parsed_content()
    {
        $active_language_id = Registry::get_lang()->get_tpl_language();
        return $this->get_renderer()->render_fragment($this->get_content()->oxcontents__oxcontent->value, "ox:{$this->get_content()->get_id()}{$active_language_id}", $this->get_view_data());
    }
    private function get_renderer(): Template_Renderer_Interface
    {
        return Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
    }
    /**
     * Returns view canonical url
     *
     * @return string
     */
    public function get_canonical_url()
    {
        $url = '';
        if ($content = $this->get_content()) {
            $utils = Registry::get_utils_url();
            if (Registry::get_utils()->seo_is_active()) {
                $url = $utils->prepare_canonical_url($content->get_base_seo_link($content->get_language()));
            } else {
                $url = $utils->prepare_canonical_url($content->get_base_std_link($content->get_language()));
            }
        }
        return $url;
    }
    /**
     * Terminates execution with exit() on no permissions
     */
    private function validate_content_access_permissions(string $content_id): void
    {
        if (!$this->can_show_content($content_id)) {
            Registry::get_utils()->redirect(Registry::get_config()->get_shop_home_url() . 'cl=account');
        }
    }
}