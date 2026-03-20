<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Model\List_Model;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Payment manager.
 * Performs payment methods, such as assigning to someone, returning value etc.
 */
class Payment extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Consider for calculation of base sum - Value of all goods in basket
     *
     * @var int
     */
    public const PAYMENT_ADDSUMRULE_ALLGOODS = 1;
    /**
     * Consider for calculation of base sum - Discounts
     *
     * @var int
     */
    public const PAYMENT_ADDSUMRULE_DISCOUNTS = 2;
    /**
     * Consider for calculation of base sum - Vouchers
     *
     * @var int
     */
    public const PAYMENT_ADDSUMRULE_VOUCHERS = 4;
    /**
     * Consider for calculation of base sum - Shipping costs
     *
     * @var int
     */
    public const PAYMENT_ADDSUMRULE_SHIPCOSTS = 8;
    /**
     * Consider for calculation of base sum - Gift Wrapping/Greeting Card
     *
     * @var int
     */
    public const PAYMENT_ADDSUMRULE_GIFTS = 16;
    /**
     * User groups object (default null).
     *
     * @var object
     */
    protected $_o_groups;
    /**
     * Countries assigned to current payment. Value from outside accessible
     * by calling \OxidEsales\Eshop\Application\Model\Payment::getCountries
     *
     * @var array
     */
    protected $_a_countries;
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxpayment';
    /**
     * current dyn values
     *
     * @var array
     */
    protected $_a_dyn_values;
    /**
     * payment error type
     *
     * @var int
     */
    protected $_i_payment_error;
    /**
     * Payment VAT config
     *
     * @var bool
     */
    protected $_bl_payment_vat_on_top = false;
    /**
     * Payment price
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_price;
    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        $this->set_payment_vat_on_top(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blPaymentVatOnTop'));
        parent::__construct();
        $this->init('oxpayments');
    }
    /**
     * Payment VAT config setter
     *
     * @param bool $blOnTop Payment vat config
     */
    public function set_payment_vat_on_top($bl_on_top): void
    {
        $this->_bl_payment_vat_on_top = $bl_on_top;
    }
    /**
     * Payment groups getter. Returns groups list
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_groups()
    {
        if ($this->_o_groups == null && $s_oxid = $this->get_id()) {
            // user groups
            $this->_o_groups = ox_new(List_Model::class, 'oxgroups');
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_view_name = $table_view_name_generator->get_view_name('oxgroups', $this->get_language());
            // performance
            $s_select = "select {$s_view_name}.* from {$s_view_name}, oxobject2group\n                        where oxobject2group.oxobjectid = :oxobjectid\n                        and oxobject2group.oxgroupsid = {$s_view_name}.oxid ";
            $this->_o_groups->select_string($s_select, ['oxobjectid' => $s_oxid]);
        }
        return $this->_o_groups;
    }
    /**
     * sets the dyn values
     *
     * @param array $aDynValues the array of dy values
     */
    public function set_dyn_values($a_dyn_values): void
    {
        $this->_a_dyn_values = $a_dyn_values;
    }
    /**
     * Sets a single dyn value
     *
     * @param mixed $oKey the key
     * @param mixed $oVal the value
     */
    public function set_dyn_value($o_key, $o_val): void
    {
        $this->_a_dyn_values[$o_key] = $o_val;
    }
    /**
     * Returns an array of dyn payment values
     *
     * @return array
     */
    public function get_dyn_values()
    {
        if (!$this->_a_dyn_values) {
            $s_raw_dyn_value = null;
            if (is_object($this->oxpayments__oxvaldesc)) {
                $s_raw_dyn_value = $this->oxpayments__oxvaldesc->get_raw_value();
            }
            $this->_a_dyn_values = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($s_raw_dyn_value);
        }
        return $this->_a_dyn_values;
    }
    /**
     * Returns additional taxes to base article price.
     *
     * @param double $dBasePrice Base article price
     *
     * @return double
     */
    public function get_payment_value($d_base_price)
    {
        if ($this->oxpayments__oxaddsumtype->value == '%') {
            $d_ret = $d_base_price * $this->oxpayments__oxaddsum->value / 100;
        } else {
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $d_ret = $this->oxpayments__oxaddsum->value * $o_cur->rate;
        }
        if ($d_ret * -1 > $d_base_price) {
            return $d_base_price;
        }
        return $d_ret;
    }
    /**
     * Returns base basket price for payment cost calculations. Price depends on
     * payment setup (payment administration)
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket oxBasket object
     *
     * @return double
     */
    public function get_base_basket_price_for_payment_cost_calc($o_basket)
    {
        $d_basket_price = 0;
        $i_rules = $this->oxpayments__oxaddsumrules->value;
        // products brutto price
        if (!$i_rules || $i_rules & self::PAYMENT_ADDSUMRULE_ALLGOODS) {
            $d_basket_price += $o_basket->get_products_price()->get_sum($o_basket->is_calculation_mode_netto());
        }
        // discounts
        if ((!$i_rules || $i_rules & self::PAYMENT_ADDSUMRULE_DISCOUNTS) && $o_costs = $o_basket->get_total_discount()) {
            $d_basket_price -= $o_costs->get_price();
        }
        // vouchers
        if (!$i_rules || $i_rules & self::PAYMENT_ADDSUMRULE_VOUCHERS) {
            $d_basket_price -= $o_basket->get_voucher_disc_value();
        }
        // delivery
        if ((!$i_rules || $i_rules & self::PAYMENT_ADDSUMRULE_SHIPCOSTS) && $o_costs = $o_basket->get_costs('oxdelivery')) {
            if ($o_basket->is_calculation_mode_netto()) {
                $d_basket_price += $o_costs->get_netto_price();
            } else {
                $d_basket_price += $o_costs->get_brutto_price();
            }
        }
        // wrapping
        if ($i_rules & self::PAYMENT_ADDSUMRULE_GIFTS && $o_costs = $o_basket->get_costs('oxwrapping')) {
            if ($o_basket->is_calculation_mode_netto()) {
                $d_basket_price += $o_costs->get_netto_price();
            } else {
                $d_basket_price += $o_costs->get_brutto_price();
            }
        }
        // gift card
        if ($i_rules & self::PAYMENT_ADDSUMRULE_GIFTS && $o_costs = $o_basket->get_costs('oxgiftcard')) {
            if ($o_basket->is_calculation_mode_netto()) {
                $d_basket_price += $o_costs->get_netto_price();
            } else {
                $d_basket_price += $o_costs->get_brutto_price();
            }
        }
        return $d_basket_price;
    }
    /**
     * Returns price object for current payment applied on basket
     *
     * @param \OxidEsales\Eshop\Application\Model\UserBasket $oBasket session basket
     */
    public function calculate($o_basket): void
    {
        //getting basket price with applied discounts and vouchers
        $d_price = $this->get_payment_value($this->get_base_basket_price_for_payment_cost_calc($o_basket));
        if (!$d_price) {
            $d_price = 0;
        }
        // calculating total price
        $o_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $o_price->set_netto_mode($this->_bl_payment_vat_on_top);
        $o_price->set_price($d_price);
        if ($d_price > 0) {
            $o_price->set_vat($o_basket->get_additional_services_vat_percent());
        }
        $this->_o_price = $o_price;
    }
    /**
     * Returns calculated price.
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_price()
    {
        return $this->_o_price;
    }
    /**
     * Returns formatted netto price.
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_f_netto_price()
    {
        if ($this->get_price()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($this->get_price()->get_netto_price());
        }
    }
    /**
     * Returns formatted brutto price.
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_f_brutto_price()
    {
        if ($this->get_price()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($this->get_price()->get_brutto_price());
        }
    }
    /**
     * Returns formatted vat value.
     *
     * @deprecated in v4.8/5.1 on 2013-10-14; for formatting use oxPrice template engine plugin
     *
     * @return string
     */
    public function get_f_price_vat()
    {
        if ($this->get_price()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($this->get_price()->get_vat_value());
        }
    }
    /**
     * Returns array of country Ids which are assigned to current payment
     *
     * @return array
     */
    public function get_countries()
    {
        if ($this->_a_countries === null) {
            $o_db = Database_Provider::get_db();
            $this->_a_countries = [];
            $s_select = 'select oxobjectid from oxobject2payment
                where oxpaymentid = :oxpaymentid and oxtype = :oxtype ';
            $rs = $o_db->get_col($s_select, ['oxpaymentid' => $this->get_id(), 'oxtype' => 'oxcountry']);
            $this->_a_countries = $rs;
        }
        return $this->_a_countries;
    }
    /**
     * Delete this object from the database, returns true on success.
     *
     * @param string $sOxId Object ID(default null)
     *
     * @return bool
     */
    public function delete($id = null)
    {
        if (parent::delete($id)) {
            $id = $id ?: $this->get_id();
            $deleted_rows = Database_Provider::get_db()->execute('delete from oxobject2payment where oxpaymentid = :oxpaymentid', ['oxpaymentid' => $id]);
            return $deleted_rows > 0;
        }
        return false;
    }
    /**
     * Function checks if loaded payment is valid to current basket
     *
     * @param array                                    $aDynValue    dynamical value (in this case oxiddebitnote is checked only)
     * @param string                                   $sShopId      id of current shop
     * @param \OxidEsales\Eshop\Application\Model\User $oUser        the current user
     * @param double                                   $dBasketPrice the current basket price (oBasket->dPrice)
     * @param string                                   $sShipSetId   the current ship set
     *
     * @return bool true if payment is valid
     */
    public function is_valid_payment($a_dyn_value, $s_shop_id, $o_user, $d_basket_price, $s_ship_set_id)
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($this->oxpayments__oxid->value == 'oxempty') {
            // inactive or blOtherCountryOrder is off
            if (!$this->oxpayments__oxactive->value || !$my_config->get_config_param('blOtherCountryOrder')) {
                $this->_i_payment_error = -2;
                return false;
            }
            if (count(\Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Delivery_Set_List::class)->get_delivery_set_list($o_user, $o_user->get_active_country()))) {
                $this->_i_payment_error = -3;
                return false;
            }
            return true;
        }
        $mx_validation_result = \Oxid_Esales\Eshop\Core\Registry::get_input_validator()->validate_payment_input_data($this->oxpayments__oxid->value, $a_dyn_value);
        if (is_integer($mx_validation_result)) {
            $this->_i_payment_error = $mx_validation_result;
            return false;
        }
        if ($mx_validation_result === false) {
            $this->_i_payment_error = 1;
            return false;
        }
        $o_cur = $my_config->get_act_shop_currency_object();
        $d_basket_price = $d_basket_price / $o_cur->rate;
        if ($s_ship_set_id) {
            $a_payment_list = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Payment_List::class)->get_payment_list($s_ship_set_id, $d_basket_price, $o_user);
            if (!array_key_exists($this->get_id(), $a_payment_list)) {
                $this->_i_payment_error = -3;
                return false;
            }
        } else {
            $this->_i_payment_error = -2;
            return false;
        }
        return true;
    }
    /**
     * Payment error number getter
     *
     * @return int
     */
    public function get_payment_error_number()
    {
        return $this->_i_payment_error;
    }
}