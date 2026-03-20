<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Exception\File_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Assets_Path_Resolver_Bridge_Interface;
/**
 * View config data access class. Keeps most
 * of getters needed for formatting various urls,
 * config parameters, session information etc.
 */
class View_Config extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Active shop object. Can only be accessed when it is assigned
     *
     * @var \OxidEsales\Eshop\Application\Model\Shop
     */
    protected $_o_shop;
    /**
     * View data array, may only be accedded when it is assigned tohether with shop object
     *
     * @var array
     */
    protected $_a_view_data;
    /**
     * View config parameters cache array
     *
     * @var array
     */
    protected $_a_config_params = [];
    /**
     * Help page link
     *
     * @return string
     */
    protected $_s_help_page_link;
    /**
     * @var \OxidEsales\Eshop\Application\Model\CountryList
     */
    protected $_o_country_list;
    /**
     * Active theme name
     */
    protected $_s_active_theme;
    /**
     * Shop logo
     *
     * @var string
     */
    protected $_s_shop_logo;
    /**
     * Returns shops home link
     *
     * @return string
     */
    public function get_home_link()
    {
        if (($s_value = $this->get_view_config_param('homeLink')) === null) {
            $s_value = null;
            $bl_add_start_cl = $this->is_start_class_required();
            if ($bl_add_start_cl) {
                $base_language = Registry::get_lang()->get_base_language();
                $s_value = Registry::get_seo_encoder()->get_static_url($this->get_self_link() . 'cl=start', $base_language);
                $s_value = Registry::get_utils_url()->append_url($s_value, Registry::get_utils_url()->get_base_add_url_params());
                $s_value = Str::get_str()->preg_replace('/(\?|&(amp;)?)$/', '', $s_value);
            }
            if (!$s_value) {
                $s_value = Str::get_str()->preg_replace('#index.php\??$#', '', $this->get_self_link());
            }
            $this->set_view_config_param('homeLink', $s_value);
        }
        return $s_value;
    }
    /**
     * Check if some shop selection page must be shown
     *
     * @return bool
     */
    protected function is_start_class_required()
    {
        $base_language = Registry::get_lang()->get_base_language();
        $shop_config = Registry::get_config();
        $is_seo_active = Registry::get_utils()->seo_is_active();
        return $is_seo_active && $base_language != $shop_config->get_config_param('sDefaultLang');
    }
    /**
     * Returns active template name (if set)
     *
     * @return string
     */
    public function get_act_content_load_id()
    {
        $s_tpl_name = Registry::get_request()->get_request_escaped_parameter('oxloadid');
        // #M1176: Logout from CMS page
        if (!$s_tpl_name && Registry::get_config()->get_top_active_view()) {
            $s_tpl_name = Registry::get_config()->get_top_active_view()->get_view_config()->get_view_config_param('oxloadid');
        }
        return $s_tpl_name ? basename((string) $s_tpl_name) : null;
    }
    /**
     * Returns active manufacturer id
     *
     * @return string
     */
    public function get_act_tpl_name()
    {
        return Registry::get_request()->get_request_escaped_parameter('tpl');
    }
    /**
     * Returns active currency id
     *
     * @return string
     */
    public function get_act_currency()
    {
        return Registry::get_config()->get_shop_currency();
    }
    /**
     * Returns shop logout link
     *
     * @return string
     */
    public function get_logout_link()
    {
        $s_class = $this->get_top_action_class_name();
        $s_catnid = $this->get_act_cat_id();
        $s_mnfid = $this->get_act_manufacturer_id();
        $s_artnid = $this->get_act_article_id();
        $s_tpl_name = $this->get_act_tpl_name();
        $s_content_load_id = $this->get_act_content_load_id();
        $s_search_param = $this->get_act_search_param();
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        $s_recomm_id = $this->get_act_recommendation_id();
        // END deprecated
        $s_list_type = $this->get_act_list_type();
        $o_config = Registry::get_config();
        return ($o_config->is_ssl() ? $o_config->get_shop_secure_home_url() : $o_config->get_shop_home_url()) . "cl={$s_class}" . ($s_catnid ? "&amp;cnid={$s_catnid}" : '') . ($s_artnid ? "&amp;anid={$s_artnid}" : '') . ($s_mnfid ? "&amp;mnid={$s_mnfid}" : '') . ($s_search_param ? "&amp;searchparam={$s_search_param}" : '') . ($s_recomm_id ? "&amp;recommid={$s_recomm_id}" : '') . ($s_list_type ? "&amp;listtype={$s_list_type}" : '') . '&amp;fnc=logout' . ($s_tpl_name ? '&amp;tpl=' . basename($s_tpl_name) : '') . ($s_content_load_id ? '&amp;oxloadid=' . $s_content_load_id : '') . '&amp;redirect=1';
    }
    /**
     * Returns help content link idents
     *
     * @return array
     */
    protected function get_help_content_idents()
    {
        $s_class = $this->get_active_class_name();
        return ['oxhelp' . strtolower($s_class), 'oxhelpdefault'];
    }
    public function get_media_picture_url(): string
    {
        return Registry::get_config()->get_picture_url('media/');
    }
    /**
     * Returns shop help link
     *
     * @return string
     */
    public function get_help_page_link()
    {
        if ($this->_s_help_page_link === null) {
            $this->_s_help_page_link = '';
            $a_content_idents = $this->get_help_content_idents();
            $o_content = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
            foreach ($a_content_idents as $s_ident) {
                if ($o_content->load_by_ident($s_ident, true)) {
                    $this->_s_help_page_link = $o_content->get_link();
                    break;
                }
            }
        }
        return $this->_s_help_page_link;
    }
    /**
     * Returns active category id
     *
     * @return string
     */
    public function get_act_cat_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('cnid');
    }
    /**
     * Returns active article id
     *
     * @return string
     */
    public function get_act_article_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('anid');
    }
    /**
     * Returns active search parameter
     *
     * @return string
     */
    public function get_act_search_param()
    {
        return Registry::get_request()->get_request_escaped_parameter('searchparam');
    }
    /**
     * Returns active recommendation id parameter
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return string
     */
    public function get_act_recommendation_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('recommid');
    }
    /**
     * Returns active listtype parameter
     *
     * @return string
     */
    public function get_act_list_type()
    {
        return Registry::get_request()->get_request_escaped_parameter('listtype');
    }
    /**
     * Returns active manufacturer id
     *
     * @return string
     */
    public function get_act_manufacturer_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('mnid');
    }
    /**
     * Returns active content id
     *
     * @return string
     */
    public function get_content_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('oxcid');
    }
    /**
     * Sets view config parameter, which can be accessed in templates in two ways:
     *
     * $oViewConf->getViewConfigParam( $sName )
     *
     * @param string $sName  name of parameter
     * @param mixed  $sValue parameter value
     */
    public function set_view_config_param($s_name, $s_value): void
    {
        start_profile('\OxidEsales\Eshop\Core\ViewConfig::setViewConfigParam');
        $this->_a_config_params[$s_name] = $s_value;
        stop_profile('\OxidEsales\Eshop\Core\ViewConfig::setViewConfigParam');
    }
    /**
     * Returns current view config parameter
     *
     * @param string $sName name of parameter to get
     *
     * @return mixed
     */
    public function get_view_config_param($s_name)
    {
        start_profile('\OxidEsales\Eshop\Core\ViewConfig::getViewConfigParam');
        if ($this->_o_shop && isset($this->_o_shop->{$s_name})) {
            $s_value = $this->_o_shop->{$s_name};
        } elseif ($this->_a_view_data && isset($this->_a_view_data[$s_name])) {
            $s_value = $this->_a_view_data[$s_name];
        } else {
            $s_value = $this->_a_config_params[$s_name] ?? null;
        }
        stop_profile('\OxidEsales\Eshop\Core\ViewConfig::getViewConfigParam');
        return $s_value;
    }
    /**
     * Sets shop object and view data to view config. This is needed mostly for
     * old templates
     *
     * @param \OxidEsales\Eshop\Application\Model\Shop $oShop     shop object
     * @param array                                    $aViewData view data array
     */
    public function set_view_shop($o_shop, $a_view_data): void
    {
        $this->_o_shop = $o_shop;
        $this->_a_view_data = $a_view_data;
    }
    /**
     * Returns forms hidden session parameters
     *
     * @return string
     */
    public function get_hidden_sid()
    {
        if (($s_value = $this->get_view_config_param('hiddensid')) === null) {
            $session = Registry::get_session();
            $s_value = $session->hidden_sid();
            // appending language info to form
            $language = Registry::get_lang()->get_form_lang();
            if ($language) {
                $s_value .= "\n{$language}";
            }
            $s_value .= $this->get_additional_request_parameters();
            $this->set_view_config_param('hiddensid', $s_value);
        }
        return $s_value;
    }
    /**
     * If any hidden parameters needed for sending with request
     *
     * @return string
     */
    protected function get_additional_request_parameters()
    {
        return '';
    }
    public function get_shop_url()
    {
        return Registry::get_config()->get_shop_url();
    }
    /**
     * Returns shops self link
     *
     * @return string
     */
    public function get_self_link()
    {
        if (($s_value = $this->get_view_config_param('selflink')) === null) {
            $s_value = Registry::get_config()->get_shop_home_url();
            $this->set_view_config_param('selflink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops self ssl link
     *
     * @return string
     */
    public function get_ssl_self_link()
    {
        if ($this->is_admin()) {
            // using getSelfLink() method in admin mode (#2745)
            return $this->get_self_link();
        }
        if (($s_value = $this->get_view_config_param('sslselflink')) === null) {
            $s_value = Registry::get_config()->get_shop_secure_home_url();
            $this->set_view_config_param('sslselflink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops base directory path
     *
     * @return string
     */
    public function get_base_dir()
    {
        if (($basedir = $this->get_view_config_param('basedir')) === null) {
            $basedir = Registry::get_config()->get_shop_url();
            $this->set_view_config_param('basedir', $basedir);
        }
        return $basedir;
    }
    /**
     * Returns shops utility directory path
     *
     * @return string
     */
    public function get_core_utils_dir()
    {
        if (($s_value = $this->get_view_config_param('coreutilsdir')) === null) {
            $s_value = Registry::get_config()->get_core_utils_url();
            $this->set_view_config_param('coreutilsdir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops action link
     *
     * @return string
     */
    public function get_self_action_link()
    {
        if (($s_value = $this->get_view_config_param('selfactionlink')) === null) {
            $s_value = Registry::get_config()->get_shop_current_url();
            $this->set_view_config_param('selfactionlink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops home path
     *
     * @return string
     */
    public function get_current_home_dir()
    {
        if (($s_value = $this->get_view_config_param('currenthomedir')) === null) {
            $s_value = Registry::get_config()->get_current_shop_url();
            $this->set_view_config_param('currenthomedir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops basket link
     *
     * @return string
     */
    public function get_basket_link()
    {
        if (($s_value = $this->get_view_config_param('basketlink')) === null) {
            $s_value = Registry::get_config()->get_shop_home_url() . 'cl=basket';
            $this->set_view_config_param('basketlink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops order link
     *
     * @return string
     */
    public function get_order_link()
    {
        if (($s_value = $this->get_view_config_param('orderlink')) === null) {
            $s_value = Registry::get_config()->get_shop_secure_home_url() . 'cl=user';
            $this->set_view_config_param('orderlink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops payment link
     *
     * @return string
     */
    public function get_payment_link()
    {
        if (($s_value = $this->get_view_config_param('paymentlink')) === null) {
            $s_value = Registry::get_config()->get_shop_secure_home_url() . 'cl=payment';
            $this->set_view_config_param('paymentlink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops order execution link
     *
     * @return string
     */
    public function get_exe_order_link()
    {
        if (($s_value = $this->get_view_config_param('exeorderlink')) === null) {
            $s_value = Registry::get_config()->get_shop_secure_home_url() . 'cl=order&amp;fnc=execute';
            $this->set_view_config_param('exeorderlink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops order confirmation link
     *
     * @return string
     */
    public function get_order_confirm_link()
    {
        if (($s_value = $this->get_view_config_param('orderconfirmlink')) === null) {
            $s_value = Registry::get_config()->get_shop_secure_home_url() . 'cl=order';
            $this->set_view_config_param('orderconfirmlink', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops resource url
     *
     * @param string $sFile resource file name
     *
     * @return string
     */
    public function get_resource_url($s_file = null)
    {
        if ($s_file) {
            $s_value = Registry::get_config()->get_resource_url($s_file, $this->is_admin());
        } elseif (($s_value = $this->get_view_config_param('basetpldir')) === null) {
            $s_value = Registry::get_config()->get_resource_url('', $this->is_admin());
            $this->set_view_config_param('basetpldir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops current (related to language) templates path
     *
     * @return string
     */
    public function get_template_dir()
    {
        if (($s_value = $this->get_view_config_param('templatedir')) === null) {
            $s_value = Registry::get_config()->get_template_dir($this->is_admin());
            $this->set_view_config_param('templatedir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns shops current templates url
     *
     * @return string
     */
    public function get_url_template_dir()
    {
        if (($s_value = $this->get_view_config_param('urltemplatedir')) === null) {
            $s_value = Registry::get_config()->get_template_url($this->is_admin());
            $this->set_view_config_param('urltemplatedir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns image url
     *
     * @param string $sFile Image file name
     * @param bool   $bSsl  Whether to force SSL
     *
     * @return string
     */
    public function get_image_url($s_file = null, $b_ssl = null)
    {
        if ($s_file) {
            $s_value = Registry::get_config()->get_image_url($this->is_admin(), $b_ssl, null, $s_file);
        } elseif (($s_value = $this->get_view_config_param('imagedir')) === null) {
            $s_value = Registry::get_config()->get_image_url($this->is_admin(), $b_ssl);
            $this->set_view_config_param('imagedir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns non ssl image url
     *
     * @return string
     */
    public function get_no_ssl_image_dir()
    {
        if (($s_value = $this->get_view_config_param('nossl_imagedir')) === null) {
            $s_value = Registry::get_config()->get_image_url($this->is_admin(), false);
            $this->set_view_config_param('nossl_imagedir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns url to pictures directory.
     *
     * @return string
     */
    public function get_picture_dir()
    {
        if (($s_value = $this->get_view_config_param('picturedir')) === null) {
            $s_value = Registry::get_config()->get_picture_url(null, $this->is_admin());
            $this->set_view_config_param('picturedir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns admin path
     *
     * @return string
     */
    public function get_admin_dir()
    {
        if (($s_value = $this->get_view_config_param('sAdminDir')) === null) {
            $s_value = Registry::get_config()->get_config_param('sAdminDir');
            $this->set_view_config_param('sAdminDir', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns currently open shop id
     *
     * @return string
     */
    public function get_active_shop_id()
    {
        if (($s_value = $this->get_view_config_param('shopid')) === null) {
            $s_value = Registry::get_config()->get_shop_id();
            $this->set_view_config_param('shopid', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns ssl mode (on/off)
     *
     * @return string
     */
    public function is_ssl()
    {
        if (($s_value = $this->get_view_config_param('isssl')) === null) {
            $s_value = Registry::get_config()->is_ssl();
            $this->set_view_config_param('isssl', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns visitor ip address
     *
     * @return string
     */
    public function get_remote_address()
    {
        if (($s_value = $this->get_view_config_param('ip')) === null) {
            $s_value = Registry::get_utils_server()->get_remote_address();
            $this->set_view_config_param('ip', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns basket popup identifier
     *
     * @return string
     */
    public function get_popup_ident()
    {
        if (($s_value = $this->get_view_config_param('popupident')) === null) {
            $s_value = md5((string) Registry::get_config()->get_shop_url());
            $this->set_view_config_param('popupident', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns random basket popup identifier
     *
     * @return string
     */
    public function get_popup_ident_rand()
    {
        if (($s_value = $this->get_view_config_param('popupidentrand')) === null) {
            $s_value = md5(time());
            $this->set_view_config_param('popupidentrand', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns list view paging url
     *
     * @return string
     */
    public function get_art_per_page_form()
    {
        if (($s_value = $this->get_view_config_param('artperpageform')) === null) {
            $s_value = Registry::get_config()->get_shop_current_url();
            $this->set_view_config_param('artperpageform', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns "blVariantParentBuyable" parent article config state
     *
     * @return string
     */
    public function is_buyable_parent()
    {
        return Registry::get_config()->get_config_param('blVariantParentBuyable');
    }
    /**
     * Returns config param "blShowBirthdayFields" value
     *
     * @return string
     */
    public function show_birthday_fields()
    {
        return Registry::get_config()->get_config_param('blShowBirthdayFields');
    }
    /**
     * Returns config param "aNrofCatArticles" value
     *
     * @return array
     */
    public function get_nr_of_cat_articles()
    {
        $s_list_type = Registry::get_session()->get_variable('ldtype');
        if (is_null($s_list_type)) {
            $s_list_type = Registry::get_config()->get_config_param('sDefaultListDisplayType');
        }
        if ('grid' === $s_list_type) {
            return Registry::get_config()->get_config_param('aNrofCatArticlesInGrid');
        }
        return Registry::get_config()->get_config_param('aNrofCatArticles');
    }
    /**
     * Returns config param "bl_showWishlist" value
     *
     * @return bool
     */
    public function get_show_wishlist()
    {
        return Registry::get_config()->get_config_param('bl_showWishlist');
    }
    /**
     * Returns config param "bl_showCompareList" value
     *
     * @return bool
     */
    public function get_show_compare_list()
    {
        $my_config = Registry::get_config();
        if (!$my_config->get_config_param('bl_showCompareList') || $my_config->get_config_param('blDisableNavBars') && $my_config->get_active_view()->get_is_order_step()) {
            return false;
        }
        return true;
    }
    /**
     * Returns config param "bl_showListmania" value
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return bool
     */
    public function get_show_listmania()
    {
        return Registry::get_config()->get_config_param('bl_showListmania');
    }
    /**
     * Returns config param "bl_showVouchers" value
     *
     * @return bool
     */
    public function get_show_vouchers()
    {
        return Registry::get_config()->get_config_param('bl_showVouchers');
    }
    /**
     * Returns config param "bl_showGiftWrapping" value
     *
     * @return bool
     */
    public function get_show_gift_wrapping()
    {
        return Registry::get_config()->get_config_param('bl_showGiftWrapping');
    }
    /**
     * Returns session language id
     *
     * @return string
     */
    public function get_act_language_id()
    {
        $s_value = $this->get_view_config_param('lang');
        if ($s_value === null) {
            $language_service = Registry::get_lang();
            $request = Registry::get_request();
            $i_lang = $request->get_request_parameter('lang');
            $s_value = $i_lang !== null ? $language_service->validate_language($i_lang) : $language_service->get_base_language();
            $this->set_view_config_param('lang', $s_value);
        }
        return $s_value;
    }
    /**
     * Returns session language id
     *
     * @return string
     */
    public function get_act_language_abbr()
    {
        return Registry::get_lang()->get_language_abbr($this->get_act_language_id());
    }
    /**
     * Returns name of active view class
     *
     * @return string
     */
    public function get_active_class_name()
    {
        return Registry::get_config()->get_active_view()->get_class_key();
    }
    /**
     * Returns name of a class of top view in the chain
     * (given a generic fnc, e.g. logout)
     *
     * @return string
     */
    public function get_top_active_class_name()
    {
        return Registry::get_config()->get_top_active_view()->get_class_key();
    }
    /**
     * Returns max number of items shown on page
     *
     * @return int
     */
    public function get_art_per_page_count()
    {
        return $this->get_view_config_param('iartPerPage');
    }
    /**
     * Returns navigation url parameters
     *
     * @return string
     */
    public function get_nav_url_params()
    {
        if (($s_params = $this->get_view_config_param('navurlparams')) === null) {
            $s_params = '';
            $a_nav_params = Registry::get_config()->get_active_view()->get_navigation_params();
            foreach ($a_nav_params as $s_name => $s_value) {
                if (isset($s_value)) {
                    if ($s_params) {
                        $s_params .= '&amp;';
                    }
                    $s_params .= "{$s_name}=" . rawurlencode($s_value);
                }
            }
            if ($s_params) {
                $s_params = '&amp;' . $s_params;
            }
            $this->set_view_config_param('navurlparams', $s_params);
        }
        return $s_params;
    }
    /**
     * Returns navigation forms parameters
     *
     * @return string
     */
    public function get_nav_form_params()
    {
        $config_parameters = $this->get_view_config_param('navformparams');
        if ($config_parameters !== null) {
            return $config_parameters;
        }
        $parameters = '';
        foreach (Registry::get_config()->get_top_active_view()->get_navigation_params() as $name => $value) {
            if (isset($value)) {
                $parameters .= \sprintf('<input type="hidden" name="%s" value="%s">' . "\n", $name, Str::get_str()->htmlentities($value));
            }
        }
        $this->set_view_config_param('navformparams', $parameters);
        return $parameters;
    }
    public function get_stock_on_default_message()
    {
        return Registry::get_config()->get_config_param('blStockOnDefaultMessage');
    }
    public function get_stock_off_default_message()
    {
        return Registry::get_config()->get_config_param('blStockOffDefaultMessage');
    }
    public function get_stock_low_default_message(): bool
    {
        return (bool) Registry::get_config()->get_config_param('blStockLowDefaultMessage');
    }
    /**
     * Returns shop version defined in view
     *
     * @return string
     */
    public function get_shop_version()
    {
        return $this->get_view_config_param('sShopVersion');
    }
    /**
     * Returns AJAX request url
     *
     * @return  string
     */
    public function get_ajax_link()
    {
        return $this->get_view_config_param('ajaxlink');
    }
    /**
     * Returns multishop status
     *
     * @return bool
     */
    public function is_multi_shop()
    {
        $o_shop = Registry::get_config()->get_active_shop();
        return isset($o_shop->oxshops__oxismultishop) && (bool) $o_shop->oxshops__oxismultishop->value;
    }
    /**
     * Returns session Remote Access token. Later you can pass the token over rtoken URL param
     * when you want to access the shop, for example, from different client.
     *
     * @return string
     */
    public function get_remote_access_token()
    {
        return Registry::get_session()->get_remote_access_token();
    }
    /**
     * Returns name of a view class, which will be active for an action
     * (given a generic fnc, e.g. logout)
     *
     * @return string
     */
    public function get_action_class_name()
    {
        return Registry::get_config()->get_active_view()->get_action_class_name();
    }
    /**
     * Returns name of a class of top view in the chain
     * (given a generic fnc, e.g. logout)
     *
     * @return string
     */
    public function get_top_action_class_name()
    {
        return Registry::get_config()->get_top_active_view()->get_action_class_name();
    }
    /**
     * should basket timeout counter be shown?
     *
     * @return bool
     */
    public function get_show_basket_timeout()
    {
        $session = Registry::get_session();
        return Registry::get_config()->get_config_param('blPsBasketReservationEnabled') && $session->get_basket_reservations()->get_time_left() > 0;
    }
    /**
     * return the seconds left until basket expiration
     *
     * @return int
     */
    public function get_basket_time_left()
    {
        if (!isset($this->_d_basket_time_left)) {
            $session = Registry::get_session();
            $this->_d_basket_time_left = $session->get_basket_reservations()->get_time_left();
        }
        return $this->_d_basket_time_left;
    }
    /**
     * min length of password
     *
     * @return int
     */
    public function get_password_length()
    {
        return Registry::get_input_validator()->get_password_length();
    }
    /**
     * Return country list
     *
     * @return \OxidEsales\Eshop\Application\Model\CountryList
     */
    public function get_country_list()
    {
        if ($this->_o_country_list === null) {
            // passing country list
            $this->_o_country_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Country_List::class);
            $this->_o_country_list->load_active_countries();
        }
        return $this->_o_country_list;
    }
    /**
     * Return path to the requested module file
     *
     *
     * @throws FileException
     *
     */
    public function get_module_path(string $module_id, string $file_path = ''): string
    {
        if (!$file_path || $file_path[0] !== '/') {
            $file_path = '/' . $file_path;
        }
        $file_path = Container_Facade::get(Module_Assets_Path_Resolver_Bridge_Interface::class)->get_assets_path($module_id) . $file_path;
        $this->validate_module_file($file_path, $module_id);
        return $file_path;
    }
    /**
     * return url to the requested module file
     *
     * @param string $sModule module name (directory name in modules dir)
     * @param string $sFile   file name to lookup
     *
     * @throws \oxFileException
     *
     * @return string
     */
    public function get_module_url($s_module, $s_file = '')
    {
        $config = Registry::get_config();
        return str_replace(rtrim((string) Container_Facade::get_parameter('oxid_esales.shop_source_directory'), '/'), rtrim((string) $config->get_current_shop_url(false), '/'), $this->get_module_path($s_module, $s_file));
    }
    /**
     * return param value
     *
     * @param string $sName param name
     *
     * @return mixed
     */
    public function get_view_theme_param($s_name)
    {
        if (Registry::get_config()->is_theme_option($s_name)) {
            return Registry::get_config()->get_config_param($s_name);
        }
        return false;
    }
    /**
     * Returns true if selection lists must be displayed in details page
     *
     * @return bool
     */
    public function show_select_lists()
    {
        return (bool) Registry::get_config()->get_config_param('bl_perfLoadSelectLists');
    }
    /**
     * Returns true if selection lists must be displayed in details page
     *
     * @return bool
     */
    public function show_select_lists_in_list()
    {
        return $this->show_select_lists() && Registry::get_config()->get_config_param('bl_perfLoadSelectListsInAList');
    }
    /**
     * Checks if alternative image server is configured.
     *
     * @return bool
     */
    public function is_alt_image_server_configured()
    {
        return (bool) Container_Facade::get_parameter('oxid_esales.alternative_image_url');
    }
    /**
     * Get config parameter for view to check if functionality is turned on or off.
     *
     * @param string $sParamName config parameter name.
     *
     * @return bool
     */
    public function is_functionality_enabled($s_param_name)
    {
        return (bool) Registry::get_config()->get_config_param($s_param_name);
    }
    /**
     * Returns active theme name
     *
     * @return string
     */
    public function get_active_theme()
    {
        if ($this->_s_active_theme === null) {
            $o_theme = ox_new(\Oxid_Esales\Eshop\Core\Theme::class);
            $this->_s_active_theme = $o_theme->get_active_theme_id();
        }
        return $this->_s_active_theme;
    }
    /**
     * Returns shop logo image file name from config option
     *
     * @return string
     */
    public function get_shop_logo()
    {
        if ($this->_s_shop_logo === null) {
            $s_logo_image = Container_Facade::get_parameter('oxid_esales.shop_logo');
            if (empty($s_logo_image)) {
                $s_logo_image = 'logo_' . strtolower((string) Registry::get_config()->get_edition()->value) . '.png';
            }
            $this->set_shop_logo($s_logo_image);
        }
        return $this->_s_shop_logo;
    }
    /**
     * Sets shop logo
     *
     * @param string $sLogo shop logo image file name
     */
    public function set_shop_logo($s_logo): void
    {
        $this->_s_shop_logo = $s_logo;
    }
    /**
     * retrieve session challenge token from session
     *
     * @return string
     */
    public function get_session_challenge_token()
    {
        if (Registry::get_session()->is_session_started()) {
            $session = Registry::get_session();
            return $session->get_session_challenge_token();
        }
        return '';
    }
    /**
     * Return shop edition (EE|CE|PE)
     *
     * @return string
     */
    public function get_edition()
    {
        return Registry::get_config()->get_edition()->value;
    }
    /**
     * Hook for modules.
     * Returns array of params => values which are used in hidden forms and as additional url params.
     * NOTICE: this method SHOULD return raw (non encoded into entities) parameters, because values
     * are processed by htmlentities() to avoid security and broken templates problems
     *
     * @return array
     */
    public function get_additional_navigation_parameters()
    {
        return [];
    }
    /**
     * Hook for modules.
     * Template variable getter. Returns additional params for url
     *
     * @return string
     */
    public function get_additional_parameters()
    {
        return '';
    }
    /**
     * Hook for modules.
     * Collects additional _GET parameters used by eShop
     *
     * @return string
     */
    public function add_request_parameters()
    {
        return '';
    }
    /**
     * Hook for modules.
     * returns additional url params for dynamic url building
     *
     * @param string $listType
     *
     * @return string
     */
    public function get_dyn_url_parameters($list_type)
    {
        return '';
    }
    /**
     * @throws FileException
     */
    private function validate_module_file(string $file_path, string $module_id): void
    {
        if (!file_exists($file_path)) {
            $exception = new File_Exception("Requested file not found for module {$module_id} ({$file_path})");
            if (Container_Facade::get_parameter('oxid_esales.debug_mode')) {
                throw $exception;
            }
            Registry::get_logger()->error($exception->get_message(), [$exception]);
        }
    }
}