<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Lightweight variant handler. Implemnets only absolutely needed oxArticle methods.
 */
class Simple_Variant extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model implements \Oxid_Esales\Eshop\Core\Contract\I_Url
{
    /**
     * Use lazy loading for this item
     *
     * @var bool
     */
    protected $_bl_use_lazy_loading = true;
    /**
     * Variant price
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_price;
    /**
     * Parent article
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_parent;
    /**
     * Stardard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_a_std_urls = [];
    /**
     * Stardard/dynamic article urls for languages
     *
     * @var array
     */
    protected $_a_base_std_urls = [];
    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_a_seo_urls = [];
    /**
     * user object
     *
     * @var \OxidEsales\Eshop\Application\Model\User
     */
    protected $_o_user;
    /**
     * Initializes instance
     */
    public function __construct()
    {
        parent::__construct();
        $this->_s_cache_key = 'simplevariants';
        $this->init('oxarticles');
    }
    /**
     * Implementing (fakeing) performance friendly method from oxArticle
     * oxbase
     */
    public function get_select_lists()
    {
        return null;
    }
    /**
     * Returns article user
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    public function get_article_user()
    {
        if ($this->_o_user === null) {
            $this->_o_user = $this->get_user();
        }
        return $this->_o_user;
    }
    /**
     * get user Group A, B or C price, returns db price if user is not in groups
     *
     * @return double
     */
    protected function get_group_price()
    {
        $d_price = $this->oxarticles__oxprice->value;
        if ($o_user = $this->get_article_user()) {
            if ($o_user->in_group('oxidpricea')) {
                $d_price = $this->oxarticles__oxpricea->value;
            } elseif ($o_user->in_group('oxidpriceb')) {
                $d_price = $this->oxarticles__oxpriceb->value;
            } elseif ($o_user->in_group('oxidpricec')) {
                $d_price = $this->oxarticles__oxpricec->value;
            }
        }
        // #1437/1436C - added config option, and check for zero A,B,C price values
        if (Registry::get_config()->get_config_param('blOverrideZeroABCPrices') && (float) $d_price == 0) {
            return $this->oxarticles__oxprice->value;
        }
        return $d_price;
    }
    /**
     * Implementing (faking) performance friendly method from oxArticle
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_price()
    {
        $my_config = Registry::get_config();
        // 0002030 No need to return price if it disabled for better performance.
        if (!$my_config->get_config_param('bl_perfLoadPrice')) {
            return;
        }
        if ($this->_o_price === null) {
            $this->_o_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
            if ($d_price = $this->get_group_price()) {
                $d_price = $this->modify_group_price($d_price);
                $this->_o_price->set_price($d_price, $this->_d_vat);
                $this->apply_parent_vat($this->_o_price);
                $this->apply_currency($this->_o_price);
                // apply discounts
                $this->apply_parent_discounts($this->_o_price);
            } elseif ($o_parent = $this->get_parent()) {
                $this->_o_price = $o_parent->get_price();
            }
        }
        return $this->_o_price;
    }
    /**
     * Make changes to price on getting price.
     *
     * @param float $price
     * @return float
     */
    public function modify_group_price($price)
    {
        return $price;
    }
    /**
     * Applies currency factor
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
     * @param object                       $oCur   Currency object
     */
    protected function apply_currency(\Oxid_Esales\Eshop\Core\Price $o_price, $o_cur = null)
    {
        if (!$o_cur) {
            $o_cur = Registry::get_config()->get_act_shop_currency_object();
        }
        $o_price->multiply($o_cur->rate);
    }
    /**
     * Applies discounts which should be applied in general case (for 0 amount)
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
     */
    protected function apply_parent_discounts($o_price)
    {
        if ($o_parent = $this->get_parent()) {
            $o_parent->apply_discounts_for_variant($o_price);
        }
    }
    /**
     * apply parent article VAT to given price
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice price object
     */
    protected function apply_parent_vat($o_price)
    {
        if (($o_parent = $this->get_parent()) && !Registry::get_config()->get_config_param('bl_perfCalcVatOnlyForBasketOrder')) {
            $o_parent->apply_vats($o_price);
        }
    }
    /**
     * Price setter
     *
     * @param object $oPrice price object
     */
    public function set_price($o_price): void
    {
        $this->_o_price = $o_price;
    }
    /**
     * Returns formated product price.
     *
     * @return double
     */
    public function get_f_price()
    {
        if ($o_price = $this->get_price()) {
            return Registry::get_lang()->format_currency($o_price->get_brutto_price());
        }
        return null;
    }
    /**
     * Sets parent article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oParent Parent article
     */
    public function set_parent($o_parent): void
    {
        $this->_o_parent = $o_parent;
    }
    /**
     * Parent article getter.
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_parent()
    {
        return $this->_o_parent;
    }
    /**
     * Get link type
     *
     * @return int
     */
    public function get_link_type()
    {
        if ($o_parent = $this->get_parent()) {
            return $o_parent->get_link_type();
        }
        return 0;
    }
    /**
     * Checks if article is assigned to category
     *
     * @param string $sCatNid category ID
     *
     * @return bool
     */
    public function in_category($s_cat_nid)
    {
        if ($o_parent = $this->get_parent()) {
            return $o_parent->in_category($s_cat_nid);
        }
        return false;
    }
    /**
     * Checks if article is assigned to price category $sCatNID
     *
     * @param string $sCatNid Price category ID
     *
     * @return bool
     */
    public function in_price_category($s_cat_nid)
    {
        if ($o_parent = $this->get_parent()) {
            return $o_parent->in_price_category($s_cat_nid);
        }
        return false;
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
        if (!isset($this->_a_base_std_urls[$i_lang][$i_link_type])) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->set_id($this->get_id());
            $o_article->set_link_type($i_link_type);
            $this->_a_base_std_urls[$i_lang][$i_link_type] = $o_article->get_base_std_link($i_lang, $bl_add_id, $bl_full);
        }
        return $this->_a_base_std_urls[$i_lang][$i_link_type];
    }
    /**
     * Gets article link
     *
     * @param int   $iLang   required language [optional]
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function get_std_link($i_lang = null, $a_params = [])
    {
        if ($i_lang === null) {
            $i_lang = (int) $this->get_language();
        }
        $i_link_type = $this->get_link_type();
        if (!isset($this->_a_std_urls[$i_lang][$i_link_type])) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->set_id($this->get_id());
            $o_article->set_link_type($i_link_type);
            $this->_a_std_urls[$i_lang][$i_link_type] = $o_article->get_std_link($i_lang, $a_params);
        }
        return $this->_a_std_urls[$i_lang][$i_link_type];
    }
    /**
     * Returns raw recommlist seo url
     *
     * @param int $iLang language id
     *
     * @return string
     */
    public function get_base_seo_link($i_lang)
    {
        return Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Article::class)->get_article_url($this, $i_lang, $i_link_type);
    }
    /**
     * Gets article link
     *
     * @param int $iLang required language id [optional]
     *
     * @return string
     */
    public function get_link($i_lang = null)
    {
        if ($i_lang === null) {
            $i_lang = (int) $this->get_language();
        }
        if (!Registry::get_utils()->seo_is_active()) {
            return $this->get_std_link($i_lang);
        }
        $i_link_type = $this->get_link_type();
        if (!isset($this->_a_seo_urls[$i_lang][$i_link_type])) {
            $this->_a_seo_urls[$i_lang][$i_link_type] = $this->get_base_seo_link($i_lang);
        }
        return $this->_a_seo_urls[$i_lang][$i_link_type];
    }
}