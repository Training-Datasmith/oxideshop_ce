<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Discount list manager.
 * Organizes list of discount objects.
 */
class Discount_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Discount user id
     *
     * @var string User ID
     */
    protected $_s_user_id;
    /**
     * Forced list reload marker
     *
     * @var bool
     */
    protected $_bl_reload = true;
    /**
     * If any shops category has "skip discounts" status this parameter value will be true
     *
     * @var bool
     */
    protected $_has_skip_discount_categories;
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct('oxdiscount');
    }
    /**
     * Initializes current state discount list
     * For iterating through the list, use getArray() on the list,
     * as iterating on object itself can cause concurrency problems.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object (optional)
     *
     * @return array
     */
    protected function get_discount_list($o_user = null)
    {
        $s_user_id = $o_user ? $o_user->get_id() : '';
        if ($this->_bl_reload || $s_user_id !== $this->_s_user_id) {
            // loading list
            $this->select_string($this->get_filter_select($o_user));
            // setting list proterties
            $this->_bl_reload = false;
            // reload marker
            $this->_s_user_id = $s_user_id;
            // discount list user id
        }
        // resetting array pointer
        $this->rewind();
        return $this;
    }
    /**
     * Returns user country id for for discount selection
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser oxuser object
     *
     * @return string
     */
    public function get_country_id($o_user)
    {
        if ($o_user) {
            return $o_user->get_active_country();
        }
        return null;
    }
    /**
     * Used to force discount list reload
     */
    public function force_reload(): void
    {
        $this->_bl_reload = true;
    }
    /**
     * Creates discount list filter SQL to load current state discount list
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object
     *
     * @return string
     */
    protected function get_filter_select($o_user)
    {
        $o_base_object = $this->get_base_object();
        $s_table = $o_base_object->get_view_name();
        $s_q = 'select ' . $o_base_object->get_select_fields() . " from {$s_table} ";
        $s_q .= 'where ' . $o_base_object->get_sql_active_snippet() . ' ';
        // defining initial filter parameters
        $s_user_id = null;
        $s_group_ids = null;
        $s_country_id = $this->get_country_id($o_user);
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // checking for current session user which gives additional restrictions for user itself, users group and country
        if ($o_user) {
            // user ID
            $s_user_id = $o_user->get_id();
            // user group ids
            foreach ($o_user->get_user_groups() as $o_group) {
                if ($s_group_ids) {
                    $s_group_ids .= ', ';
                }
                $s_group_ids .= $o_db->quote($o_group->get_id());
            }
        }
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_user_table = $table_view_name_generator->get_view_name('oxuser');
        $s_group_table = $table_view_name_generator->get_view_name('oxgroups');
        $s_country_table = $table_view_name_generator->get_view_name('oxcountry');
        $s_country_sql = $s_country_id ? "EXISTS(select oxobject2discount.oxid from oxobject2discount where oxobject2discount.OXDISCOUNTID={$s_table}.OXID and oxobject2discount.oxtype='oxcountry' and oxobject2discount.OXOBJECTID=" . $o_db->quote($s_country_id) . ')' : '0';
        $s_user_sql = $s_user_id ? "EXISTS(select oxobject2discount.oxid from oxobject2discount where oxobject2discount.OXDISCOUNTID={$s_table}.OXID and oxobject2discount.oxtype='oxuser' and oxobject2discount.OXOBJECTID=" . $o_db->quote($s_user_id) . ')' : '0';
        $s_group_sql = $s_group_ids ? "EXISTS(select oxobject2discount.oxid from oxobject2discount where oxobject2discount.OXDISCOUNTID={$s_table}.OXID and oxobject2discount.oxtype='oxgroups' and oxobject2discount.OXOBJECTID in ({$s_group_ids}) )" : '0';
        $s_q .= "and (\n                if(EXISTS(select 1 from oxobject2discount, {$s_country_table} where {$s_country_table}.oxid=oxobject2discount.oxobjectid and oxobject2discount.OXDISCOUNTID={$s_table}.OXID and oxobject2discount.oxtype='oxcountry' LIMIT 1),\n                        {$s_country_sql},\n                        1) &&\n                if(EXISTS(select 1 from oxobject2discount, {$s_user_table} where {$s_user_table}.oxid=oxobject2discount.oxobjectid and oxobject2discount.OXDISCOUNTID={$s_table}.OXID and oxobject2discount.oxtype='oxuser' LIMIT 1),\n                        {$s_user_sql},\n                        1) &&\n                if(EXISTS(select 1 from oxobject2discount, {$s_group_table} where {$s_group_table}.oxid=oxobject2discount.oxobjectid and oxobject2discount.OXDISCOUNTID={$s_table}.OXID and oxobject2discount.oxtype='oxgroups' LIMIT 1),\n                        {$s_group_sql},\n                        1)\n            )";
        return $s_q . " order by {$s_table}.oxsort ";
    }
    /**
     * Returns array of discounts that can be globally (transparently) applied
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @param \OxidEsales\Eshop\Application\Model\User    $oUser    oxuser object (optional)
     *
     * @return array
     */
    public function get_article_discounts($o_article, $o_user = null)
    {
        $a_list = [];
        $a_disc_list = $this->get_discount_list($o_user)->get_array();
        foreach ($a_disc_list as $o_discount) {
            if ($o_discount->is_for_article($o_article)) {
                $a_list[$o_discount->get_id()] = $o_discount;
            }
        }
        return $a_list;
    }
    /**
     * Returns array of discounts that can be applied for individual basket item
     *
     * @param mixed                                      $oArticle article object or article id (according to needs)
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket  array of basket items containing article id, amount and price
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser    user object (optional)
     *
     * @return array
     */
    public function get_basket_item_discounts($o_article, $o_basket, $o_user = null)
    {
        $a_list = [];
        $a_disc_list = $this->get_discount_list($o_user)->get_array();
        /** @var \OxidEsales\Eshop\Application\Model\Discount $oDiscount */
        foreach ($a_disc_list as $o_discount) {
            if ($o_discount->is_for_basket_item($o_article) && $o_discount->is_for_basket_amount($o_basket)) {
                $a_list[$o_discount->get_id()] = $o_discount;
            }
        }
        return $a_list;
    }
    /**
     * Returns array of discounts that can be applied for whole basket
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser   user object (optional)
     *
     * @return array
     */
    public function get_basket_discounts($o_basket, $o_user = null)
    {
        $a_list = [];
        $a_disc_list = $this->get_discount_list($o_user)->get_array();
        /** @var \OxidEsales\Eshop\Application\Model\Discount $oDiscount */
        foreach ($a_disc_list as $o_discount) {
            if ($o_discount->is_for_basket($o_basket)) {
                $a_list[$o_discount->get_id()] = $o_discount;
            }
        }
        return $a_list;
    }
    /**
     * Returns array of bundle discounts that can be applied for whole basket
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @param \OxidEsales\Eshop\Application\Model\Basket  $oBasket  basket
     * @param \OxidEsales\Eshop\Application\Model\User    $oUser    user object (optional)
     *
     * @return array
     */
    public function get_basket_item_bundle_discounts($o_article, $o_basket, $o_user = null)
    {
        $a_list = [];
        $a_disc_list = $this->get_discount_list($o_user)->get_array();
        /** @var \OxidEsales\Eshop\Application\Model\Discount $oDiscount */
        foreach ($a_disc_list as $o_discount) {
            if ($o_discount->is_for_bundle_item($o_article, $o_basket) && $o_discount->is_for_basket_amount($o_basket)) {
                $a_list[$o_discount->get_id()] = $o_discount;
            }
        }
        return $a_list;
    }
    /**
     * Returns array of basket bundle discounts
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket oxbasket object
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser   oxuser object (optional)
     *
     * @return array
     */
    public function get_basket_bundle_discounts($o_basket, $o_user = null)
    {
        $a_list = [];
        $a_disc_list = $this->get_discount_list($o_user)->get_array();
        /** @var \OxidEsales\Eshop\Application\Model\Discount $oDiscount */
        foreach ($a_disc_list as $o_discount) {
            if ($o_discount->is_for_bundle_basket($o_basket)) {
                $a_list[$o_discount->get_id()] = $o_discount;
            }
        }
        return $a_list;
    }
    /**
     * Checks if any category has "skip discounts" status
     *
     * @return bool
     */
    public function has_skip_discount_categories()
    {
        if ($this->_has_skip_discount_categories === null || $this->_bl_reload) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_view_name = $table_view_name_generator->get_view_name('oxcategories');
            $s_q = "select 1 from {$s_view_name} where {$s_view_name}.oxactive = 1 and {$s_view_name}.oxskipdiscounts = '1' ";
            $this->_has_skip_discount_categories = (bool) \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_q);
        }
        return $this->_has_skip_discount_categories;
    }
}