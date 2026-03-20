<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Exception\No_Article_Exception;
use Oxid_Esales\Eshop\Core\Price;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Psr\Log\Logger_Interface;
use stdClass;
/**
 * Basket manager
 */
#[\Allow_Dynamic_Properties]
class Basket extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Array or oxbasketitem objects
     *
     * @var array
     */
    protected $_a_basket_contents = [];
    /**
     * Number of different product type in basket.
     * The value is updated only after recalculating a basket.
     *
     * @var int
     */
    protected $_i_products_cnt = 0;
    /**
     * Number of basket items.
     * The value is updated only after recalculating a basket.
     *
     * @var double
     */
    protected $_d_items_cnt = 0.0;
    /**
     * Basket weight.
     * The value is updated only after recalculating a basket.
     *
     * @var double
     */
    protected $_d_weight = 0.0;
    /**
     * Total basket price
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_price = null;
    /**
     * Basket calculation mode netto
     *
     * @var bool
     */
    protected $_is_calculation_mode_netto = null;
    /**
     * Basket netto sum
     *
     * @var float
     */
    protected $_d_netto_sum = null;
    /**
     * Basket brutto sum
     *
     * @var float
     */
    protected $_d_brutto_sum = null;
    /**
     * The list of all basket item prices
     *
     * @var \OxidEsales\Eshop\Core\PriceList
     */
    protected $_o_products_price_list = null;
    /**
     * Basket discounts information
     *
     * @var array
     */
    protected $_a_discounts = [];
    /**
     * Basket items discounts information
     *
     * @var array
     */
    protected $_a_item_discounts = [];
    /**
     * Basket order ID. Usually this ID is set on last order step
     *
     * @var string
     */
    protected $_s_order_id = null;
    /**
     * Array of vouchers applied on basket price
     *
     * @var array
     */
    protected $_a_vouchers = [];
    /**
     * Additional costs array of \OxidEsales\Eshop\Core\Price objects
     *
     * @var array
     */
    protected $_a_costs = [];
    /**
     * Sum price of articles applicable to discounts
     *
     * @var \OxidEsales\Eshop\Core\PriceList
     */
    protected $_o_discount_products_price_list = null;
    /**
     * Sum price of articles not applicable to discounts
     *
     * @var \OxidEsales\Eshop\Core\PriceList
     */
    protected $_o_not_discounted_products_price_list = null;
    /**
     * Basket recalculation marker
     *
     * @var bool
     */
    protected $_bl_update_needed = true;
    /**
     * oxBasket summary object, usually used for discount calculations etc
     *
     * @var array
     */
    protected $_a_basket_summary = null;
    /**
     * Basket Payment ID
     *
     * @var string
     */
    protected $_s_payment_id = null;
    /**
     * Basket Shipping set ID
     *
     * @var string
     */
    protected $_s_shipping_set_id = null;
    /**
     * Ref. to session user
     *
     * @var \OxidEsales\Eshop\Application\Model\User
     */
    protected $_o_user = null;
    /**
     * Total basket products discount price object (does not include voucher discount)
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_total_discount = null;
    /**
     * Basket voucher discount price object
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_voucher_discount = null;
    /**
     * Basket currency
     *
     * @var stdClass
     */
    protected $_o_currency = null;
    /**
     * Skip or not vouchers availability checking
     *
     * @var bool
     */
    protected $_bl_skip_vouchers_availability_checking = null;
    /**
     * Netto price including discount and voucher
     *
     * @var double
     */
    protected $_d_discounted_product_netto_price = null;
    /**
     * All VAT values with discount and voucher
     *
     * @var array
     */
    protected $_a_discounted_vats = null;
    /**
     * Skip discounts marker
     *
     * @var boolean
     */
    protected $_bl_skip_discounts = false;
    /**
     * User set delivery costs
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_delivery_price = null;
    /**
     * Basket product stock check (live db check) status
     *
     * @var bool
     */
    protected $_bl_check_stock = true;
    /**
     * discount calculation marker
     *
     * @var bool
     */
    protected $_bl_calc_discounts = true;
    /**
     * Basket category id
     *
     * @var string
     */
    protected $_s_basket_category_id = null;
    /**
     * Category change warning state
     *
     * @var bool
     */
    protected $_bl_show_cat_change_warning = false;
    /**
     * new basket item addition state
     *
     * @var bool
     */
    protected $_bl_new_i_tem_added = null;
    /**
     * if basket has downloadable product
     *
     * @var bool
     */
    protected $_bl_downloadable_products = null;
    /**
     * Save basket to data base if user is logged in
     *
     * @var bool
     */
    protected $_bl_save_to_data_base = null;
    /**
     * Save card id
     *
     * @var string
     */
    protected $_s_card_id = null;
    /**
     * Card message.
     *
     * @var string
     */
    protected $_s_card_message = '';
    /**
     * Enables or disable saving to data base
     *
     * @param boolean $blSave
     */
    public function enable_save_to_data_base($bl_save = true)
    {
        $this->_bl_save_to_data_base = $bl_save;
    }
    /**
     * Returns true if saving to data base enabled
     *
     * @return boolean
     */
    public function is_save_to_data_base_enabled()
    {
        if (is_null($this->_bl_save_to_data_base)) {
            $this->_bl_save_to_data_base = (bool) !Registry::get_config()->get_config_param('blPerfNoBasketSaving');
        }
        return $this->_bl_save_to_data_base;
    }
    /**
     * Return true if calculation mode is netto
     *
     * @return bool
     */
    public function is_calculation_mode_netto()
    {
        if ($this->_is_calculation_mode_netto === null) {
            $this->set_calculation_mode_netto($this->is_price_view_mode_netto());
        }
        return $this->_is_calculation_mode_netto;
    }
    /**
     * Set netto calculation mode
     *
     * @param bool $blNettoMode - true in netto; false - turn off
     */
    public function set_calculation_mode_netto($bl_netto_mode = true)
    {
        $this->_is_calculation_mode_netto = (bool) $bl_netto_mode;
    }
    /**
     * Return basket netto sum (in B2B view mode sum include discount)
     *
     * @return float
     */
    public function get_netto_sum()
    {
        return $this->_d_netto_sum;
    }
    /**
     * Return basket brutto sum (in B2C view mode sum include discount)
     *
     * @return float
     */
    public function get_brutto_sum()
    {
        return $this->_d_brutto_sum;
    }
    /**
     * Set basket netto sum
     *
     * @param float $dNettoSum sum of basket in netto mode
     */
    public function set_netto_sum($d_netto_sum)
    {
        $this->_d_netto_sum = $d_netto_sum;
    }
    /**
     * Set basket brutto sum
     *
     * @param float $dBruttoSum sum of basket in brutto mode
     */
    public function set_brutto_sum($d_brutto_sum)
    {
        $this->_d_brutto_sum = $d_brutto_sum;
    }
    /**
     * Checks if configuration allows basket usage or if user agent is search engine
     *
     * @return bool
     */
    public function is_enabled()
    {
        return !Registry::get_utils()->is_search_engine();
    }
    /**
     * change old key to new one but retain key position in array
     *
     * @param string $sOldKey old key
     * @param string $sNewKey new key to place in old one's place
     * @param mixed  $value   (optional)
     */
    protected function change_basket_item_key($s_old_key, $s_new_key, $value = null)
    {
        reset($this->_a_basket_contents);
        $i_old_key_place = 0;
        while (key($this->_a_basket_contents) != $s_old_key && next($this->_a_basket_contents)) {
            ++$i_old_key_place;
        }
        $a_new_copy = array_merge(array_slice($this->_a_basket_contents, 0, $i_old_key_place, true), [$s_new_key => $value], array_slice($this->_a_basket_contents, $i_old_key_place + 1, count($this->_a_basket_contents) - $i_old_key_place, true));
        $this->_a_basket_contents = $a_new_copy;
    }
    /**
     * Adds user item to basket. Returns oxBasketItem object if adding succeeded
     *
     * @param string $sProductID       id of product
     * @param double $dAmount          product amount
     * @param mixed  $aSel             product select lists (default null)
     * @param mixed  $aPersParam       product persistent parameters (default null)
     * @param bool   $blOverride       marker to accumulate passed amount or renew (default false)
     * @param bool   $blBundle         marker if product is bundle or not (default false)
     * @param mixed  $sOldBasketItemId id if old basket item if to change it
     *
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     *
     * @return null|\OxidEsales\Eshop\Application\Model\BasketItem
     */
    public function add_to_basket($s_product_id, $d_amount, $a_sel = null, $a_pers_param = null, $bl_override = false, $bl_bundle = false, $s_old_basket_item_id = null)
    {
        // enabled ?
        if (!$this->is_enabled()) {
            return null;
        }
        // basket exclude
        if (Registry::get_config()->get_config_param('blBasketExcludeEnabled')) {
            if (!$this->can_add_product_to_basket($s_product_id)) {
                $this->set_cat_change_warning_state(true);
                return null;
            } else {
                $this->set_cat_change_warning_state(false);
            }
        }
        $s_item_id = $this->get_item_key($s_product_id, $a_sel, $a_pers_param, $bl_bundle);
        if ($s_old_basket_item_id && strcmp($s_old_basket_item_id, $s_item_id) != 0) {
            if (isset($this->_a_basket_contents[$s_item_id])) {
                // we are merging, so params will just go to the new key
                unset($this->_a_basket_contents[$s_old_basket_item_id]);
                // do not override stock
                $bl_override = false;
            } else {
                // value is null - means isset will fail and real values will be filled
                $this->change_basket_item_key($s_old_basket_item_id, $s_item_id);
            }
        }
        // after some checks item must be removed from basket
        $bl_remove_item = false;
        // initialling exception storage
        $o_ex = null;
        if (isset($this->_a_basket_contents[$s_item_id])) {
            //updating existing
            try {
                // setting stock check status
                $this->_a_basket_contents[$s_item_id]->set_stock_check_status($this->get_stock_check_mode());
                //validate amount
                //possibly throws exception
                $this->_a_basket_contents[$s_item_id]->set_amount($d_amount, $bl_override, $s_item_id);
            } catch (\Oxid_Esales\Eshop\Core\Exception\Out_Of_Stock_Exception $o_ex) {
                // rethrow later
            }
        } else {
            //inserting new
            $o_basket_item = ox_new(\Oxid_Esales\Eshop\Application\Model\Basket_Item::class);
            try {
                $o_basket_item->set_stock_check_status($this->get_stock_check_mode());
                $o_basket_item->init($s_product_id, $d_amount, $a_sel, $a_pers_param, $bl_bundle);
            } catch (No_Article_Exception $o_ex) {
                // in this case that the article does not exist remove the item from the basket by setting its amount to 0
                //$oBasketItem->dAmount = 0;
                $bl_remove_item = true;
            } catch (\Oxid_Esales\Eshop\Core\Exception\Out_Of_Stock_Exception $o_ex) {
                // rethrow later
            } catch (\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception $o_ex) {
                // rethrow later
                $bl_remove_item = true;
            }
            $this->_a_basket_contents[$s_item_id] = $o_basket_item;
        }
        //in case amount is 0 removing item
        if ($this->_a_basket_contents[$s_item_id]->get_amount() == 0 || $bl_remove_item) {
            $this->remove_item($s_item_id);
        } elseif ($bl_bundle) {
            //marking bundles
            $this->_a_basket_contents[$s_item_id]->set_bundle(true);
        }
        //calling update method
        $this->on_update();
        if ($o_ex) {
            throw $o_ex;
        }
        // notifying that new basket item was added
        if (!$bl_bundle) {
            $this->added_new_item($bl_override);
        }
        // returning basket item object
        if (isset($this->_a_basket_contents[$s_item_id]) && $this->_a_basket_contents[$s_item_id] instanceof \Oxid_Esales\Eshop\Application\Model\Basket_Item) {
            $this->_a_basket_contents[$s_item_id]->set_basket_item_key($s_item_id);
        }
        return $this->_a_basket_contents[$s_item_id] ?? null;
    }
    /**
     * Adds order article to basket (method normally used while recalculating order)
     *
     * @param \OxidEsales\Eshop\Application\Model\OrderArticle $oOrderArticle order article to store in basket
     *
     * @return \OxidEsales\Eshop\Application\Model\BasketItem
     */
    public function add_order_article_to_basket($o_order_article)
    {
        // adding only if amount > 0
        if ($o_order_article->oxorderarticles__oxamount->value > 0 && !$o_order_article->is_bundle()) {
            $this->_is_for_order_recalculation = true;
            $s_item_id = $o_order_article->get_id();
            //inserting new
            $this->_a_basket_contents[$s_item_id] = ox_new(\Oxid_Esales\Eshop\Application\Model\Basket_Item::class);
            $this->_a_basket_contents[$s_item_id]->init_from_order_article($o_order_article);
            $this->_a_basket_contents[$s_item_id]->set_wrapping($o_order_article->oxorderarticles__oxwrapid->value);
            $this->_a_basket_contents[$s_item_id]->set_bundle($o_order_article->is_bundle());
            //calling update method
            $this->on_update();
            return $this->_a_basket_contents[$s_item_id];
        } elseif ($o_order_article->is_bundle()) {
            // deleting bundles, they are handled automatically
            $o_order_article->delete();
        }
    }
    /**
     * Sets stock control mode
     *
     * @param bool $blCheck stock control mode
     */
    public function set_stock_check_mode($bl_check)
    {
        $this->_bl_check_stock = $bl_check;
    }
    /**
     * Returns stock control mode
     *
     * @return bool
     */
    public function get_stock_check_mode()
    {
        return $this->_bl_check_stock;
    }
    /**
     * Returns unique basket item identifier which consist from product ID,
     * select lists data, persistent info and bundle property
     *
     * @param string $sProductId       basket item id
     * @param array  $aSel             basket item selectlists
     * @param array  $aPersParam       basket item persistent parameters
     * @param bool   $blBundle         bundle marker
     * @param string $sAdditionalParam possible additional information
     *
     * @return string
     */
    public function get_item_key($s_product_id, $a_sel = null, $a_pers_param = null, $bl_bundle = false, $s_additional_param = '')
    {
        $a_sel = $a_sel != null ? $a_sel : [0 => '0'];
        $s_item_key = md5($s_product_id . '|' . serialize($a_sel) . '|' . serialize($a_pers_param) . '|' . (int) $bl_bundle . '|' . serialize($s_additional_param));
        return $s_item_key;
    }
    /**
     * Removes item from basket
     *
     * @param string $sItemKey basket item key
     */
    public function remove_item($s_item_key)
    {
        if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
            if (isset($this->_a_basket_contents[$s_item_key])) {
                $s_article_id = $this->_a_basket_contents[$s_item_key]->get_product_id();
                if ($s_article_id) {
                    $session = Registry::get_session();
                    $session->get_basket_reservations()->discard_article_reservation($s_article_id);
                }
            }
        }
        unset($this->_a_basket_contents[$s_item_key]);
        // basket exclude
        if (!count($this->_a_basket_contents) && Registry::get_config()->get_config_param('blBasketExcludeEnabled')) {
            $this->set_basket_root_cat_id(null);
        }
    }
    /**
     * Unsets bundled basket items from basket contents array
     */
    protected function clear_bundles()
    {
        reset($this->_a_basket_contents);
        foreach ($this->_a_basket_contents as $s_item_key => $o_basket_item) {
            if ($o_basket_item->is_bundle()) {
                $this->remove_item($s_item_key);
            }
        }
    }
    /**
     * Returns array of bundled articles IDs for basket item
     *
     * @param object $oBasketItem basket item object
     *
     * @return array
     */
    protected function get_article_bundles($o_basket_item)
    {
        $a_bundles = [];
        if ($o_basket_item->is_bundle()) {
            return $a_bundles;
        }
        $o_article = $o_basket_item->get_article(true);
        if ($o_article && $o_article->get_field_data('oxbundleid')) {
            $a_bundles[$o_article->get_field_data('oxbundleid')] = 1;
        }
        return $a_bundles;
    }
    /**
     * Returns array of bundled discount articles
     *
     * @param object $oBasketItem basket item object
     * @param array  $aBundles    array of found bundles
     *
     * @return array
     */
    protected function get_item_bundles($o_basket_item, $a_bundles = [])
    {
        if ($o_basket_item->is_bundle()) {
            return [];
        }
        // does this object still exists ?
        if ($o_article = $o_basket_item->get_article()) {
            $a_discounts = Registry::get(\Oxid_Esales\Eshop\Application\Model\Discount_List::class)->get_basket_item_bundle_discounts($o_article, $this, $this->get_basket_user());
            foreach ($a_discounts as $o_discount) {
                $i_amnt = $o_discount->get_bundle_amount($o_basket_item->get_amount());
                if ($i_amnt) {
                    //init array element
                    if (!isset($a_bundles[$o_discount->oxdiscount__oxitmartid->value])) {
                        $a_bundles[$o_discount->oxdiscount__oxitmartid->value] = 0;
                    }
                    if ($o_discount->oxdiscount__oxitmmultiple->value) {
                        $a_bundles[$o_discount->oxdiscount__oxitmartid->value] += $i_amnt;
                    } else {
                        $a_bundles[$o_discount->oxdiscount__oxitmartid->value] = $i_amnt;
                    }
                }
            }
        }
        return $a_bundles;
    }
    /**
     * Returns array of bundled discount articles for whole basket
     *
     * @param array $aBundles array of found bundles
     *
     * @return array
     */
    protected function get_basket_bundles($a_bundles = [])
    {
        $a_discounts = Registry::get(\Oxid_Esales\Eshop\Application\Model\Discount_List::class)->get_basket_bundle_discounts($this, $this->get_basket_user());
        // calculating amount of non bundled/discount items
        $d_amount = 0;
        foreach ($this->_a_basket_contents as $o_basket_item) {
            if (!($o_basket_item->is_bundle() || $o_basket_item->is_discount_article())) {
                $d_amount += $o_basket_item->get_amount();
            }
        }
        foreach ($a_discounts as $o_discount) {
            if ($o_discount->oxdiscount__oxitmartid->value) {
                if (!isset($a_bundles[$o_discount->oxdiscount__oxitmartid->value])) {
                    $a_bundles[$o_discount->oxdiscount__oxitmartid->value] = 0;
                }
                $a_bundles[$o_discount->oxdiscount__oxitmartid->value] += $o_discount->get_bundle_amount($d_amount);
            }
        }
        return $a_bundles;
    }
    /**
     * Iterates through basket contents and adds bundles to items + adds
     * global basket bundles
     */
    protected function add_bundles()
    {
        $bundles = [];
        foreach ($this->_a_basket_contents as $key => $basket_item) {
            try {
                if (!$basket_item->is_discount_article() && !$basket_item->is_bundle()) {
                    $bundles = $this->get_item_bundles($basket_item, $bundles);
                } else {
                    continue;
                }
                $art_bundles = $this->get_article_bundles($basket_item);
                $this->add_bundles_to_basket($art_bundles);
            } catch (No_Article_Exception $exception) {
                $this->handle_no_article_exception($basket_item, $exception);
            } catch (\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception $exception) {
                $this->remove_item($key);
                Registry::get_utils_view()->add_error_to_display($exception);
            }
        }
        $bundles = $this->get_basket_bundles($bundles);
        if ($bundles) {
            $this->add_bundles_to_basket($bundles);
        }
    }
    /**
     * Adds bundles to basket
     *
     * @param array $aBundles added bundle articles
     */
    protected function add_bundles_to_basket($a_bundles)
    {
        foreach ($a_bundles as $s_bundle_id => $d_amount) {
            if ($d_amount) {
                try {
                    if ($o_bundle_item = $this->add_to_basket($s_bundle_id, $d_amount, null, null, false, true)) {
                        $o_bundle_item->set_as_discount_article(true);
                    }
                } catch (\Oxid_Esales\Eshop\Core\Exception\Article_Exception $o_ex) {
                    // caught and ignored
                    if ($o_ex instanceof \Oxid_Esales\Eshop\Core\Exception\Out_Of_Stock_Exception && $o_ex->get_remaining_amount() > 0) {
                        $s_item_id = $this->get_item_key($s_bundle_id, null, null, true);
                        $this->_a_basket_contents[$s_item_id]->set_as_discount_article(true);
                    }
                }
            }
        }
    }
    /**
     * Iterates through basket items and calculates its prices and discounts
     */
    protected function calc_items_price()
    {
        // resetting
        $this->set_skip_discounts(false);
        $this->_i_products_cnt = 0;
        // count different types
        $this->_d_items_cnt = 0;
        // count of item units
        $this->_d_weight = 0;
        // basket weight
        $this->_o_products_price_list = ox_new(\Oxid_Esales\Eshop\Core\Price_List::class);
        $this->_o_discount_products_price_list = ox_new(\Oxid_Esales\Eshop\Core\Price_List::class);
        $this->_o_not_discounted_products_price_list = ox_new(\Oxid_Esales\Eshop\Core\Price_List::class);
        $o_discount_list = Registry::get(\Oxid_Esales\Eshop\Application\Model\Discount_List::class);
        /** @var \oxBasketItem $oBasketItem */
        foreach ($this->_a_basket_contents as $o_basket_item) {
            $this->_i_products_cnt++;
            $this->_d_items_cnt += $o_basket_item->get_amount();
            $this->_d_weight += $o_basket_item->get_weight();
            if (!$o_basket_item->is_discount_article() && $o_article = $o_basket_item->get_article(true)) {
                $o_basket_price = $o_article->get_basket_price($o_basket_item->get_amount(), $o_basket_item->get_sel_list(), $this);
                $o_basket_item->set_regular_unit_price(clone $o_basket_price);
                if (!$o_article->skip_discounts() && $this->can_calc_discounts()) {
                    // apply basket type discounts for item
                    $a_discounts = $o_discount_list->get_basket_item_discounts($o_article, $this, $this->get_basket_user());
                    reset($a_discounts);
                    /** @var \oxDiscount $oDiscount */
                    foreach ($a_discounts as $o_discount) {
                        $o_basket_price->set_discount($o_discount->get_add_sum(), $o_discount->get_add_sum_type());
                    }
                    $o_basket_price->calculate_discount();
                } else {
                    $o_basket_item->set_skip_discounts(true);
                    $this->set_skip_discounts(true);
                }
                $o_basket_item->set_price($o_basket_price);
                $this->_o_products_price_list->add_to_price_list($o_basket_item->get_price());
                //P collect discount values for basket items which are discountable
                if (!$o_article->skip_discounts()) {
                    $this->_o_discount_products_price_list->add_to_price_list($o_basket_item->get_price());
                } else {
                    $this->_o_not_discounted_products_price_list->add_to_price_list($o_basket_item->get_price());
                    $o_basket_item->set_skip_discounts(true);
                    $this->set_skip_discounts(true);
                }
            } elseif ($o_basket_item->is_bundle()) {
                // if bundles price is set to zero
                $o_price = ox_new(Price::class);
                $o_basket_item->set_price($o_price);
            }
        }
    }
    /**
     * Sets discount calculation mode
     *
     * @param bool $blCalcDiscounts calculate discounts or not
     */
    public function set_discount_calc_mode($bl_calc_discounts)
    {
        $this->_bl_calc_discounts = $bl_calc_discounts;
    }
    /**
     * Returns true if discount calculation is enabled
     *
     * @return bool
     */
    public function can_calc_discounts()
    {
        return $this->_bl_calc_discounts;
    }
    /**
     * Merges two discount arrays. If there are two the same
     * discounts, discount values will be added.
     *
     * @param array $aDiscounts     Discount array
     * @param array $aItemDiscounts Discount array
     *
     * @return array $aDiscounts
     */
    protected function merge_discounts($a_discounts, $a_item_discounts)
    {
        foreach ($a_item_discounts as $s_key => $o_discount) {
            // add prices of the same discounts
            if (array_key_exists($s_key, $a_discounts)) {
                $a_discounts[$s_key]->d_discount += $o_discount->d_discount;
            } else {
                $a_discounts[$s_key] = $o_discount;
            }
        }
        return $a_discounts;
    }
    /**
     * Iterates through basket items and calculates its delivery costs
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    protected function calc_delivery_cost()
    {
        if ($this->_o_delivery_price !== null) {
            return $this->_o_delivery_price;
        }
        $my_config = Registry::get_config();
        $o_delivery_price = ox_new(Price::class);
        if (Registry::get_config()->get_config_param('blDeliveryVatOnTop')) {
            $o_delivery_price->set_netto_price_mode();
        } else {
            $o_delivery_price->set_brutto_price_mode();
        }
        // don't calculate if not logged in
        $o_user = $this->get_basket_user();
        if (!$o_user && !$my_config->get_config_param('blCalculateDelCostIfNotLoggedIn')) {
            return $o_delivery_price;
        }
        $f_del_vat_percent = $this->get_additional_services_vat_percent();
        $o_delivery_price->set_vat($f_del_vat_percent);
        // list of active delivery costs
        if ($my_config->get_config_param('bl_perfLoadDelivery')) {
            $a_delivery_list = Registry::get(\Oxid_Esales\Eshop\Application\Model\Delivery_List::class)->get_delivery_list($this, $o_user, $this->find_deliv_country(), $this->get_shipping_id());
            if (count($a_delivery_list) > 0) {
                foreach ($a_delivery_list as $o_delivery) {
                    $o_delivery_price->add_price($o_delivery->get_delivery_price($f_del_vat_percent));
                }
            }
        }
        return $o_delivery_price;
    }
    /**
     * Basket user getter
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    public function get_basket_user()
    {
        if ($this->_o_user == null) {
            return $this->get_user();
        }
        return $this->_o_user;
    }
    /**
     * Basket user setter
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser Basket user
     */
    public function set_basket_user($o_user)
    {
        $this->_o_user = $o_user;
    }
    /**
     * Get most used vat percent:
     *
     * @return double
     */
    public function get_most_used_vat_percent()
    {
        if ($this->_o_products_price_list) {
            return $this->_o_products_price_list->get_most_used_vat_percent();
        }
    }
    /**
     * Get most used vat percent:
     *
     * @return double
     */
    public function get_additional_services_vat_percent()
    {
        if ($this->_o_products_price_list) {
            if (Registry::get_config()->get_config_param('sAdditionalServVATCalcMethod') == 'proportional') {
                return $this->_o_products_price_list->get_proportional_vat_percent();
            } else {
                return $this->_o_products_price_list->get_most_used_vat_percent();
            }
        }
    }
    /**
     * Get most used vat percent:
     *
     * @return double
     */
    public function is_proportional_calculation_on()
    {
        if (Registry::get_config()->get_config_param('sAdditionalServVATCalcMethod') == 'proportional') {
            return true;
        }
        return false;
    }
    //P
    /**
     * Performs final sum calculation and rounding.
     */
    protected function calc_total_price()
    {
        // 1. add products price
        $d_price = $this->_d_brutto_sum;
        /** @var \OxidEsales\Eshop\Core\Price $oTotalPrice */
        $o_total_price = ox_new(Price::class);
        $o_total_price->set_brutto_price_mode();
        $o_total_price->set_price($d_price);
        // 2. subtract discounts
        if ($d_price && !$this->is_calculation_mode_netto()) {
            // 2.2 applying basket discounts
            $o_total_price->subtract($this->_o_total_discount->get_brutto_price());
            // 2.3 applying voucher discounts
            if ($o_voucher_disc = $this->get_voucher_discount()) {
                $o_total_price->subtract($o_voucher_disc->get_brutto_price());
            }
        }
        // 2.3 add delivery cost
        if (isset($this->_a_costs['oxdelivery'])) {
            $o_total_price->add($this->_a_costs['oxdelivery']->get_brutto_price());
        }
        // 2.4 add wrapping price
        if (isset($this->_a_costs['oxwrapping'])) {
            $o_total_price->add($this->_a_costs['oxwrapping']->get_brutto_price());
        }
        if (isset($this->_a_costs['oxgiftcard'])) {
            $o_total_price->add($this->_a_costs['oxgiftcard']->get_brutto_price());
        }
        // 2.5 add payment price
        if (isset($this->_a_costs['oxpayment'])) {
            $o_total_price->add($this->_a_costs['oxpayment']->get_brutto_price());
        }
        $this->set_price($o_total_price);
    }
    /**
     * Voucher discount setter
     *
     * @param double $dDiscount voucher discount value
     */
    public function set_voucher_discount($d_discount)
    {
        $this->_o_voucher_discount = ox_new(Price::class);
        $this->_o_voucher_discount->set_brutto_price_mode();
        $this->_o_voucher_discount->add($d_discount);
    }
    /**
     * Calculates voucher discount
     */
    protected function calc_voucher_discount()
    {
        if (Registry::get_config()->get_config_param('bl_showVouchers') && ($this->_o_voucher_discount === null || $this->_bl_update_needed && !$this->is_admin())) {
            $this->_o_voucher_discount = $this->get_price_object();
            // calculating price to apply discount
            $d_price = $this->_o_discount_products_price_list->get_sum($this->is_calculation_mode_netto()) - $this->_o_total_discount->get_price();
            // recalculating
            if (count($this->_a_vouchers)) {
                $o_lang = Registry::get_lang();
                foreach ($this->_a_vouchers as $s_voucher_id => $o_std_voucher) {
                    $o_voucher = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher::class);
                    try {
                        // checking
                        $o_voucher->load($o_std_voucher->s_voucher_id);
                        if (!$this->_bl_skip_vouchers_availability_checking) {
                            $o_voucher->check_basket_voucher_availability($this->_a_vouchers, $d_price);
                            $o_voucher->check_user_availability($this->get_basket_user());
                            $o_voucher->mark_as_reserved();
                        }
                        // assigning real voucher discount value as this is the only place where real value is calculated
                        $d_voucherdiscount = $o_voucher->get_discount_value($d_price);
                        if ($d_voucherdiscount > 0) {
                            $d_vat_part = ($d_price - $d_voucherdiscount) / $d_price * 100;
                            if (!$this->_a_discounted_vats) {
                                if ($o_price_list = $this->get_discount_products_price()) {
                                    $this->_a_discounted_vats = $o_price_list->get_vat_info($this->is_calculation_mode_netto());
                                }
                            }
                            // apply discount to vat
                            foreach ($this->_a_discounted_vats as $s_key => $d_vat) {
                                $this->_a_discounted_vats[$s_key] = Price::percent($d_vat, $d_vat_part);
                            }
                        }
                        // accumulating discount value
                        $this->_o_voucher_discount->add($d_voucherdiscount);
                        // collecting formatted for preview
                        $o_std_voucher->f_voucherdiscount = $o_lang->format_currency($d_voucherdiscount, $this->get_basket_currency());
                        $o_std_voucher->d_voucherdiscount = $d_voucherdiscount;
                        // subtracting voucher discount
                        $d_price = $d_price - $d_voucherdiscount;
                    } catch (\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception $o_ex) {
                        // removing voucher on error
                        $o_voucher->un_mark_as_reserved();
                        unset($this->_a_vouchers[$s_voucher_id]);
                        // storing voucher error info
                        Registry::get_utils_view()->add_error_to_display($o_ex, false, true);
                    }
                }
            }
        }
    }
    /**
     * Performs netto price and VATs calculations including discounts and vouchers.
     */
    protected function apply_discounts()
    {
        //apply discounts for brutto price
        $d_discounted_sum = $this->get_discounted_products_sum();
        $o_utils = Registry::get_utils();
        $d_vat_sum = 0;
        foreach ($this->_a_discounted_vats as $d_vat) {
            $d_vat_sum += $o_utils->f_round($d_vat, $this->_o_currency);
        }
        $o_not_discounted = $this->get_not_discount_products_price();
        if ($this->is_calculation_mode_netto()) {
            // netto view mode
            $this->set_netto_sum($this->get_products_price()->get_sum());
            $this->set_brutto_sum($o_not_discounted->get_sum(false) + $d_discounted_sum + $d_vat_sum);
        } else {
            // brutto view mode
            $this->set_netto_sum($o_not_discounted->get_sum() + $d_discounted_sum - $d_vat_sum);
            $this->set_brutto_sum($this->get_products_price()->get_sum(false));
        }
    }
    /**
     * Returns true if view mode is netto
     *
     * @return bool
     */
    public function is_price_view_mode_netto()
    {
        $bl_result = (bool) Registry::get_config()->get_config_param('blShowNetPrice');
        $o_user = $this->get_basket_user();
        if ($o_user) {
            $bl_result = $o_user->is_price_view_mode_netto();
        }
        return $bl_result;
    }
    /**
     * Returns prepared price object depending on view mode
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    protected function get_price_object()
    {
        $o_price = ox_new(Price::class);
        if ($this->is_calculation_mode_netto()) {
            $o_price->set_netto_price_mode();
        } else {
            $o_price->set_brutto_price_mode();
        }
        return $o_price;
    }
    /**
     * Loads basket discounts and calculates discount values.
     */
    protected function calc_basket_discount()
    {
        // resetting
        $this->_a_discounts = [];
        // P using prices sum which has discount, not sum of skipped discounts
        $d_old_price = $this->_o_discount_products_price_list->get_sum($this->is_calculation_mode_netto());
        // add basket discounts
        if ($this->_o_total_discount !== null && isset($this->_is_for_order_recalculation) && $this->_is_for_order_recalculation) {
            //if total discount was set on order recalculation
            $o_total_price = $this->get_total_discount();
            $o_discount = ox_new(\Oxid_Esales\Eshop\Application\Model\Discount::class);
            $o_discount->oxdiscount__oxaddsum = new \Oxid_Esales\Eshop\Core\Field($o_total_price->get_price());
            $o_discount->oxdiscount__oxaddsumtype = new \Oxid_Esales\Eshop\Core\Field('abs');
            $a_discounts[] = $o_discount;
        } else {
            // discounts for basket
            $a_discounts = Registry::get(\Oxid_Esales\Eshop\Application\Model\Discount_List::class)->get_basket_discounts($this, $this->get_basket_user());
        }
        if ($o_price_list = $this->get_discount_products_price()) {
            $this->_a_discounted_vats = $o_price_list->get_vat_info($this->is_calculation_mode_netto());
        }
        /** @var \oxDiscount $oDiscount */
        foreach ($a_discounts as $o_discount) {
            // storing applied discounts
            $o_std_discount = $o_discount->get_simple_discount();
            // skipping bundle discounts
            if ($o_discount->oxdiscount__oxaddsumtype->value == 'itm') {
                continue;
            }
            // saving discount info
            $o_std_discount->d_discount = $o_discount->get_abs_value($d_old_price);
            $d_vat_part = 100 - $o_discount->get_percentage($d_old_price);
            // if discount is more than basket sum
            if ($d_old_price < $o_std_discount->d_discount) {
                $o_std_discount->d_discount = $d_old_price;
                $d_vat_part = 0;
            }
            // apply discount to vat
            foreach ($this->_a_discounted_vats as $s_key => $d_vat) {
                $this->_a_discounted_vats[$s_key] = Price::percent($d_vat, $d_vat_part);
            }
            //storing discount
            if ($o_std_discount->d_discount != 0) {
                $this->_a_discounts[$o_discount->get_id()] = $o_std_discount;
                // subtracting product price after discount
                $d_old_price = $d_old_price - $o_std_discount->d_discount;
            }
        }
    }
    /**
     * Calculates total basket discount value.
     */
    protected function calc_basket_total_discount()
    {
        if ($this->_o_total_discount === null || !$this->is_admin()) {
            $this->_o_total_discount = $this->get_price_object();
            if (is_array($this->_a_discounts)) {
                foreach ($this->_a_discounts as $o_discount) {
                    // skipping bundle discounts
                    if ($o_discount->s_type == 'itm') {
                        continue;
                    }
                    // add discount value to total basket discount
                    $this->_o_total_discount->add($o_discount->d_discount);
                }
            }
        }
    }
    /**
     * Adds Gift price info to $this->oBasket (additional field for
     * basket item "oWrap""). Loads each basket item, checks for
     * wrapping data, updates if available and stores back into
     * $this->oBasket. Returns price object for wrapping.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    protected function calc_basket_wrapping()
    {
        $o_wrapping_prices = ox_new(\Oxid_Esales\Eshop\Core\Price_List::class);
        /** @var \oxBasketItem $oBasketItem */
        foreach ($this->_a_basket_contents as $o_basket_item) {
            if ($o_wrapping = $o_basket_item->get_wrapping()) {
                $o_wrapping_price = $o_wrapping->get_wrapping_price($o_basket_item->get_amount());
                $o_wrapping_price->set_vat($o_basket_item->get_price()->get_vat());
                $o_wrapping_prices->add_to_price_list($o_wrapping_price);
            }
        }
        return $o_wrapping_prices->calculate_to_price();
    }
    /**
     * Adds Gift price info to $this->oBasket (additional field for
     * basket item "oWrap""). Loads each basket item, checks for
     * wrapping data, updates if available and stores back into
     * $this->oBasket. Returns oxprice object for wrapping.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    protected function calc_basket_gift_card()
    {
        $o_gift_card_price = ox_new(Price::class);
        if (Registry::get_config()->get_config_param('blWrappingVatOnTop')) {
            $o_gift_card_price->set_netto_price_mode();
        } else {
            $o_gift_card_price->set_brutto_price_mode();
        }
        $d_vat_percent = $this->get_additional_services_vat_percent();
        $o_gift_card_price->set_vat($d_vat_percent);
        // gift card price calculation
        if ($o_card = $this->get_card()) {
            if ($d_vat_percent !== null) {
                $o_card->set_wrapping_vat($d_vat_percent);
            }
            $o_gift_card_price->add_price($o_card->get_wrapping_price());
        }
        return $o_gift_card_price;
    }
    /**
     * Payment cost calculation, applying payment discount if available.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    protected function calc_payment_cost()
    {
        // resetting values
        $o_payment_price = ox_new(Price::class);
        // payment
        if ($this->_s_payment_id = $this->get_payment_id()) {
            $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
            $o_payment->load($this->_s_payment_id);
            $o_payment->calculate($this);
            $o_payment_price = $o_payment->get_price();
        }
        return $o_payment_price;
    }
    /**
     * Sets basket additional costs
     *
     * @param string $sCostName additional costs
     * @param object $oPrice    \OxidEsales\Eshop\Core\Price
     */
    public function set_cost($s_cost_name, $o_price = null)
    {
        $this->_a_costs[$s_cost_name] = $o_price;
    }
    /**
     * Executes all needed functions to calculate basket price and other needed
     * info
     *
     * @param bool $blForceUpdate set this parameter to TRUE to force basket recalculation
     *
     * @return null
     */
    public function calculate_basket($bl_force_update = false)
    {
        /*
                //would be good to perform the reset of previous calculation
                //at least you can use it for the debug
                $this->_aDiscounts = array();
                $this->_aItemDiscounts = array();
                $this->_oTotalDiscount = null;
                $this->_dDiscountedProductNettoPrice = 0;
                $this->_aDiscountedVats = array();
                $this->_oPrice = null;
                $this->_oNotDiscountedProductsPriceList = null;
                $this->_oProductsPriceList = null;
                $this->_oDiscountProductsPriceList = null;*/
        if (!$this->is_enabled()) {
            return;
        }
        if ($bl_force_update) {
            $this->on_update();
        }
        if (!($this->_bl_update_needed || $bl_force_update)) {
            return;
        }
        $this->_a_costs = [];
        //  1. saving basket to the database
        $this->save();
        //  2. remove all bundles
        $this->clear_bundles();
        //  3. generate bundle items
        $this->add_bundles();
        //  4. calculating item prices
        $this->calc_items_price();
        //  5. calculating/applying discounts
        $this->calc_basket_discount();
        //  6. calculating basket total discount
        $this->calc_basket_total_discount();
        //  7. check for vouchers
        $this->calc_voucher_discount();
        //  8. applies all discounts to pricelist
        $this->apply_discounts();
        //  9. calculating additional costs:
        //  9.1: delivery
        $this->set_cost('oxdelivery', $this->calc_delivery_cost());
        //  9.2: adding wrapping and gift card costs
        $this->set_cost('oxwrapping', $this->calc_basket_wrapping());
        $this->set_cost('oxgiftcard', $this->calc_basket_gift_card());
        //  9.3: adding payment cost
        $this->set_cost('oxpayment', $this->calc_payment_cost());
        //  10. calculate total price
        $this->calc_total_price();
        //  11. formatting discounts
        $this->format_discount();
        //  12.setting to up-to-date status
        $this->after_update();
    }
    /**
     * Notifies basket that recalculation is needed
     */
    public function on_update()
    {
        $this->_bl_update_needed = true;
    }
    /**
     * Marks basket as up-to-date
     */
    public function after_update()
    {
        $this->_bl_update_needed = false;
    }
    /**
     * Function collects summary information about basket. Usually this info
     * is used while calculating discounts or so. Data is stored in static
     * class parameter \OxidEsales\Eshop\Application\Model\Basket::$_aBasketSummary
     *
     * @return object
     */
    public function get_basket_summary()
    {
        if ($this->_bl_update_needed || $this->_a_basket_summary === null) {
            $this->_a_basket_summary = new stdclass();
            $this->_a_basket_summary->a_articles = [];
            $this->_a_basket_summary->a_categories = [];
            $this->_a_basket_summary->i_article_count = 0;
            $this->_a_basket_summary->d_article_price = 0;
            $this->_a_basket_summary->d_article_discountable_price = 0;
        }
        if (!$this->is_enabled()) {
            return $this->_a_basket_summary;
        }
        $my_config = Registry::get_config();
        /** @var \OxidEsales\EshopCommunity\Application\Model\BasketItem $oBasketItem */
        foreach ($this->_a_basket_contents as $o_basket_item) {
            if (!$o_basket_item->is_bundle() && $o_article = $o_basket_item->get_article(false)) {
                $a_cat_ids = $o_article->get_category_ids();
                //#M530 if price is not loaded for articles
                $d_price = 0;
                $d_discountable_price = 0;
                if ($o_price = $o_article->get_basket_price($o_basket_item->get_amount(), $o_basket_item->get_sel_list(), $this)) {
                    $d_price = $o_price->get_price();
                    if (!$o_article->skip_discounts()) {
                        $d_discountable_price = $d_price;
                    }
                }
                foreach ($a_cat_ids as $s_cat_id) {
                    if (!isset($this->_a_basket_summary->a_categories[$s_cat_id])) {
                        $price_object = new stdClass();
                        $price_object->d_price = 0;
                        $price_object->d_discountable_price = 0;
                        $price_object->d_amount = 0;
                        $price_object->i_count = 0;
                        $this->_a_basket_summary->a_categories[$s_cat_id] = $price_object;
                    }
                    $category_summary_price = $this->_a_basket_summary->a_categories[$s_cat_id];
                    $category_summary_price->d_price += $d_price * $o_basket_item->get_amount();
                    $category_summary_price->d_discountable_price += $d_discountable_price * $o_basket_item->get_amount();
                    $category_summary_price->d_amount += $o_basket_item->get_amount();
                    $category_summary_price->i_count++;
                }
                // variant handling
                if (($s_parent_id = $o_article->get_parent_id()) && $my_config->get_config_param('blVariantParentBuyable')) {
                    if (!isset($this->_a_basket_summary->a_articles[$s_parent_id])) {
                        $this->_a_basket_summary->a_articles[$s_parent_id] = 0;
                    }
                    $this->_a_basket_summary->a_articles[$s_parent_id] += $o_basket_item->get_amount();
                }
                if (!isset($this->_a_basket_summary->a_articles[$o_basket_item->get_product_id()])) {
                    $this->_a_basket_summary->a_articles[$o_basket_item->get_product_id()] = 0;
                }
                $this->_a_basket_summary->a_articles[$o_basket_item->get_product_id()] += $o_basket_item->get_amount();
                $this->_a_basket_summary->i_article_count += $o_basket_item->get_amount();
                $this->_a_basket_summary->d_article_price += $d_price * $o_basket_item->get_amount();
                $this->_a_basket_summary->d_article_discountable_price += $d_discountable_price * $o_basket_item->get_amount();
            }
        }
        return $this->_a_basket_summary;
    }
    /**
     * Checks and sets voucher information. Checks it's availability according
     * to few conditions: oxvoucher::checkVoucherAvailability(),
     * oxvoucher::checkUserAvailability(). After all voucher is marked as reserved
     * (oxvoucher::MarkAsReserved())
     *
     * @param string $sVoucherId voucher ID
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     */
    public function add_voucher($s_voucher_id)
    {
        // calculating price to check
        // P using prices sum which has discount, not sum of skipped discounts
        $d_price = 0;
        if ($this->_o_discount_products_price_list) {
            $d_price = $this->_o_discount_products_price_list->get_sum($this->is_calculation_mode_netto());
        }
        // trying to load voucher and apply it
        $o_voucher = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher::class);
        if (!$this->_bl_skip_vouchers_availability_checking) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            $o_db->start_transaction();
            try {
                $o_voucher->get_voucher_by_nr($s_voucher_id, $this->_a_vouchers, true);
                $o_voucher->check_voucher_availability($this->_a_vouchers, $d_price);
                $o_voucher->check_user_availability($this->get_basket_user());
                $o_voucher->mark_as_reserved();
            } catch (\Exception $exception) {
                $o_db->rollback_transaction();
                if ($exception instanceof \Oxid_Esales\Eshop\Core\Exception\Voucher_Exception) {
                    throw $exception;
                } else {
                    $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
                    $o_ex->set_message('Something went wrong, please try again');
                    $o_ex->set_voucher_nr($o_voucher->oxvouchers__oxvouchernr->value);
                    throw $o_ex;
                }
            }
            $o_db->commit_transaction();
        } else {
            $o_voucher->load($s_voucher_id);
        }
        // saving voucher info
        $this->_a_vouchers[$o_voucher->oxvouchers__oxid->value] = $o_voucher->get_simple_voucher();
        $this->on_update();
    }
    /**
     * Removes voucher from basket and unreserved it.
     *
     * @param string $sVoucherId removable voucher ID
     */
    public function remove_voucher($s_voucher_id)
    {
        // removing if it exists
        if (isset($this->_a_vouchers[$s_voucher_id])) {
            $o_voucher = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher::class);
            $o_voucher->load($s_voucher_id);
            $o_voucher->un_mark_as_reserved();
            // unset it if exists this voucher in DB or not
            unset($this->_a_vouchers[$s_voucher_id]);
            $this->on_update();
        }
    }
    /**
     * Resets user related information kept in basket object
     */
    public function reset_user_info()
    {
        $this->set_payment(null);
        $this->set_shipping(null);
    }
    /**
     * Formatting discounts
     */
    protected function format_discount()
    {
        // discount information
        // formatting discount value
        $this->a_discounts = $this->get_discounts();
        if (is_array($this->a_discounts) && count($this->a_discounts) > 0) {
            $o_lang = Registry::get_lang();
            foreach ($this->a_discounts as $o_discount) {
                $o_discount->f_discount = $o_lang->format_currency($o_discount->d_discount, $this->get_basket_currency());
            }
        }
    }
    /**
     * Populates current basket from the saved one.
     *
     * @return null
     */
    public function load()
    {
        $o_user = $this->get_basket_user();
        if (!$o_user) {
            return;
        }
        $o_basket = $o_user->get_basket('savedbasket');
        // restoring from saved history
        $a_saved_items = $o_basket->get_items();
        foreach ($a_saved_items as $o_item) {
            try {
                $o_sel_list = $o_item->get_sel_list();
                $this->add_to_basket($o_item->oxuserbasketitems__oxartid->value, $o_item->oxuserbasketitems__oxamount->value, $o_sel_list, $o_item->get_pers_params(), true);
            } catch (\Oxid_Esales\Eshop\Core\Exception\Article_Exception $o_ex) {
                // caught and ignored
            }
        }
    }
    /**
     * Saves existing basket to database
     */
    protected function save()
    {
        if ($this->is_save_to_data_base_enabled()) {
            if ($o_user = $this->get_basket_user()) {
                //first delete all contents
                //#2039
                $o_saved_basket = $o_user->get_basket('savedbasket');
                $o_saved_basket->delete();
                //then save
                /** @var \oxBasketItem $oBasketItem */
                foreach ($this->_a_basket_contents as $o_basket_item) {
                    // discount or bundled products will be added automatically if available
                    if (!$o_basket_item->is_bundle() && !$o_basket_item->is_discount_article()) {
                        $o_saved_basket->add_item_to_basket($o_basket_item->get_product_id(), $o_basket_item->get_amount(), $o_basket_item->get_sel_list(), true, $o_basket_item->get_pers_params());
                    }
                }
            }
        }
    }
    /**
     * Cleans up saved basket data. This method usually is initiated by
     * \OxidEsales\Eshop\Application\Model\Basket::deleteBasket() method which cleans up basket data when
     * user completes order.
     */
    protected function delete_saved_basket()
    {
        // deleting basket if session user available
        if ($o_user = $this->get_basket_user()) {
            $o_user->get_basket('savedbasket')->delete();
        }
        // basket exclude
        if (Registry::get_config()->get_config_param('blBasketExcludeEnabled')) {
            $this->set_basket_root_cat_id(null);
        }
    }
    /**
     * Tries to fetch user delivery country ID
     *
     * @return string
     */
    protected function find_deliv_country()
    {
        $my_config = Registry::get_config();
        $o_user = $this->get_basket_user();
        $s_delivery_country = null;
        if (!$o_user) {
            // don't calculate if not logged in unless specified otherwise
            $a_home_country = $my_config->get_config_param('aHomeCountry');
            if ($my_config->get_config_param('blCalculateDelCostIfNotLoggedIn') && is_array($a_home_country)) {
                $s_delivery_country = current($a_home_country);
            }
        } else {
            // ok, logged in
            if ($s_country_id = $my_config->get_global_parameter('delcountryid')) {
                $s_delivery_country = $s_country_id;
            } elseif ($s_address_id = Registry::get_session()->get_variable('deladrid')) {
                $o_delivery_address = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
                if ($o_delivery_address->load($s_address_id)) {
                    $s_delivery_country = $o_delivery_address->oxaddress__oxcountryid->value;
                }
            }
            // still not found ?
            if (!$s_delivery_country) {
                $s_delivery_country = $o_user->get_field_data('oxcountryid');
            }
        }
        return $s_delivery_country;
    }
    /**
     * Deletes user basket object from session
     */
    public function delete_basket()
    {
        $session = Registry::get_session();
        $this->_a_basket_contents = [];
        $session->del_basket();
        if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
            $session->get_basket_reservations()->discard_reservations();
        }
        // merging basket history
        $this->delete_saved_basket();
    }
    /**
     * Set basket payment ID
     *
     * @param string $sPaymentId payment id
     */
    public function set_payment($s_payment_id = null)
    {
        $this->_s_payment_id = $s_payment_id;
    }
    /**
     * Get basket payment, if payment id is not set, try to get it from session
     *
     * @return string
     */
    public function get_payment_id()
    {
        if (!$this->_s_payment_id) {
            $this->_s_payment_id = Registry::get_session()->get_variable('paymentid');
        }
        return $this->_s_payment_id;
    }
    /**
     * Set basket shipping set ID
     *
     * @param string $sShippingSetId delivery set id
     */
    public function set_shipping($s_shipping_set_id = null)
    {
        $this->_s_shipping_set_id = $s_shipping_set_id;
        Registry::get_session()->set_variable('sShipSet', $s_shipping_set_id);
    }
    /**
     * Set basket shipping price
     *
     * @param \OxidEsales\Eshop\Core\Price $oShippingPrice delivery costs
     */
    public function set_delivery_price($o_shipping_price = null)
    {
        $this->_o_delivery_price = $o_shipping_price;
    }
    /**
     * Get basket shipping set, if shipping set id is not set, try to get it from session
     *
     * @return string oxDeliverySet
     */
    public function get_shipping_id()
    {
        if (!$this->_s_shipping_set_id) {
            $this->_s_shipping_set_id = Registry::get_session()->get_variable('sShipSet');
        }
        $s_act_payment_id = $this->get_payment_id();
        // setting default if none is set
        if (!$this->_s_shipping_set_id && $s_act_payment_id != 'oxempty') {
            $o_user = $this->get_user();
            // choosing first preferred delivery set
            list(, $s_act_ship_set) = Registry::get(\Oxid_Esales\Eshop\Application\Model\Delivery_Set_List::class)->get_delivery_set_data(null, $o_user, $this);
            // in case nothing was found and no user set - choosing default
            $this->_s_shipping_set_id = $s_act_ship_set ? $s_act_ship_set : ($o_user ? null : 'oxidstandard');
        } elseif (!$this->is_admin() && $s_act_payment_id == 'oxempty') {
            // in case 'oxempty' is payment id - delivery set must be reset
            $this->_s_shipping_set_id = null;
        }
        return $this->_s_shipping_set_id;
    }
    /**
     * Returns array of basket oxarticle objects
     *
     * @return array
     */
    public function get_basket_articles()
    {
        $a_basket_articles = [];
        /** @var \oxBasketItem $oBasketItem */
        foreach ($this->_a_basket_contents as $s_item_key => $o_basket_item) {
            try {
                $o_product = $o_basket_item->get_article(true);
                if (Registry::get_config()->get_config_param('bl_perfLoadSelectLists')) {
                    // marking chosen select list
                    $a_sel_list = $o_basket_item->get_sel_list();
                    if (is_array($a_sel_list) && $a_selectlist = $o_product->get_select_lists($s_item_key)) {
                        reset($a_sel_list);
                        foreach ($a_sel_list as $conkey => $i_sel) {
                            $a_selectlist[$conkey][$i_sel]->selected = 1;
                        }
                        $o_product->set_selectlist($a_selectlist);
                    }
                }
            } catch (No_Article_Exception $o_ex) {
                Registry::get_utils_view()->add_error_to_display($o_ex);
                $this->remove_item($s_item_key);
                $this->calculate_basket(true);
                continue;
            } catch (\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception $o_ex) {
                Registry::get_utils_view()->add_error_to_display($o_ex);
                $this->remove_item($s_item_key);
                $this->calculate_basket(true);
                continue;
            }
            $a_basket_articles[$s_item_key] = $o_product;
        }
        return $a_basket_articles;
    }
    /**
     * Returns price list object of discounted products
     *
     * @return \OxidEsales\Eshop\Core\PriceList
     */
    public function get_discount_products_price()
    {
        return $this->_o_discount_products_price_list;
    }
    /**
     * Returns basket products price list object
     *
     * @return \OxidEsales\Eshop\Core\PriceList
     */
    public function get_products_price()
    {
        if (is_null($this->_o_products_price_list)) {
            $this->_o_products_price_list = ox_new(\Oxid_Esales\Eshop\Core\Price_List::class);
        }
        return $this->_o_products_price_list;
    }
    /**
     * Returns basket price object
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_price()
    {
        if (is_null($this->_o_price)) {
            /** @var \OxidEsales\Eshop\Core\Price $price */
            $price = ox_new(Price::class);
            $this->set_price($price);
        }
        return $this->_o_price;
    }
    /**
     * Set basket total sum price object
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice Price object
     */
    public function set_price($o_price)
    {
        $this->_o_price = $o_price;
    }
    /**
     * Returns unique order ID assigned to current basket.
     * This id is only available on last order step
     *
     * @return string
     */
    public function get_order_id()
    {
        return $this->_s_order_id;
    }
    /**
     * Basket order ID setter
     *
     * @param string $sId unique id for basket order
     */
    public function set_order_id($s_id)
    {
        $this->_s_order_id = $s_id;
    }
    /**
     * Returns array of basket costs. By passing cost identifier method will return
     * this cost if available
     *
     * @param string $sId cost id ( optional )
     *
     * @return array|\OxidEsales\Eshop\Core\Price|null
     */
    public function get_costs($s_id = null)
    {
        // if user want some specific cost - return it
        if ($s_id) {
            return isset($this->_a_costs[$s_id]) ? $this->_a_costs[$s_id] : null;
        }
        return $this->_a_costs;
    }
    /**
     * Returns array of vouchers applied to basket
     *
     * @return array
     */
    public function get_vouchers()
    {
        return $this->_a_vouchers;
    }
    /**
     * Returns number of different products stored in basket.
     *
     * @return int
     */
    public function get_products_count()
    {
        return count($this->_a_basket_contents);
    }
    /**
     * Returns count of items stored in basket.
     *
     * @return double
     */
    public function get_items_count()
    {
        $items_count = 0;
        foreach ($this->_a_basket_contents as $o_basket_item) {
            $items_count += $o_basket_item->get_amount();
        }
        return $items_count;
    }
    /**
     * Returns total basket weight.
     *
     * @return double
     */
    public function get_weight()
    {
        $weight = 0;
        foreach ($this->_a_basket_contents as $o_basket_item) {
            $weight += $o_basket_item->get_weight();
        }
        return $weight;
    }
    /**
     * Returns basket items array
     *
     * @return array
     */
    public function get_contents()
    {
        return $this->_a_basket_contents;
    }
    /**
     * Returns array of plain of formatted VATs which were calculated for basket
     *
     * @param bool $blFormatCurrency enables currency formatting
     *
     * @return array
     */
    public function get_product_vats($bl_format_currency = true)
    {
        if (!$this->_o_not_discounted_products_price_list) {
            return [];
        }
        $a_vats = $this->_o_not_discounted_products_price_list->get_vat_info($this->is_calculation_mode_netto());
        $o_utils = Registry::get_utils();
        foreach ((array) $this->_a_discounted_vats as $s_key => $d_vat) {
            if (!isset($a_vats[$s_key])) {
                $a_vats[$s_key] = 0;
            }
            // add prices of the same discounts
            $a_vats[$s_key] += $o_utils->f_round($d_vat, $this->_o_currency);
        }
        if ($bl_format_currency) {
            $o_lang = Registry::get_lang();
            foreach ($a_vats as $s_key => $d_vat) {
                $a_vats[$s_key] = $o_lang->format_currency($d_vat, $this->get_basket_currency());
            }
        }
        return $a_vats;
    }
    /**
     * Gift card message setter
     *
     * @param string $sMessage gift card message
     */
    public function set_card_message($s_message)
    {
        $this->_s_card_message = $s_message;
    }
    /**
     * Returns gift card message text
     *
     * @return string
     */
    public function get_card_message()
    {
        return $this->_s_card_message;
    }
    /**
     * Gift card ID setter
     *
     * @param string $sCardId gift card id
     */
    public function set_card_id($s_card_id)
    {
        $this->_s_card_id = $s_card_id;
    }
    /**
     * Returns applied gift card ID
     *
     * @return string
     */
    public function get_card_id()
    {
        return $this->_s_card_id;
    }
    /**
     * Returns gift card object (if available)
     *
     * @return \OxidEsales\Eshop\Application\Model\Wrapping|null
     */
    public function get_card()
    {
        $o_card = null;
        if ($s_card_id = $this->get_card_id()) {
            $o_card = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
            $o_card->load($s_card_id);
            $o_card->set_wrapping_vat($this->get_additional_services_vat_percent());
        }
        return $o_card;
    }
    /**
     * Returns total basket discount Price object
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_total_discount()
    {
        return $this->_o_total_discount;
    }
    /**
     * Returns applied discount information array
     *
     * @return array
     */
    public function get_discounts()
    {
        if ($this->get_total_discount() && $this->get_total_discount()->get_brutto_price() == 0 && count($this->_a_item_discounts) == 0) {
            return [];
        }
        return array_merge($this->_a_item_discounts, $this->_a_discounts);
    }
    /**
     * Returns basket voucher discount price object
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_voucher_discount()
    {
        if (Registry::get_config()->get_config_param('bl_showVouchers')) {
            return $this->_o_voucher_discount;
        }
        return null;
    }
    /**
     * Set basket currency
     *
     * @param stdClass $oCurrency currency object
     */
    public function set_basket_currency($o_currency)
    {
        $this->_o_currency = $o_currency;
    }
    /**
     * Basket currency getter
     *
     * @return stdClass
     */
    public function get_basket_currency()
    {
        if ($this->_o_currency === null) {
            $this->_o_currency = Registry::get_config()->get_act_shop_currency_object();
        }
        return $this->_o_currency;
    }
    /**
     * Set skip or not vouchers availability checking
     *
     * @param bool $blSkipChecking skip or not vouchers checking
     */
    public function set_skip_vouchers_checking($bl_skip_checking = null)
    {
        $this->_bl_skip_vouchers_availability_checking = $bl_skip_checking;
    }
    /**
     * Returns true if discount must be skipped for one of the products
     *
     * @return bool
     */
    public function has_skiped_discount()
    {
        return $this->_bl_skip_discounts;
    }
    /**
     * Used to set "skip discounts" status for basket
     *
     * @param bool $blSkip set true to skip discounts
     */
    public function set_skip_discounts($bl_skip)
    {
        $this->_bl_skip_discounts = $bl_skip;
    }
    /**
     * Formatted Products net price getter
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_products_net_price()
    {
        return Registry::get_lang()->format_currency($this->get_netto_sum(), $this->get_basket_currency());
    }
    /**
     * Formatted Products price getter
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_f_products_price()
    {
        return Registry::get_lang()->format_currency($this->get_brutto_sum(), $this->get_basket_currency());
    }
    /**
     * Returns VAT of delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return double
     */
    public function get_del_cost_vat_percent()
    {
        return $this->get_costs('oxdelivery')->get_vat();
    }
    /**
     * Returns formatted VAT of delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_del_cost_vat()
    {
        $d_del_vat = $this->get_costs('oxdelivery')->get_vat_value();
        // blShowVATForDelivery option will be used, only for displaying, but not calculation
        if ($d_del_vat > 0 && Registry::get_config()->get_config_param('blShowVATForDelivery')) {
            return Registry::get_lang()->format_currency($d_del_vat, $this->get_basket_currency());
        }
        return false;
    }
    /**
     * Returns formatted netto price of delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_del_cost_net()
    {
        $o_config = Registry::get_config();
        // blShowVATForDelivery option will be used, only for displaying, but not calculation
        if ($o_config->get_config_param('blShowVATForDelivery') && ($this->get_basket_user() || $o_config->get_config_param('blCalculateDelCostIfNotLoggedIn'))) {
            $d_net_price = $this->get_costs('oxdelivery')->get_netto_price();
            if ($d_net_price > 0) {
                return Registry::get_lang()->format_currency($d_net_price, $this->get_basket_currency());
            }
        }
        return false;
    }
    /**
     * Returns VAT of payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return double
     */
    public function get_pay_cost_vat_percent()
    {
        return $this->get_costs('oxpayment')->get_vat();
    }
    /**
     * Returns formatted VAT of payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_pay_cost_vat()
    {
        $d_pay_vat = $this->get_costs('oxpayment')->get_vat_value();
        // blShowVATForPayCharge option will be used, only for displaying, but not calculation
        if ($d_pay_vat > 0 && Registry::get_config()->get_config_param('blShowVATForPayCharge')) {
            return Registry::get_lang()->format_currency($d_pay_vat, $this->get_basket_currency());
        }
        return false;
    }
    /**
     * Returns formatted netto price of payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_pay_cost_net()
    {
        // blShowVATForPayCharge option will be used, only for displaying, but not calculation
        if (Registry::get_config()->get_config_param('blShowVATForPayCharge')) {
            $o_payment_cost = $this->get_costs('oxpayment');
            if ($o_payment_cost && $o_payment_cost->get_netto_price()) {
                return Registry::get_lang()->format_currency($this->get_costs('oxpayment')->get_netto_price(), $this->get_basket_currency());
            }
        }
        return false;
    }
    /**
     * Returns payment costs brutto value
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return double|bool
     */
    public function get_payment_costs()
    {
        $o_payment_cost = $this->get_costs('oxpayment');
        if ($o_payment_cost && $o_payment_cost->get_brutto_price()) {
            return $o_payment_cost->get_brutto_price();
        }
    }
    /**
     * Returns payment costs
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_payment_cost()
    {
        return $this->get_costs('oxpayment');
    }
    /**
     * Returns if exists formatted payment costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_f_payment_costs()
    {
        $o_payment_cost = $this->get_costs('oxpayment');
        if ($o_payment_cost && $o_payment_cost->get_brutto_price()) {
            return Registry::get_lang()->format_currency($o_payment_cost->get_brutto_price(), $this->get_basket_currency());
        }
        return false;
    }
    /**
     * Returns value of voucher discount
     *
     * @return double
     */
    public function get_voucher_disc_value()
    {
        if ($this->get_voucher_discount()) {
            return $this->get_voucher_discount()->get_brutto_price();
        }
        return false;
    }
    /**
     * Returns formatted voucher discount
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_f_voucher_discount_value()
    {
        if ($o_voucher_discount = $this->get_voucher_discount()) {
            if ($o_voucher_discount->get_brutto_price()) {
                return Registry::get_lang()->format_currency($o_voucher_discount->get_brutto_price(), $this->get_basket_currency());
            }
        }
        return false;
    }
    /**
     * Returns VAT of wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return double
     */
    public function get_wrapp_cost_vat_percent()
    {
        return $this->get_costs('oxwrapping')->get_vat();
    }
    /**
     * Returns VAT of gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return double
     */
    public function get_gift_card_cost_vat_percent()
    {
        return $this->get_costs('oxgiftcard')->get_vat();
    }
    /**
     * Returns formatted VAT of wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_wrapp_cost_vat()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::get_config()->get_config_param('blShowVATForWrapping')) {
            $o_price = $this->get_costs('oxwrapping');
            if ($o_price && $o_price->get_vat_value() > 0) {
                return Registry::get_lang()->format_currency($o_price->get_vat_value(), $this->get_basket_currency());
            }
        }
        return false;
    }
    /**
     * Returns formatted netto price of wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_wrapp_cost_net()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::get_config()->get_config_param('blShowVATForWrapping')) {
            $o_price = $this->get_costs('oxwrapping');
            if ($o_price && $o_price->get_netto_price() > 0) {
                return Registry::get_lang()->format_currency($o_price->get_netto_price(), $this->get_basket_currency());
            }
        }
        return false;
    }
    /**
     * Returns if exists formatted wrapping costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_f_wrapping_costs()
    {
        $o_price = $this->get_costs('oxwrapping');
        if ($o_price && $o_price->get_brutto_price()) {
            return Registry::get_lang()->format_currency($o_price->get_brutto_price(), $this->get_basket_currency());
        }
        return false;
    }
    /**
     * Returns array of wrapping costs
     *
     * @return array
     */
    public function get_wrapping_cost()
    {
        return $this->get_costs('oxwrapping');
    }
    /**
     * Returns formatted VAT of gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_gift_card_cost_vat()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::get_config()->get_config_param('blShowVATForWrapping')) {
            $o_price = $this->get_costs('oxgiftcard');
            if ($o_price && $o_price->get_vat_value() > 0) {
                return Registry::get_lang()->format_currency($o_price->get_vat_value(), $this->get_basket_currency());
            }
        }
        return false;
    }
    /**
     * Returns formatted netto price of gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_gift_card_cost_net()
    {
        // blShowVATForWrapping option will be used, only for displaying, but not calculation
        if (Registry::get_config()->get_config_param('blShowVATForWrapping')) {
            $o_price = $this->get_costs('oxgiftcard');
            if ($o_price && $o_price->get_netto_price() > 0) {
                return Registry::get_lang()->format_currency($o_price->get_netto_price(), $this->get_basket_currency());
            }
        }
        return false;
    }
    /**
     * Returns if exists formatted gift card costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_f_gift_card_costs()
    {
        $o_price = $this->get_costs('oxgiftcard');
        if ($o_price && $o_price->get_brutto_price()) {
            return Registry::get_lang()->format_currency($o_price->get_brutto_price(), $this->get_basket_currency());
        }
        return false;
    }
    /**
     * Gets gift card cost.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_gift_card_cost()
    {
        return $this->get_costs('oxgiftcard');
    }
    /**
     * Returns formatted basket total price
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_f_price()
    {
        return Registry::get_lang()->format_currency($this->get_price()->get_brutto_price(), $this->get_basket_currency());
    }
    /**
     * Returns if exists formatted delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_f_delivery_costs()
    {
        $o_price = $this->get_costs('oxdelivery');
        if ($o_price && ($this->get_basket_user() || Registry::get_config()->get_config_param('blCalculateDelCostIfNotLoggedIn'))) {
            return Registry::get_lang()->format_currency($o_price->get_brutto_price(), $this->get_basket_currency());
        }
        return false;
    }
    /**
     * Returns if exists delivery costs
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string|bool
     */
    public function get_delivery_costs()
    {
        if ($o_delivery_cost = $this->get_costs('oxdelivery')) {
            return $o_delivery_cost->get_brutto_price();
        }
        return false;
    }
    /**
     * Returns delivery costs
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_delivery_cost()
    {
        return $this->get_costs('oxdelivery');
    }
    /**
     * Sets total discount value
     *
     * @param double $dDiscount new total discount value
     */
    public function set_total_discount($d_discount)
    {
        $this->_o_total_discount = ox_new(Price::class);
        $this->_o_total_discount->set_brutto_price_mode();
        $this->_o_total_discount->add($d_discount);
    }
    /**
     * Get basket price for payment cost calculation. Returned price
     * is with applied discounts, vouchers and added delivery cost
     *
     * @return double
     */
    public function get_price_for_payment()
    {
        $d_price = $this->get_discounted_products_brutto_price();
        //#1905 not discounted products should be included in payment amount calculation
        if ($o_price_list = $this->get_not_discount_products_price()) {
            $d_price += $o_price_list->get_brutto_sum();
        }
        // adding delivery price to final price
        if (isset($this->_a_costs['oxdelivery'])) {
            $o_delivery_price = $this->_a_costs['oxdelivery'];
            $d_price += $o_delivery_price->get_brutto_price();
        }
        return $d_price;
    }
    /**
     * Returns ( current basket products sum - total discount - voucher discount )
     *
     * @return double
     */
    public function get_discounted_products_sum()
    {
        if ($o_products_price = $this->get_discount_products_price()) {
            $d_price = $o_products_price->get_sum($this->is_calculation_mode_netto());
        }
        // subtracting total discount
        if ($o_price = $this->get_total_discount()) {
            $d_price -= $o_price->get_price();
        }
        if ($o_voucher_price = $this->get_voucher_discount()) {
            $d_price -= $o_voucher_price->get_price();
        }
        return $d_price;
    }
    /**
     * Gets total discount sum.
     *
     * @return float|int
     */
    public function get_total_discount_sum()
    {
        $d_price = 0;
        // subtracting total discount
        if ($o_price = $this->get_total_discount()) {
            $d_price += $o_price->get_price();
        }
        if ($o_voucher_price = $this->get_voucher_discount()) {
            $d_price += $o_voucher_price->get_price();
        }
        return $d_price;
    }
    /**
     * Returns ( current basket products sum - total discount - voucher discount )
     *
     * @return double
     */
    public function get_discounted_products_brutto_price()
    {
        if ($o_products_price = $this->get_discount_products_price()) {
            $d_price = $o_products_price->get_brutto_sum();
        }
        // subtracting total discount
        if ($o_price = $this->get_total_discount()) {
            $d_price -= $o_price->get_brutto_price();
        }
        if ($o_voucher_price = $this->get_voucher_discount()) {
            $d_price -= $o_voucher_price->get_brutto_price();
        }
        return $d_price;
    }
    /**
     * Returns TRUE if ( current basket products sum - total discount - voucher discount ) > 0
     *
     * @return bool
     */
    public function is_below_min_order_price()
    {
        $bl_is_below_min_order_price = false;
        $s_conf_value = Registry::get_config()->get_config_param('iMinOrderPrice');
        if (is_numeric($s_conf_value) && $this->get_products_count()) {
            $d_min_order_price = Price::get_price_in_act_currency((float) $s_conf_value);
            $d_not_discounted_product_price = 0;
            if ($o_price = $this->get_not_discount_products_price()) {
                $d_not_discounted_product_price = $o_price->get_brutto_sum();
            }
            $bl_is_below_min_order_price = $d_min_order_price > $this->get_discounted_products_brutto_price() + $d_not_discounted_product_price;
        }
        return $bl_is_below_min_order_price;
    }
    /**
     * Returns stock of article in basket, including bundle article
     *
     * @param string $sArtId        article id
     * @param string $sExpiredArtId item id of updated article
     *
     * @return double
     */
    public function get_art_stock_in_basket($s_art_id, $s_expired_art_id = null)
    {
        $d_art_stock = 0;
        foreach ($this->_a_basket_contents as $s_item_key => $o_order_article) {
            if ($o_order_article && ($s_expired_art_id == null || $s_expired_art_id != $s_item_key)) {
                if ($o_order_article->get_article(true)->get_id() == $s_art_id) {
                    $d_art_stock += $o_order_article->get_amount();
                }
            }
        }
        return $d_art_stock;
    }
    /**
     * Checks if product can be added to basket
     *
     * @param string $sProductId product id
     *
     * @return bool
     */
    public function can_add_product_to_basket($s_product_id)
    {
        $bl_can_add = null;
        // if basket category is not set..
        if ($this->_s_basket_category_id === null) {
            $o_cat = null;
            // request category
            if ($o_view = Registry::get_config()->get_active_view()) {
                if ($o_cat = $o_view->get_active_category()) {
                    if (!$this->is_product_in_root_category($s_product_id, $o_cat->oxcategories__oxrootid->value)) {
                        $o_cat = null;
                    } else {
                        $bl_can_add = true;
                    }
                }
            }
            // product main category
            if (!$o_cat) {
                $o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                if ($o_product->load($s_product_id)) {
                    $o_cat = $o_product->get_category();
                }
            }
            // root category id
            if ($o_cat) {
                $this->set_basket_root_cat_id($o_cat->oxcategories__oxrootid->value);
            }
        }
        // avoiding double check..
        if ($bl_can_add === null) {
            $bl_can_add = $this->_s_basket_category_id ? $this->is_product_in_root_category($s_product_id, $this->get_basket_root_cat_id()) : true;
        }
        return $bl_can_add;
    }
    /**
     * Checks if product is in root category
     *
     * @param string $sProductId product id
     * @param string $sRootCatId root category id
     *
     * @return bool
     */
    protected function is_product_in_root_category($s_product_id, $s_root_cat_id)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_o2c_table = $table_view_name_generator->get_view_name('oxobject2category');
        $s_cat_table = $table_view_name_generator->get_view_name('oxcategories');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_parent_id = $o_db->get_one('select oxparentid from oxarticles where oxid = :oxid', ['oxid' => $s_product_id]);
        $s_product_id = $s_parent_id ? $s_parent_id : $s_product_id;
        $s_q = "select 1 from {$s_o2c_table}\n                 left join {$s_cat_table} on {$s_cat_table}.oxid = {$s_o2c_table}.oxcatnid\n                 where {$s_o2c_table}.oxobjectid = :oxobjectid and\n                       {$s_cat_table}.oxrootid = :oxrootid";
        return (bool) $o_db->get_one($s_q, ['oxobjectid' => $s_product_id, 'oxrootid' => $s_root_cat_id]);
    }
    /**
     * Set active basket root category
     *
     * @param string $sRoot Root category id
     */
    public function set_basket_root_cat_id($s_root)
    {
        $this->_s_basket_category_id = $s_root;
    }
    /**
     * Get active basket root category
     *
     * @return string
     */
    public function get_basket_root_cat_id()
    {
        return $this->_s_basket_category_id;
    }
    /**
     * Sets category change warn state
     *
     * @param bool $blShow to show warning or not
     */
    public function set_cat_change_warning_state($bl_show)
    {
        $this->_bl_show_cat_change_warning = $bl_show;
    }
    /**
     * Tells to show category change warning
     *
     * @return bool
     */
    public function show_cat_change_warning()
    {
        return $this->_bl_show_cat_change_warning;
    }
    /**
     * Returns price list object of not discounted products
     *
     * @return \OxidEsales\Eshop\Core\PriceList in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine
     *                                          plugin
     */
    public function get_not_discount_products_price()
    {
        return $this->_o_not_discounted_products_price_list;
    }
    /**
     * Is called when new basket item is successfully added.
     *
     * @param bool $blOverride marker to accumulate passed amount or renew (default false).
     */
    protected function added_new_item($bl_override)
    {
        if (!$bl_override) {
            $this->_bl_new_i_tem_added = null;
            Registry::get_session()->set_variable('blAddedNewItem', true);
        }
    }
    /**
     * Resets new basket item addition state on unserialization
     */
    public function __wakeUp()
    {
        $this->_bl_new_i_tem_added = null;
        $this->_is_calculation_mode_netto = null;
    }
    /**
     * Returns true if new product was just added to basket
     *
     * @return bool
     */
    public function is_new_item_added()
    {
        if ($this->_bl_new_i_tem_added == null) {
            $this->_bl_new_i_tem_added = (bool) Registry::get_session()->get_variable('blAddedNewItem');
            Registry::get_session()->delete_variable('blAddedNewItem');
        }
        return $this->_bl_new_i_tem_added;
    }
    /**
     * Returns true if at least one product is downloadable in basket
     *
     * @return bool
     */
    public function has_downloadable_products()
    {
        $this->_bl_downloadable_products = false;
        /** @var \OxidEsales\Eshop\Application\Model\BasketItem $oBasketItem */
        foreach ($this->_a_basket_contents as $o_basket_item) {
            if ($o_basket_item->get_article(false) && $o_basket_item->get_article(false)->is_downloadable()) {
                $this->_bl_downloadable_products = true;
                break;
            }
        }
        return $this->_bl_downloadable_products;
    }
    /**
     * Returns whether there are any articles in basket with intangible products agreement enabled.
     *
     * @return bool
     */
    public function has_articles_with_intangible_agreement()
    {
        $bl_has_articles_with_intangible_agreement = false;
        /** @var \OxidEsales\Eshop\Application\Model\BasketItem $oBasketItem */
        foreach ($this->_a_basket_contents as $o_basket_item) {
            if ($o_basket_item->get_article(false) && $o_basket_item->get_article(false)->has_intangible_agreement()) {
                $bl_has_articles_with_intangible_agreement = true;
                break;
            }
        }
        return $bl_has_articles_with_intangible_agreement;
    }
    /**
     * Returns whether there are any articles in basket with downloadable products agreement enabled.
     *
     * @return bool
     */
    public function has_articles_with_downloadable_agreement()
    {
        $bl_has_articles_with_intangible_agreement = false;
        /** @var \OxidEsales\Eshop\Application\Model\BasketItem $oBasketItem */
        foreach ($this->_a_basket_contents as $o_basket_item) {
            if ($o_basket_item->get_article(false) && $o_basket_item->get_article(false)->has_downloadable_agreement()) {
                $bl_has_articles_with_intangible_agreement = true;
                break;
            }
        }
        return $bl_has_articles_with_intangible_agreement;
    }
    /**
     * Returns min order price value
     *
     * @return float
     */
    public function get_min_order_price()
    {
        return Price::get_price_in_act_currency(Registry::get_config()->get_config_param('iMinOrderPrice'));
    }
    private function handle_no_article_exception(Basket_Item $basket_item, No_Article_Exception $exception): void
    {
        $this->remove_item($basket_item->get_basket_item_key());
        $message = sprintf(Registry::get_lang()->translate_string('ERROR_MESSAGE_ARTICLE_ARTICLE_DOES_NOT_EXIST'), $basket_item->get_title());
        Registry::get_utils_view()->add_error_to_display($message);
        Container_Facade::get(Logger_Interface::class)->warning($message, ['product id' => $exception->get_product_id(), 'shop id' => $basket_item->get_shop_id()]);
    }
}