<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Price list class. Deals with a list of oxPrice object.
 * The main reason why we can't just sum oxPrice objects is that they have different VAT percents.
 */
class Price_List
{
    /**
     * Array containing oxPrice objects
     *
     * @var array
     */
    protected $_a_list = [];
    /**
     * Returns Brutto price sum
     *
     * @return double
     */
    public function get_brutto_sum(): int|float
    {
        $d_sum = 0;
        foreach ($this->_a_list as $o_price) {
            $d_sum += $o_price->get_brutto_price();
        }
        return $d_sum;
    }
    /**
     * Returns the sum of list Netto prices
     *
     * @return double
     */
    public function get_netto_sum(): int|float
    {
        $d_sum = 0;
        foreach ($this->_a_list as $o_price) {
            $d_sum += $o_price->get_netto_price();
        }
        return $d_sum;
    }
    /**
     * Returns the sum of list Netto prices
     *
     * @param bool $isNettoMode mode in which calculate sum, default netto
     *
     * @return double
     */
    public function get_sum($is_netto_mode = true): int|float
    {
        if ($is_netto_mode) {
            return $this->get_netto_sum();
        }
        return $this->get_brutto_sum();
    }
    /**
     * Returns VAT values sum separated to different array elements depending on VAT
     *
     * @param bool $isNettoMode mode in which calculate sum, default netto
     */
    public function get_vat_info($is_netto_mode = true): array
    {
        $a_vat_values = [];
        $a_prices = [];
        foreach ($this->_a_list as $o_price) {
            $s_key = (string) $o_price->get_vat();
            if (!isset($a_prices[$s_key])) {
                $a_prices[$s_key]['sum'] = 0;
                $a_prices[$s_key]['vat'] = $o_price->get_vat();
            }
            $a_prices[$s_key]['sum'] += $o_price->get_price();
        }
        foreach ($a_prices as $s_key => $a_price) {
            if ($is_netto_mode) {
                $d_price = $a_price['sum'] * $a_price['vat'] / 100;
            } else {
                $d_price = $a_price['sum'] * $a_price['vat'] / (100 + $a_price['vat']);
            }
            $a_vat_values[$s_key] = $d_price;
        }
        return $a_vat_values;
    }
    /**
     * Return prices separated to different array elements depending on VAT
     */
    public function get_price_info(): array
    {
        $a_prices = [];
        foreach ($this->_a_list as $o_price) {
            $s_vat = (string) $o_price->get_vat();
            if (!isset($a_prices[$s_vat])) {
                $a_prices[$s_vat] = 0;
            }
            $a_prices[$s_vat] += $o_price->get_brutto_price();
        }
        return $a_prices;
    }
    /**
     * Iterates through applied VATs and fetches VAT for delivery.
     * If not VAT was applied - default VAT (myConfig->dDefaultVAT) will be used
     *
     * @return double
     */
    public function get_most_used_vat_percent()
    {
        $a_prices = $this->get_price_info();
        if (count($a_prices) == 0) {
            return;
        }
        return max(array_keys($a_prices, max($a_prices)));
    }
    /**
     * Iterates through applied VATs and calculates proportional VAT
     *
     * @return double
     */
    public function get_proportional_vat_percent(): int|float
    {
        $d_total_sum = 0;
        foreach ($this->_a_list as $o_price) {
            $d_total_sum += $o_price->get_netto_price();
        }
        $d_proportional_vat = 0;
        foreach ($this->_a_list as $o_price) {
            if ($d_total_sum > 0) {
                $d_proportional_vat += $o_price->get_netto_price() / $d_total_sum * $o_price->get_vat();
            }
        }
        return $d_proportional_vat;
    }
    /**
     * Add an oxPrice object to prices array
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice oxprice object
     */
    public function add_to_price_list($o_price): void
    {
        $this->_a_list[] = $o_price;
    }
    /**
     * Recalculate price list to one price: sum total value of prices, and calculate VAT
     */
    public function calculate_to_price()
    {
        if (count($this->_a_list) == 0) {
            return;
        }
        $d_neto_total = 0;
        $d_vat_total = 0;
        foreach ($this->_a_list as $o_price) {
            $d_neto_total += $o_price->get_netto_price();
            $d_vat_total += $o_price->get_vat_value();
        }
        $o_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        if ($d_neto_total) {
            $d_vat = $d_vat_total * 100 / $d_neto_total;
            $o_price->set_netto_price_mode();
            $o_price->set_price($d_neto_total);
            $o_price->set_vat($d_vat);
        }
        return $o_price;
    }
    /**
     * Return count of added oxPrices
     */
    public function get_count(): int
    {
        return count($this->_a_list);
    }
}