<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database\Adapter\Doctrine\Database;
use Oxid_Esales\Eshop\Core\Exception\Input_Exception;
use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
use stdClass;
/**
 * Discounts manager.
 */
class Discount extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxdiscount';
    /**
     * Stores amount of articles which are applied for current discount
     *
     * @var double
     */
    protected $_d_amount;
    /**
     * Basket ident
     *
     * @var string
     */
    protected $_s_basket_ident;
    /**
     * Is discount for article or For category
     *
     * @var bool
     */
    protected $_bl_is_for_article_or_for_category;
    /**
     * Is discount set for article, array index article id
     *
     * @var array
     */
    protected $_a_has_article_discounts = [];
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxdiscount');
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
        if (!$s_oxid) {
            return false;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_db->execute('delete from oxobject2discount where oxobject2discount.oxdiscountid = :oxdiscountid', ['oxdiscountid' => $s_oxid]);
        return parent::delete($s_oxid);
    }
    /**
     * Save the discount.
     * Assigns a value to oxsort, if it was null
     * Does input validation before saving the discount.
     *
     * Returns saving status
     *
     * @throws InputException
     * @throws StandardException
     *
     * @return bool
     */
    public function save()
    {
        // Auto assign oxsort, if it is null
        $oxsort = $this->oxdiscount__oxsort->value;
        if (is_null($oxsort)) {
            $shop_id = $this->oxdiscount__oxshopid->value;
            $new_sort = $this->get_next_oxsort($shop_id);
            $this->oxdiscount__oxsort = new \Ox_Field($new_sort, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        // Validate oxsort before saving
        if (!is_numeric($this->oxdiscount__oxsort->value)) {
            /** @var InputException $exception */
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
            $exception->set_message('DISCOUNT_ERROR_OXSORT_NOT_A_NUMBER');
            throw $exception;
        }
        try {
            $save_status = parent::save();
        } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $exception) {
            if ($exception->get_code() == \Oxid_Esales\Eshop\Core\Database\Adapter\Doctrine\Database::DUPLICATE_KEY_ERROR_CODE && str_contains($exception->get_message(), 'UNIQ_OXSORT')) {
                $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
                $exception->set_message('DISCOUNT_ERROR_OXSORT_NOT_UNIQUE');
            }
            throw $exception;
        }
        return $save_status;
    }
    /**
     * Check for global discount (no articles, no categories)
     *
     * @return bool
     */
    public function is_global_discount()
    {
        if (is_null($this->_bl_is_for_article_or_for_category)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_query = 'select 1
                        from oxobject2discount
                        where oxdiscountid = :oxdiscountid and (oxtype = :oxtypearticles or oxtype = :oxtypecategories)';
            $params = ['oxdiscountid' => $this->oxdiscount__oxid->value, 'oxtypearticles' => 'oxarticles', 'oxtypecategories' => 'oxcategories'];
            $this->_bl_is_for_article_or_for_category = $o_db->get_one($s_query, $params) ? false : true;
        }
        return $this->_bl_is_for_article_or_for_category;
    }
    /**
     * Checks if discount applies for article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     *
     * @return bool
     */
    public function is_for_article($o_article)
    {
        // item discounts may only be applied for basket
        if ($this->oxdiscount__oxaddsumtype->value == 'itm') {
            return false;
        }
        if ($this->oxdiscount__oxamount->value || $this->oxdiscount__oxprice->value) {
            return false;
        }
        if ($this->oxdiscount__oxpriceto->value && $this->oxdiscount__oxpriceto->value < $o_article->get_base_price()) {
            return false;
        }
        if ($this->is_global_discount()) {
            return true;
        }
        $s_article_id = $o_article->get_product_id();
        if (!isset($this->_a_has_article_discounts[$s_article_id])) {
            $bl_result = $this->is_article_assigned($o_article) || $this->is_categories_assigned($o_article->get_category_ids());
            $this->_a_has_article_discounts[$s_article_id] = $bl_result;
        }
        return $this->_a_has_article_discounts[$s_article_id];
    }
    /**
     * Checks if discount is setup for some basket item
     *
     * @param object $oArticle basket item
     *
     * @return bool
     */
    public function is_for_basket_item($o_article)
    {
        if ($this->oxdiscount__oxamount->value == 0 && $this->oxdiscount__oxprice->value == 0) {
            return false;
        }
        // skipping bundle discounts
        if ($this->oxdiscount__oxaddsumtype->value == 'itm') {
            return false;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // check if this article is assigned
        $s_q = 'select 1 from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype ';
        $s_q .= $this->get_product_check_query($o_article);
        $params = ['oxdiscountid' => $this->oxdiscount__oxid->value, 'oxtype' => 'oxarticles'];
        if (!$bl_ok = (bool) $o_db->get_one($s_q, $params)) {
            // checking article category
            return $this->check_for_article_categories($o_article);
        }
        return $bl_ok;
    }
    /**
     * Tests if total amount or price (price priority) of articles that can be applied to current discount fits to discount configuration
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket
     *
     * @return bool
     */
    public function is_for_basket_amount($o_basket)
    {
        $d_amount = 0;
        $a_basket_items = $o_basket->get_contents();
        foreach ($a_basket_items as $o_basket_item) {
            $o_basket_article = $o_basket_item->get_article(false);
            if ($this->oxdiscount__oxaddsumtype->value != 'itm') {
                $bl_for_basket_item = $this->is_for_basket_item($o_basket_article);
            } else {
                $bl_for_basket_item = $this->is_for_bundle_item($o_basket_article);
            }
            if ($bl_for_basket_item) {
                $d_rate = $o_basket->get_basket_currency()->rate;
                if ($this->oxdiscount__oxprice->value) {
                    if ($o_price = $o_basket_article->get_price()) {
                        $d_amount += $o_price->get_price() * $o_basket_item->get_amount() / $d_rate;
                    }
                } elseif ($this->oxdiscount__oxamount->value) {
                    $d_amount += $o_basket_item->get_amount();
                }
            }
        }
        return $this->is_for_amount($d_amount);
    }
    /**
     * Tests if passed amount or price fits current discount (price priority)
     *
     * @param double $dAmount amount or price to check (price priority)
     *
     * @return bool
     */
    public function is_for_amount($d_amount)
    {
        $bl_is = true;
        if ($this->oxdiscount__oxprice->value && ($d_amount < $this->oxdiscount__oxprice->value || $d_amount > $this->oxdiscount__oxpriceto->value)) {
            $bl_is = false;
        } elseif ($this->oxdiscount__oxamount->value && ($d_amount < $this->oxdiscount__oxamount->value || $d_amount > $this->oxdiscount__oxamountto->value)) {
            $bl_is = false;
        }
        return $bl_is;
    }
    /**
     * Checks if discount is setup for whole basket
     *
     * @param object $oBasket basket object
     *
     * @return bool
     */
    public function is_for_basket($o_basket)
    {
        // initial configuration check
        if ($this->oxdiscount__oxamount->value == 0 && $this->oxdiscount__oxprice->value == 0) {
            return false;
        }
        $o_summary = $o_basket->get_basket_summary();
        // amounts check
        if ($this->oxdiscount__oxamount->value && ($o_summary->i_article_count < $this->oxdiscount__oxamount->value || $o_summary->i_article_count > $this->oxdiscount__oxamountto->value)) {
            return false;
            // price check
        } elseif ($this->oxdiscount__oxprice->value) {
            $d_rate = $o_basket->get_basket_currency()->rate;
            if ($o_summary->d_article_discountable_price < $this->oxdiscount__oxprice->value * $d_rate || $o_summary->d_article_discountable_price > $this->oxdiscount__oxpriceto->value * $d_rate) {
                return false;
            }
        }
        // oxobject2discount configuration check
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = 'select 1 from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype in ("oxarticles", "oxcategories" ) ';
        $params = ['oxdiscountid' => $this->oxdiscount__oxid->value];
        return !(bool) $o_db->get_one($s_q, $params);
    }
    /**
     * Checks if discount type is bundle discount
     *
     * @param object $oArticle article object
     *
     * @return bool
     */
    public function is_for_bundle_item($o_article)
    {
        if ($this->oxdiscount__oxaddsumtype->value != 'itm') {
            return false;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = 'select 1 from oxobject2discount where oxdiscountid = :oxdiscountid';
        $s_q .= $this->get_product_check_query($o_article);
        $params = ['oxdiscountid' => $this->get_id()];
        if (!$bl_ok = (bool) $o_db->get_one($s_q, $params)) {
            // additional checks for amounts and other dependencies
            return $this->check_for_article_categories($o_article);
        }
        return $bl_ok;
    }
    /**
     * Checks if discount type is whole basket bundle discount
     *
     * @param object $oBasket basket object
     *
     * @return bool
     */
    public function is_for_bundle_basket($o_basket)
    {
        if ($this->oxdiscount__oxaddsumtype->value != 'itm') {
            return false;
        }
        return $this->is_for_basket($o_basket);
    }
    /**
     * Returns absolute discount value
     *
     * @param float     $dPrice  item price
     * @param float|int $dAmount item amount, interpretted only when discount is absolute (default 1)
     *
     * @return float
     */
    public function get_abs_value($d_price, $d_amount = 1)
    {
        if ($this->oxdiscount__oxaddsumtype->value == '%') {
            return $d_price * ($this->oxdiscount__oxaddsum->value / 100);
        }
        $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
        return $this->oxdiscount__oxaddsum->value * $d_amount * $o_cur->rate;
    }
    /**
     * Return discount percent
     *
     * @param double $dPrice - price from which calculates discount
     *
     * @return double
     */
    public function get_percentage($d_price)
    {
        if ($this->get_add_sum_type() == 'abs' && $d_price > 0) {
            return $this->get_add_sum() / $d_price * 100;
        }
        return $this->get_add_sum();
    }
    /**
     * Return add sum in abs type discount with efected currency rate;
     * Return discount percent value in other way;
     *
     * @return double
     */
    public function get_add_sum()
    {
        if ($this->oxdiscount__oxaddsumtype->value == 'abs') {
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            return $this->oxdiscount__oxaddsum->value * $o_cur->rate;
        }
        return $this->oxdiscount__oxaddsum->value;
    }
    /**
     * Return addsum type
     *
     * @return string
     */
    public function get_add_sum_type()
    {
        return $this->oxdiscount__oxaddsumtype->value;
    }
    /**
     * Returns amount of items to bundle
     *
     * @param double $dAmount item amount
     *
     * @return double
     */
    public function get_bundle_amount($d_amount)
    {
        // Multiplying bundled articles count, if allowed
        if ($this->oxdiscount__oxitmmultiple->value && $this->oxdiscount__oxamount->value > 0) {
            return floor($d_amount / $this->oxdiscount__oxamount->value) * $this->oxdiscount__oxitmamount->value;
        }
        return $this->oxdiscount__oxitmamount->value;
    }
    /**
     * Returns compact discount object which is used in oxbasket
     *
     * @return stdClass
     */
    public function get_simple_discount()
    {
        $o_discount = new stdClass();
        $o_discount->s_oxid = $this->get_id();
        $o_discount->s_discount = $this->oxdiscount__oxtitle->value;
        $o_discount->s_type = $this->oxdiscount__oxaddsumtype->value;
        return $o_discount;
    }
    /**
     * Returns article ids assigned to discount
     *
     * @return array
     */
    public function get_article_ids()
    {
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $params = ['oxdiscountid' => $this->get_id(), 'oxtype' => 'oxarticles'];
        return $db->get_col('select `oxobjectid` from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype', $params);
    }
    /**
     * Returns category ids asigned to discount
     *
     * @return array
     */
    public function get_category_ids()
    {
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $params = ['oxdiscountid' => $this->get_id(), 'oxtype' => 'oxcategories'];
        return $db->get_col('select `oxobjectid` from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype', $params);
    }
    /**
     * Increment the maximum value of oxsort found in the database by certain amount and return it.
     *
     * @param int $shopId The id of the current shop
     *
     * @return int The incremented oxsort
     */
    public function get_next_oxsort($shop_id)
    {
        $query = 'SELECT MAX(`oxsort`)+10 FROM `oxdiscount` WHERE `oxshopid` = :oxshopid';
        $next_sort = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($query, ['oxshopid' => $shop_id]);
        return (int) $next_sort;
    }
    /**
     * Checks if discount may be applied according amounts info
     *
     * @param object $oArticle article object to chesk
     *
     * @return bool
     */
    protected function check_for_article_categories($o_article)
    {
        // check if article is in some assigned category
        $a_cat_ids = $o_article->get_category_ids();
        if (!$a_cat_ids || !count($a_cat_ids)) {
            // no categories are set for article, so no discounts from categories..
            return false;
        }
        $s_cat_ids = '(' . implode(',', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_cat_ids)) . ')';
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // getOne appends limit 1, so this one should be fast enough
        $s_q = "select oxobjectid from oxobject2discount \n            where oxdiscountid = :oxdiscountid \n                and oxobjectid in {$s_cat_ids} \n                and oxtype = :oxtype";
        return $o_db->get_one($s_q, ['oxdiscountid' => $this->oxdiscount__oxid->value, 'oxtype' => 'oxcategories']);
    }
    /**
     * Returns part of query for discount check. If product is variant - query contains both id check e.g.
     * "and (oxobjectid = '...' or oxobjectid = '...')
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oProduct product used for discount check
     *
     * @return string
     */
    protected function get_product_check_query($o_product)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // check if this article is assigned
        if ($s_parent_id = $o_product->get_parent_id()) {
            return ' and ( oxobjectid = ' . $o_db->quote($o_product->get_product_id()) . ' or oxobjectid = ' . $o_db->quote($s_parent_id) . ' )';
        }
        return ' and oxobjectid = ' . $o_db->quote($o_product->get_product_id());
    }
    /**
     * Checks whether this article is assigned to discount
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     *
     * @return bool
     */
    protected function is_article_assigned($o_article)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = 'select 1
                from oxobject2discount
                where oxdiscountid = :oxdiscountid 
                    and oxtype = :oxtype ';
        $s_q .= $this->get_product_check_query($o_article);
        $params = ['oxdiscountid' => $this->oxdiscount__oxid->value, 'oxtype' => 'oxarticles'];
        return $o_db->get_one($s_q, $params) ? true : false;
    }
    /**
     * Checks whether categories are assigned to discount
     *
     * @param array $aCategoryIds
     *
     * @return bool
     */
    protected function is_categories_assigned($a_category_ids)
    {
        if (empty($a_category_ids)) {
            return false;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_category_ids = '(' . implode(',', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_category_ids)) . ')';
        $s_q = "select 1\n                from oxobject2discount\n                where oxdiscountid = :oxdiscountid and oxobjectid in {$s_category_ids} and oxtype = :oxtype";
        $params = ['oxdiscountid' => $this->oxdiscount__oxid->value, 'oxtype' => 'oxcategories'];
        return $o_db->get_one($s_q, $params) ? true : false;
    }
}