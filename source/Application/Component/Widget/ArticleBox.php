<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Article box widget
 */
class Article_Box extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * User component used in template.
     *
     * @var array
     */
    protected $_a_component_names = ['oxcmp_user' => 1, 'oxcmp_basket' => 1, 'oxcmp_cur' => 1];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_template = 'widget/product/boxproduct';
    /**
     * Current article
     *
     * @var \OxidEsales\Eshop\Application\Model\Article|null
     */
    protected $_o_article;
    /**
     * Returns active category
     *
     * @return \OxidEsales\Eshop\Application\Model\Category|null
     */
    public function get_active_category()
    {
        $o_category = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_top_active_view()->get_active_category();
        if ($o_category) {
            $this->set_active_category($o_category);
        }
        return $this->_o_act_category;
    }
    /**
     * Renders template based on widget type or just use directly passed path of template
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $s_widget_type = $this->get_view_parameter('sWidgetType');
        $s_list_type = $this->get_view_parameter('sListType');
        if ($s_widget_type && $s_list_type) {
            $this->_s_template = 'widget/' . $s_widget_type . '/' . $s_list_type;
        }
        $s_force_template = $this->get_view_parameter('oxwtemplate');
        if ($s_force_template) {
            $this->_s_template = $s_force_template;
        }
        return $this->_s_template;
    }
    /**
     * Sets box product
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Box product
     */
    public function set_product($o_article): void
    {
        $this->_o_article = $o_article;
    }
    /**
     * Get product article
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_product()
    {
        if (is_null($this->_o_article)) {
            if ($this->get_view_parameter('_object')) {
                $o_article = $this->get_view_parameter('_object');
            } else {
                $s_add_dyn_params = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_top_active_view()->get_add_url_params();
                $s_add_dyn_params = $this->update_dynamic_parameters($s_add_dyn_params);
                $o_article = $this->get_article_by_id($this->get_view_parameter('anid'));
                $this->add_dyn_params_to_link($s_add_dyn_params, $o_article);
            }
            $this->set_product($o_article);
        }
        return $this->_o_article;
    }
    /**
     * get link of current top view
     *
     * @param int $iLang requested language
     *
     * @return string
     */
    public function get_link($i_lang = null)
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_top_active_view()->get_link($i_lang);
    }
    /**
     * Returns if VAT is included in price
     *
     * @return bool
     */
    public function is_vat_included()
    {
        return (bool) $this->get_view_parameter('isVatIncluded');
    }
    /**
     * Returns wish list id
     *
     * @return string
     */
    public function get_wish_id()
    {
        return $this->get_view_parameter('owishid');
    }
    /**
     * Returns remove function
     *
     * @return string
     */
    public function get_remove_function()
    {
        return $this->get_view_parameter('removeFunction');
    }
    /**
     * Returns toBasket function
     *
     * @return string
     */
    public function get_to_basket_function()
    {
        return $this->get_view_parameter('toBasketFunction');
    }
    /**
     * Returns if toCart must be disabled
     *
     * @return bool
     */
    public function get_disable_to_cart()
    {
        return (bool) $this->get_view_parameter('blDisableToCart');
    }
    /**
     * Returns list item id with identifier
     *
     * @return string
     */
    public function get_index()
    {
        return $this->get_view_parameter('iIndex');
    }
    /**
     * Returns recommendation id
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return string
     */
    public function get_recomm_id()
    {
        return $this->get_view_parameter('recommid');
    }
    /**
     * Returns iteration number
     *
     * @return string
     */
    public function get_iteration()
    {
        return $this->get_view_parameter('iIteration');
    }
    /**
     * Returns the answer if main link must be showed
     *
     * @return bool
     */
    public function get_show_main_link()
    {
        return (bool) $this->get_view_parameter('showMainLink');
    }
    /**
     * Returns if alternate product exists
     *
     * @return bool
     */
    public function get_alt_product()
    {
        return (bool) $this->get_view_parameter('altproduct');
    }
    /**
     * Appends dyn params to url.
     *
     * @param string                                      $sAddDynParams Dyn params
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle      Article
     *
     * @return bool
     */
    protected function add_dyn_params_to_link($s_add_dyn_params, $o_article)
    {
        $bl_added_params = false;
        if ($s_add_dyn_params) {
            $bl_seo = \Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active();
            if (!$bl_seo) {
                // only if seo is off..
                $o_article->append_std_link($s_add_dyn_params);
            }
            $o_article->append_link($s_add_dyn_params);
            $bl_added_params = true;
        }
        return $bl_added_params;
    }
    /**
     * Returns prepared article by id.
     *
     * @param string $sArticleId Article id
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function get_article_by_id($s_article_id)
    {
        /** @var \OxidEsales\Eshop\Application\Model\Article $oArticle */
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->load($s_article_id);
        $i_link_type = $this->get_view_parameter('iLinkType');
        if ($this->get_view_parameter('inlist')) {
            $o_article->set_in_list();
        }
        if ($i_link_type) {
            $o_article->set_link_type($i_link_type);
        }
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        if ($o_recomm_list = $this->get_active_recomm_list()) {
            $o_article->text = $o_recomm_list->get_art_description($o_article->get_id());
        }
        // END deprecated
        return $o_article;
    }
    /**
     * @param string $dynamicParameters
     *
     * @return string
     */
    protected function update_dynamic_parameters($dynamic_parameters)
    {
        return $dynamic_parameters;
    }
}