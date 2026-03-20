<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use function array_key_exists;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Ox_Object_Exception;
/**
 * Class, responsible for retrieving correct vat for users and articles
 */
class Vat_Selector extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * State is VAT calculation for category is set
     *
     * @var bool
     */
    protected $_bl_cat_vat_set;
    /**
     * keeps loaded user Vats for later reusage
     *
     * @var array
     */
    protected static $_a_user_vat_cache = [];
    /**
     * get VAT for user, can NOT be null
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser        given  user object
     * @param bool                                     $blCacheReset reset cache
     *
     * @throws oxObjectException if wrong country
     * @return double|false
     */
    public function get_user_vat(\Oxid_Esales\Eshop\Application\Model\User $o_user, $bl_cache_reset = false)
    {
        $cache_id = sprintf('%s_%s', $o_user->get_id(), $o_user->get_field_data('oxcountryid') ?? '');
        if (!$bl_cache_reset && array_key_exists($cache_id, self::$_a_user_vat_cache) && self::$_a_user_vat_cache[$cache_id] !== null) {
            return self::$_a_user_vat_cache[$cache_id];
        }
        $ret = false;
        $s_country_id = $this->get_vat_country($o_user);
        if ($s_country_id) {
            $o_country = ox_new(\Oxid_Esales\Eshop\Application\Model\Country::class);
            if (!$o_country->load($s_country_id)) {
                throw ox_new(\Oxid_Esales\Eshop\Core\Exception\Object_Exception::class);
            }
            if ($o_country->is_foreign_country()) {
                $ret = $this->get_foreign_country_user_vat($o_user, $o_country);
            }
        }
        self::$_a_user_vat_cache[$cache_id] = $ret;
        return $ret;
    }
    /**
     * get vat for user of a foreign country
     *
     * @param \OxidEsales\Eshop\Application\Model\User    $oUser    given user object
     * @param \OxidEsales\Eshop\Application\Model\Country $oCountry given country object
     *
     * @return mixed
     */
    protected function get_foreign_country_user_vat(\Oxid_Esales\Eshop\Application\Model\User $o_user, \Oxid_Esales\Eshop\Application\Model\Country $o_country)
    {
        if ($o_country->is_in_eu()) {
            if ($o_user->oxuser__oxustid->value) {
                return 0;
            }
            return false;
        }
        return 0;
    }
    /**
     * return Vat value for category type assignment only
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle given article
     *
     * @return float|false
     */
    protected function get_vat_for_article_category(\Oxid_Esales\Eshop\Application\Model\Article $o_article)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_cat_t = $table_view_name_generator->get_view_name('oxcategories');
        if ($this->_bl_cat_vat_set === null) {
            $s_select = "SELECT oxid FROM {$s_cat_t} WHERE oxvat IS NOT NULL LIMIT 1";
            //no category specific vats in shop?
            //then for performance reasons we just return false
            $this->_bl_cat_vat_set = (bool) $o_db->get_one($s_select);
        }
        if (!$this->_bl_cat_vat_set) {
            return false;
        }
        $s_o2c = $table_view_name_generator->get_view_name('oxobject2category');
        $s_sql = "SELECT c.oxvat\n                 FROM {$s_cat_t} AS c, {$s_o2c} AS o2c\n                 WHERE c.oxid=o2c.oxcatnid AND\n                       o2c.oxobjectid = :oxobjectid AND\n                       c.oxvat IS NOT NULL\n                 ORDER BY o2c.oxtime ";
        $f_vat = $o_db->get_one($s_sql, ['oxobjectid' => $o_article->get_id()]);
        if ($f_vat !== false && $f_vat !== null) {
            return $f_vat;
        }
        return false;
    }
    /**
     * get VAT for given article, can NOT be null
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle given article
     *
     * @return double
     */
    public function get_article_vat(\Oxid_Esales\Eshop\Application\Model\Article $o_article)
    {
        start_profile('_assignPriceInternal');
        // article has its own VAT ?
        if (($d_article_vat = $o_article->get_custom_vat()) !== null) {
            stop_profile('_assignPriceInternal');
            return $d_article_vat;
        }
        if (($d_article_vat = $this->get_vat_for_article_category($o_article)) !== false) {
            stop_profile('_assignPriceInternal');
            return $d_article_vat;
        }
        stop_profile('_assignPriceInternal');
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('dDefaultVAT');
    }
    /**
     * Currently returns vats percent that can be applied for basket
     * item ( executes \OxidEsales\Eshop\Application\Model\VatSelector::getArticleVat()). Can be used to override
     * basket price calculation behaviour (\OxidEsales\Eshop\Application\Model\Article::getBasketPrice())
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @param \OxidEsales\Eshop\Application\Model\Basket  $oBasket  oxbasket object
     *
     * @return double
     */
    public function get_basket_item_vat(\Oxid_Esales\Eshop\Application\Model\Article $o_article, $o_basket)
    {
        return $this->get_article_vat($o_article);
    }
    /**
     * get article user vat
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     *
     * @return double|false
     */
    public function get_article_user_vat(\Oxid_Esales\Eshop\Application\Model\Article $o_article)
    {
        if ($o_user = $o_article->get_article_user()) {
            return $this->get_user_vat($o_user);
        }
        return false;
    }
    /**
     * Returns country id which VAT should be applied to.
     * Depending on configuration option either user billing country or shipping country (if available) is returned.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object
     *
     * @return string
     */
    protected function get_vat_country(\Oxid_Esales\Eshop\Application\Model\User $o_user)
    {
        $bl_use_shipping_country = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShippingCountryVat');
        if ($bl_use_shipping_country) {
            $a_addresses = $o_user->get_user_addresses($o_user->get_id());
            $s_selected_address = $o_user->get_selected_address_id();
            if (isset($a_addresses[$s_selected_address])) {
                return $a_addresses[$s_selected_address]->oxaddress__oxcountryid->value;
            }
        }
        return $o_user->get_field_data('oxcountryid');
    }
}