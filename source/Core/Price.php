<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Price calculation class. Responsible for simple price calculations. Basically contains Brutto, Netto prices and VAT values.
 */
class Price
{
    /**
     * Brutto price
     *
     * @var double
     */
    protected $_d_brutto = 0.0;
    /**
     * Netto price
     *
     * @var double
     */
    protected $_d_netto = 0.0;
    /**
     * VAT percent
     *
     * @var double
     */
    protected $_d_vat = 0.0;
    /**
     * Assigned discount array
     *
     * @var array
     */
    protected $_a_discounts;
    /**
     * Price entering mode
     * Reference to myConfig->blEnterNetPrice
     * Then true  - setPrice sets netto price and calculates brutto price
     * Then false - setPrice sets brutto price and calculates netto price
     *
     * @var boolean
     */
    protected $_bl_net_price_mode;
    /**
     * Class constructor. Gets price entering mode.
     *
     * @param double $dPrice given price
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function __construct($d_price = null)
    {
        $this->set_netto_mode(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blEnterNetPrice'));
        if (!is_null($d_price)) {
            $this->set_price($d_price);
        }
    }
    /**
     * Netto price mode setter
     *
     * @param bool $blNetto State to set price to net mode (default true).
     */
    public function set_netto_mode($bl_netto = true): void
    {
        $this->_bl_net_price_mode = $bl_netto;
    }
    /**
     * return true if mode is netto
     *
     * @return bool
     */
    public function is_netto_mode()
    {
        return $this->_bl_net_price_mode;
    }
    /**
     * Netto price mode setter
     */
    public function set_netto_price_mode(): void
    {
        $this->set_netto_mode();
    }
    /**
     * Brutto price mode setter
     */
    public function set_brutto_price_mode(): void
    {
        $this->set_netto_mode(false);
    }
    /**
     * Sets new VAT percent, and recalculates price.
     *
     * @param float $dVat vat percent
     */
    public function set_vat($d_vat): void
    {
        $this->_d_vat = (float) $d_vat;
    }
    /**
     * Sets new base VAT percent, recalculates brutto, and then netto price (in brutto mode).
     * if bruttoMode then BruttoPrice =(BruttoPrice - oldVAT% ) + newVat;
     * oldVAT = newVat;
     * finally recalculate;
     * USE ONLY TO CHANGE BASE VAT (in case when local VAT differs from user VAT),
     * USE setVat() in usual case !!!
     *
     * @param float $newVat vat percent
     */
    public function set_user_vat($new_vat): void
    {
        if (!$this->is_netto_mode() && $new_vat != $this->_d_vat) {
            $this->_d_brutto = self::Netto2Brutto(self::Brutto2Netto($this->_d_brutto, $this->_d_vat), (float) $new_vat);
        }
        $this->_d_vat = (float) $new_vat;
    }
    /**
     * Returns VAT percent
     *
     * @return double
     */
    public function get_vat()
    {
        return $this->_d_vat;
    }
    /**
     * Sets new price and VAT percent(optional). Recalculates price by
     * price entering mode
     *
     * @param double $dPrice new price
     * @param double $dVat   VAT
     */
    public function set_price($d_price, $d_vat = null): void
    {
        if (!is_null($d_vat)) {
            $this->set_vat($d_vat);
        }
        if ($this->is_netto_mode()) {
            $this->_d_netto = $d_price;
        } else {
            $this->_d_brutto = $d_price;
        }
    }
    /**
     * Returns price depending on mode brutto or netto
     *
     * @return double
     */
    public function get_price()
    {
        if ($this->is_netto_mode()) {
            return $this->get_netto_price();
        }
        return $this->get_brutto_price();
    }
    /**
     * Returns brutto price
     *
     * @return double
     */
    public function get_brutto_price()
    {
        if ($this->is_netto_mode()) {
            return $this->get_netto_price() + $this->get_vat_value();
        }
        return \Oxid_Esales\Eshop\Core\Registry::get_utils()->f_round($this->_d_brutto);
    }
    /**
     * Returns netto price
     *
     * @return double
     */
    public function get_netto_price()
    {
        if ($this->is_netto_mode()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils()->f_round($this->_d_netto);
        }
        return $this->get_brutto_price() - $this->get_vat_value();
    }
    /**
     * Returns absolute VAT value
     *
     * @return double
     */
    public function get_vat_value()
    {
        if ($this->is_netto_mode()) {
            $d_vat_value = $this->get_netto_price() * $this->get_vat() / 100;
        } else {
            $d_vat_value = $this->get_brutto_price() * $this->get_vat() / (100 + $this->get_vat());
        }
        return \Oxid_Esales\Eshop\Core\Registry::get_utils()->f_round($d_vat_value);
    }
    /**
     * Subtracts given percent from price depending  on price entering mode,
     * and recalculates price
     *
     * @param double $dValue percent to subtract from price
     */
    public function subtract_percent($d_value): void
    {
        $d_price = $this->get_price();
        $this->set_price($d_price - self::percent($d_price, $d_value));
    }
    /**
     * Adds given percent to price depending  on price entering mode,
     * and recalculates price
     *
     * @param double $dValue percent to add to price
     */
    public function add_percent($d_value): void
    {
        $this->subtract_percent(-$d_value);
    }
    /**
     * Adds another oxPrice object and recalculates current method.
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice object
     */
    public function add_price(\Oxid_Esales\Eshop\Core\Price $o_price): void
    {
        if ($this->is_netto_mode()) {
            $this->add($o_price->get_netto_price());
        } else {
            $this->add($o_price->get_brutto_price());
        }
    }
    /**
     * Adds given value to price depending  on price entering mode,
     * and recalculates price
     *
     * @param double $dValue value to add to price
     */
    public function add($d_value): void
    {
        $d_price = $this->get_price();
        $this->set_price($d_price + $d_value);
    }
    /**
     * Subtracts given value from price depending  on price entering mode,
     * and recalculates price
     *
     * @param double $dValue value to subtracts from price
     */
    public function subtract($d_value): void
    {
        $this->add(-$d_value);
    }
    /**
     * Multiplies price by given value depending on price entering mode,
     * and recalculates price
     *
     * @param double $dValue value for multiplying price
     */
    public function multiply($d_value): void
    {
        $d_price = $this->get_price();
        $this->set_price($d_price * $d_value);
    }
    /**
     * Divides price by given value depending on price entering mode,
     * and recalculates price
     *
     * @param double $dValue value for dividing price
     */
    public function divide($d_value): void
    {
        $d_price = $this->get_price();
        $this->set_price($d_price / $d_value);
    }
    /**
     * Compares this object to another oxPrice objects. Comparison is performed on brutto price.
     * Result is equal to:
     *   0 - when prices are equal.
     *   1 - when this price is larger than $oPrice.
     *  -1 - when this price is smaller than $oPrice.
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice price object
     */
    public function compare(\Oxid_Esales\Eshop\Core\Price $o_price): int
    {
        $d_brutto_price1 = $this->get_brutto_price();
        $d_brutto_price2 = $o_price->get_brutto_price();
        if ($d_brutto_price1 == $d_brutto_price2) {
            $i_res = 0;
        } elseif ($d_brutto_price1 > $d_brutto_price2) {
            $i_res = 1;
        } else {
            $i_res = -1;
        }
        return $i_res;
    }
    /**
     * Private function for percent value calculations
     *
     * @param double $dValue   value
     * @param double $dPercent percent
     */
    public static function percent($d_value, $d_percent): float
    {
        return (float) $d_value * (float) $d_percent / 100.0;
    }
    /**
     * Converts Brutto price to Netto using formula:
     * X + $dVat% = $dBrutto
     * X/100 = $dBrutto/(100+$dVAT)
     * X= ($dBrutto/(100+$dVAT))/100
     * returns X
     *
     * @param double $dBrutto brutto price
     * @param double $dVat    vat
     *
     * @return double
     */
    public static function brutto2Netto($d_brutto, $d_vat): int|float
    {
        // if VAT = -100% Return 0 because we subtract all what we have.
        // made to avoid division by zero in formula.
        if ($d_vat == -100) {
            return 0;
        }
        return (float) $d_brutto * 100.0 / (100.0 + (float) $d_vat);
    }
    /**
     * Converts Netto price to Brutto using formula:
     * X = $dNetto + $dVat%
     * returns X
     *
     * @param float $dNetto netto price
     * @param float $dVat   vat
     */
    public static function netto2Brutto($d_netto, $d_vat): float
    {
        return (float) $d_netto + self::percent($d_netto, $d_vat);
    }
    /**
     * Returns price multiplied by current currency
     *
     * @param string $dPrice price value
     */
    public static function get_price_in_act_currency($d_price): float
    {
        $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
        return (float) $d_price * $o_cur->rate;
    }
    /**
     * Sets discount to price
     *
     * @param double $dValue discount value
     * @param string $sType  discount type: abs or %
     */
    public function set_discount($d_value, $s_type): void
    {
        $this->_a_discounts[] = ['value' => $d_value, 'type' => $s_type];
    }
    /**
     * Returns assigned discounts
     *
     * @return array
     */
    public function get_discounts()
    {
        return $this->_a_discounts;
    }
    /**
     * Flush assigned discounts
     */
    protected function flush_discounts()
    {
        $this->_a_discounts = null;
    }
    /**
     * Calculates price: affects discounts
     */
    public function calculate_discount(): void
    {
        $d_price = $this->get_price();
        $a_discounts = $this->get_discounts();
        if ($a_discounts) {
            foreach ($a_discounts as $a_discount) {
                if ($a_discount['type'] == 'abs') {
                    $d_price = $d_price - $a_discount['value'];
                } else {
                    $d_price = $d_price * (100 - $a_discount['value']) / 100;
                }
            }
            if ($d_price < 0) {
                $this->set_price(0);
            } else {
                $this->set_price($d_price);
            }
            $this->flush_discounts();
        }
    }
}