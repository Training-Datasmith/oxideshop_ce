<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model\Contract;

/**
 * Article interface
 */
interface Article_Interface
{
    /**
     * Checks if stock configuration allows to buy user chosen amount $dAmount
     *
     * @param double $dAmount         buyable amount
     * @param double $dArtStockAmount stock amount
     *
     * @return mixed
     */
    public function check_for_stock($d_amount, $d_art_stock_amount = 0);
    /**
     * Returns all selectlists this article has.
     *
     * @param string $sKeyPrefix Optionall key prefix
     *
     * @return array
     */
    public function get_select_lists($s_key_prefix = null);
    /**
     * Creates, calculates and returns oxprice object for basket product.
     *
     * @param double $dAmount  Amount
     * @param string $aSelList Selection list
     * @param object $oBasket  User shopping basket object
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_basket_price($d_amount, $a_sel_list, $o_basket);
    /**
     * Checks if discount should be skipped for this article in basket. Returns true if yes.
     *
     * @return bool
     */
    public function skip_discounts();
    /**
     * Returns ID's of categories. where this article is assigned
     *
     * @param bool $blActCats   select categories if all parents are active
     * @param bool $blSkipCache Whether to skip cache
     *
     * @return array
     */
    public function get_category_ids($bl_act_cats = false, $bl_skip_cache = false);
    /**
     * Calculates and returns price of article (adds taxes and discounts).
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_price();
    /**
     * Returns product id (oxid)
     *
     * @return string
     */
    public function get_product_id();
    /**
     * Returns base article price from database
     *
     * @param double $dAmount article amount. Default is 1
     *
     * @return double
     */
    public function get_base_price($d_amount = 1);
    /**
     * Returns true if object is derived from oxorderarticle class
     *
     * @return bool
     */
    public function is_order_article();
}