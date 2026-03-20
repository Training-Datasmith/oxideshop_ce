<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Order delivery manager.
 * Currently calculates price/costs.
 */
class Delivery extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Calculation rule
     */
    public const CALCULATION_RULE_ONCE_PER_CART = 0;
    public const CALCULATION_RULE_FOR_EACH_DIFFERENT_PRODUCT = 1;
    public const CALCULATION_RULE_FOR_EACH_PRODUCT = 2;
    /**
     * Condition type
     */
    public const CONDITION_TYPE_PRICE = 'p';
    public const CONDITION_TYPE_AMOUNT = 'a';
    public const CONDITION_TYPE_SIZE = 's';
    public const CONDITION_TYPE_WEIGHT = 'w';
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxdelivery';
    /**
     * Total count of product items which are covered by current delivery
     * (used for caching purposes across several methods)
     *
     * @var double
     */
    protected $_i_item_cnt = 0;
    /**
     * Total count of products which are covered by current delivery
     * (used for caching purposes across several methods)
     *
     * @var double
     */
    protected $_i_prod_cnt = 0;
    /**
     * Total price of products which are covered by current delivery
     * (used for caching purposes across several methods)
     *
     * @var double
     */
    protected $_d_price = 0;
    /**
     * Current delivery price object which keeps price info
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_price;
    /**
     * Article Ids which are assigned to current delivery
     *
     * @var array
     */
    protected $_a_art_ids;
    /**
     * Category Ids which are assigned to current delivery
     *
     * @var array
     */
    protected $_a_cat_ids;
    /**
     * If article has free shipping
     *
     * @var bool
     */
    protected $_bl_free_shipping = true;
    /**
     * Product list storage
     *
     * @var array
     */
    protected static $_a_product_list = [];
    /**
     * Delivery VAT config
     *
     * @var bool
     */
    protected $_bl_del_vat_on_top = false;
    /**
     * Countries ISO assigned to current delivery.
     *
     * @var array
     */
    protected $_a_countries_iso;
    /**
     * RDFa delivery sets assigned to current delivery.
     *
     * @var array
     */
    protected $_a_rd_fa_delivery_set;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxdelivery');
        $this->set_del_vat_on_top(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blDeliveryVatOnTop'));
    }
    /**
     * Delivery VAT config setter
     *
     * @param bool $blOnTop delivery vat config
     */
    public function set_del_vat_on_top($bl_on_top): void
    {
        $this->_bl_del_vat_on_top = $bl_on_top;
    }
    /**
     * Collects article Ids which are assigned to current delivery
     *
     * @return array
     */
    public function get_articles()
    {
        if (is_null($this->_a_art_ids)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'select oxobjectid from oxobject2delivery 
                where oxdeliveryid = :oxdeliveryid and oxtype = :oxtype';
            $a_art_ids = $o_db->get_col($s_q, ['oxdeliveryid' => $this->get_id(), 'oxtype' => 'oxarticles']);
            $this->_a_art_ids = $a_art_ids;
        }
        return $this->_a_art_ids;
    }
    /**
     * Collects category Ids which are assigned to current delivery
     *
     * @return array
     */
    public function get_categories()
    {
        if (is_null($this->_a_cat_ids)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'select oxobjectid from oxobject2delivery 
                where oxdeliveryid = :oxdeliveryid and oxtype = :oxtype';
            $a_cat_ids = $o_db->get_col($s_q, ['oxdeliveryid' => $this->get_id(), 'oxtype' => 'oxcategories']);
            $this->_a_cat_ids = $a_cat_ids;
        }
        return $this->_a_cat_ids;
    }
    /**
     * Checks if delivery has assigned articles
     *
     * @return bool
     */
    public function has_articles()
    {
        return (bool) count($this->get_articles());
    }
    /**
     * Checks if delivery has assigned categories
     *
     * @return bool
     */
    public function has_categories()
    {
        return (bool) count($this->get_categories());
    }
    /**
     * Returns amount (total net price/weight/volume/Amount) on which delivery price is applied
     *
     * @param \OxidEsales\Eshop\Application\Model\BasketItem $oBasketItem basket item object
     *
     * @return double
     */
    public function get_delivery_amount($o_basket_item)
    {
        $d_amount = 0;
        $o_product = $o_basket_item->get_article(false);
        if ($o_product->is_order_article()) {
            $o_product = $o_product->get_article();
        }
        $bl_excl_non_material = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blExclNonMaterialFromDelivery');
        // mark free shipping products
        if ($o_product->oxarticles__oxfreeshipping->value || $o_product->oxarticles__oxnonmaterial->value && $bl_excl_non_material) {
            if ($this->_bl_free_shipping !== false) {
                $this->_bl_free_shipping = true;
            }
        } else {
            $this->_bl_free_shipping = false;
            switch ($this->get_condition_type()) {
                case self::CONDITION_TYPE_PRICE:
                    // price
                    if ($this->get_calculation_rule() == self::CALCULATION_RULE_FOR_EACH_PRODUCT) {
                        $d_amount += $o_product->get_price()->get_price();
                    } elseif ($o_basket_item->get_price()) {
                        $d_amount += $o_basket_item->get_price()->get_price();
                        // price// currency conversion must already be done in price class / $oCur->rate; // $oBasketItem->oPrice->getPrice() / $oCur->rate;
                    }
                    break;
                case self::CONDITION_TYPE_WEIGHT:
                    // weight
                    if ($this->get_calculation_rule() == self::CALCULATION_RULE_FOR_EACH_PRODUCT) {
                        $d_amount += $o_product->get_weight();
                    } else {
                        $d_amount += $o_basket_item->get_weight();
                    }
                    break;
                case self::CONDITION_TYPE_SIZE:
                    // size
                    $d_amount += $o_product->get_size();
                    if ($this->get_calculation_rule() != self::CALCULATION_RULE_FOR_EACH_PRODUCT) {
                        $d_amount *= $o_basket_item->get_amount();
                    }
                    break;
                case self::CONDITION_TYPE_AMOUNT:
                    // amount
                    $d_amount += $o_basket_item->get_amount();
                    break;
            }
            if ($o_basket_item->get_price()) {
                $this->_d_price += $o_basket_item->get_price()->get_price();
            }
        }
        return $d_amount;
    }
    /**
     * Delivery price setter
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice delivery price to set
     */
    public function set_delivery_price($o_price): void
    {
        $this->_o_price = $o_price;
    }
    /**
     * Returns oxPrice object for delivery costs
     *
     * @param double $dVat delivery vat
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_delivery_price($d_vat = null)
    {
        if ($this->_o_price === null) {
            // loading oxPrice object for final price calculation
            $o_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
            $o_price->set_netto_mode($this->_bl_del_vat_on_top);
            $o_price->set_vat($d_vat);
            // if article is free shipping, price for delivery will be not calculated
            if (!$this->_bl_free_shipping) {
                $o_price->add($this->get_cost_sum());
            }
            $this->set_delivery_price($o_price);
        }
        return $this->_o_price;
    }
    /**
     * Delete this object from the database, returns true on success.
     *
     * @param string $sOxId Object ID (default null)
     *
     * @return bool
     */
    public function delete($s_ox_id = null)
    {
        if (!$s_ox_id) {
            $s_ox_id = $this->get_id();
        }
        if (!$s_ox_id) {
            return false;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = 'delete from `oxobject2delivery` where `oxobject2delivery`.`oxdeliveryid` = :oxdeliveryid';
        $o_db->execute($s_q, ['oxdeliveryid' => $s_ox_id]);
        return parent::delete($s_ox_id);
    }
    /**
     * Checks if delivery fits for current basket
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket shop basket
     *
     * @return bool
     */
    public function is_for_basket($o_basket)
    {
        $this->_i_item_cnt = 0;
        $this->_i_prod_cnt = 0;
        $this->_d_price = 0;
        // amount for conditional check
        $bl_has_articles = $this->has_articles();
        $bl_has_categories = $this->has_categories();
        $bl_use = true;
        $aggregated_delivery_amount = 0;
        $bl_for_basket = false;
        // category & article check
        if ($bl_has_categories || $bl_has_articles) {
            $bl_use = false;
            $a_delivery_articles = $this->get_articles();
            $a_delivery_categories = $this->get_categories();
            foreach ($o_basket->get_contents() as $o_content) {
                //V FS#1954 - load delivery for variants from parent article
                $o_article = $o_content->get_article(false);
                $s_product_id = $o_article->get_product_id();
                $s_parent_id = $o_article->get_parent_id();
                if ($bl_has_articles && (in_array($s_product_id, $a_delivery_articles) || $s_parent_id && in_array($s_parent_id, $a_delivery_articles))) {
                    $bl_use = true;
                    $art_amount = $this->get_delivery_amount($o_content);
                    if ($this->is_delivery_rule_fit_by_article($art_amount)) {
                        $bl_for_basket = true;
                        $this->update_item_count($o_content);
                        $this->increase_product_count();
                    }
                    if (!$bl_for_basket) {
                        $aggregated_delivery_amount += $art_amount;
                    }
                } elseif ($bl_has_categories) {
                    if (isset(self::$_a_product_list[$s_product_id])) {
                        $o_product = self::$_a_product_list[$s_product_id];
                    } else {
                        $o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                        $o_product->set_skip_assign(true);
                        if (!$o_product->load($s_product_id)) {
                            continue;
                        }
                        $o_product->set_id($s_product_id);
                        self::$_a_product_list[$s_product_id] = $o_product;
                    }
                    foreach ($a_delivery_categories as $s_cat_id) {
                        if ($o_product->in_category($s_cat_id)) {
                            $art_amount = $this->get_delivery_amount($o_content);
                            $bl_use = true;
                            if ($this->is_delivery_rule_fit_by_article($art_amount)) {
                                $bl_for_basket = true;
                                $this->update_item_count($o_content);
                                $this->increase_product_count();
                            }
                            if (!$bl_for_basket) {
                                $aggregated_delivery_amount += $art_amount;
                            }
                            //HR#5650 product might be in multiple rule categories, counting it once is enough
                            break;
                        }
                    }
                }
            }
        } else {
            // regular amounts check
            foreach ($o_basket->get_contents() as $o_content) {
                $art_amount = $this->get_delivery_amount($o_content);
                if ($this->is_delivery_rule_fit_by_article($art_amount)) {
                    $bl_for_basket = true;
                    $this->update_item_count($o_content);
                    $this->increase_product_count();
                }
                if (!$bl_for_basket) {
                    $aggregated_delivery_amount += $art_amount;
                }
            }
        }
        //#M1130: Single article in Basket, checked as free shipping, is not buyable (step 3 no payments found)
        if (!$bl_for_basket && $bl_use && ($this->check_delivery_amount($aggregated_delivery_amount) || $this->_bl_free_shipping)) {
            return true;
        }
        return $bl_for_basket;
    }
    /**
     * Update total count of product items are covered by current delivery.
     *
     * @param \OxidEsales\Eshop\Application\Model\BasketItem $content
     */
    protected function update_item_count($content)
    {
        $this->_i_item_cnt += $content->get_amount();
    }
    /**
     * Increase count of products which are covered by current delivery.
     */
    protected function increase_product_count()
    {
        $this->_i_prod_cnt += 1;
    }
    /**
     * checks if amount param is ok for this delivery
     *
     * @param double $iAmount amount
     *
     * @return boolean
     */
    protected function check_delivery_amount($i_amount)
    {
        $bl_result = false;
        if ($this->get_condition_type() == self::CONDITION_TYPE_PRICE) {
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $i_amount /= $o_cur->rate;
        }
        if ($i_amount >= $this->get_condition_from() && $i_amount <= $this->get_condition_to()) {
            return true;
        }
        return $bl_result;
    }
    /**
     * returns delivery id
     *
     * @param string $sTitle delivery name
     *
     * @return string
     */
    public function get_id_by_name($s_title)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_q = 'SELECT `oxid` FROM `' . $table_view_name_generator->get_view_name('oxdelivery') . '` 
            WHERE `oxtitle` = :oxtitle';
        return $o_db->get_one($s_q, ['oxtitle' => $s_title]);
    }
    /**
     * Returns array of country ISO's which are assigned to current delivery
     *
     * @return array
     */
    public function get_countries_iso()
    {
        if ($this->_a_countries_iso === null) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $this->_a_countries_iso = [];
            $s_select = '
                SELECT
                    `oxcountry`.`oxisoalpha2`
                FROM `oxcountry`
                    LEFT JOIN `oxobject2delivery` ON `oxobject2delivery`.`oxobjectid` = `oxcountry`.`oxid`
                WHERE `oxobject2delivery`.`oxdeliveryid` = :oxdeliveryid
                    AND `oxobject2delivery`.`oxtype` = :oxtype';
            $rs = $o_db->get_col($s_select, ['oxdeliveryid' => $this->get_id(), 'oxtype' => 'oxcountry']);
            $this->_a_countries_iso = $rs;
        }
        return $this->_a_countries_iso;
    }
    /**
     * Returns condition type (type >= from <= to) : a - amount, s - size, w -weight, p - price
     *
     * @return string
     */
    public function get_condition_type()
    {
        return $this->oxdelivery__oxdeltype->value;
    }
    /**
     * Returns condition from value (type >= from <= to)
     *
     * @return string
     */
    public function get_condition_from()
    {
        return $this->oxdelivery__oxparam->value;
    }
    /**
     * Returns condition to value (type >= from <= to)
     *
     * @return string
     */
    public function get_condition_to()
    {
        return $this->oxdelivery__oxparamend->value;
    }
    /**
     * Returns calculation rule: 0 - Once per Cart; 1 - Once for each different product 2 - For each product
     *
     * @return int
     */
    public function get_calculation_rule()
    {
        return $this->oxdelivery__oxfixed->value;
    }
    /**
     * Returns amount cost
     *
     * @return float
     */
    public function get_add_sum()
    {
        return $this->oxdelivery__oxaddsum->value;
    }
    /**
     * Returns type of cost: % - percentage; abs - absolute value
     *
     * @return string
     */
    public function get_add_sum_type()
    {
        return $this->oxdelivery__oxaddsumtype->value;
    }
    /**
     * Calculate multiplier for price calculation
     *
     * @return float|int
     */
    protected function get_multiplier()
    {
        $d_amount = 0;
        if ($this->get_calculation_rule() == self::CALCULATION_RULE_ONCE_PER_CART) {
            $d_amount = 1;
        } elseif ($this->get_calculation_rule() == self::CALCULATION_RULE_FOR_EACH_DIFFERENT_PRODUCT) {
            $d_amount = $this->_i_prod_cnt;
        } elseif ($this->get_calculation_rule() == self::CALCULATION_RULE_FOR_EACH_PRODUCT) {
            $d_amount = $this->_i_item_cnt;
        }
        return $d_amount;
    }
    /**
     * Calculate cost sum
     *
     * @return float
     */
    protected function get_cost_sum()
    {
        if ($this->get_add_sum_type() == 'abs') {
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            return $this->get_add_sum() * $o_cur->rate * $this->get_multiplier();
        }
        return $this->_d_price / 100 * $this->get_add_sum();
    }
    /**
     * Checks if delivery rule applies for basket because of one article's amount.
     * Delivery rules that are to be applied once per cart can be ruled out here.
     *
     * @param integer $artAmount product amount
     *
     * @return bool
     */
    protected function is_delivery_rule_fit_by_article($art_amount)
    {
        $result = false;
        if ($this->get_calculation_rule() != self::CALCULATION_RULE_ONCE_PER_CART) {
            if (!$this->_bl_free_shipping && $this->check_delivery_amount($art_amount)) {
                $result = true;
            }
        }
        return $result;
    }
}