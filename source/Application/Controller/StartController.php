<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Starting shop page.
 * Shop starter, manages starting visible articles, etc.
 */
class Start_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * List display type
     *
     * @var string
     */
    protected $_s_list_display_type;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/shop/start';
    /**
     * Start page meta description CMS ident
     *
     * @var string
     */
    protected $_s_meta_description_ident = 'oxstartmetadescription';
    /**
     * Start page meta keywords CMS ident
     *
     * @var string
     */
    protected $_s_meta_keywords_ident = 'oxstartmetakeywords';
    /**
     * Are actions on
     *
     * @var bool
     */
    protected $_bl_load_actions;
    /**
     * Newest article list
     *
     * @var array
     */
    protected $_a_new_article_list;
    /**
     * Sign if to load and show top5articles action
     *
     * @var bool
     */
    protected $_bl_top5action = true;
    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = true;
    /**
     * Executes parent::render(), loads action articles
     * (oxarticlelist::loadActionArticles()). Returns name of
     * template file to render.
     *
     * @return  string  cuurent template file name
     */
    public function render()
    {
        if (Registry::get_request()->get_request_escaped_parameter('showexceptionpage') == '1') {
            return 'message/exception';
        }
        parent::render();
        return $this->_s_this_template;
    }
    /**
     * Template variable getter. Returns if actions are ON
     *
     * @return string
     */
    protected function get_load_actions_param()
    {
        if ($this->_bl_load_actions === null) {
            $this->_bl_load_actions = false;
            if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadAktion')) {
                $this->_bl_load_actions = true;
            }
        }
        return $this->_bl_load_actions;
    }
    /**
     * Template variable getter. Returns newest article list
     *
     * @return array
     */
    public function get_newest_articles()
    {
        if ($this->_a_new_article_list === null) {
            $this->_a_new_article_list = [];
            if ($this->get_load_actions_param()) {
                // newest articles
                $o_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
                $o_art_list->load_newest_articles();
                if ($o_art_list->count()) {
                    $this->_a_new_article_list = $o_art_list;
                }
            }
        }
        return $this->_a_new_article_list;
    }
    /**
     * Returns SEO suffix for page title
     *
     * @return string
     */
    public function get_title_suffix()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_shop()->oxshops__oxstarttitle->value;
    }
    /**
     * Returns view canonical url
     *
     * @return string
     */
    public function get_canonical_url()
    {
        if (\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active() && $o_view_conf = $this->get_view_config()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->prepare_canonical_url($o_view_conf->get_home_link());
        }
    }
    /**
     * Returns active banner list
     *
     * @return \OxidEsales\Eshop\Application\Model\ActionList|null
     */
    public function get_banners()
    {
        $o_banner_list = null;
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadAktion')) {
            $o_banner_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Action_List::class);
            $o_banner_list->load_banners();
        }
        return $o_banner_list;
    }
    /**
     * Returns manufacturer list for manufacturer slider
     *
     * @return array|null
     */
    public function get_manufacturer_for_slider()
    {
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadManufacturerTree')) {
            return $this->get_manufacturerlist();
        }
        return null;
    }
}