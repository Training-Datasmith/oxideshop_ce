<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * Price list class. Deals with a list of oxPrice object.
 * The main reason why we can't just sum oxPrice objects is that they have different VAT percents.
 */
class PriceList
{
    /**
     * Array containing oxPrice objects
     *
     * @var array
     */
    protected $_aList = [];

    /**
     * Returns Brutto price sum
     *
     * @return double
     */
    public function getBruttoSum(): int|float
    {
        $dSum = 0;
        foreach ($this->_aList as $oPrice) {
            $dSum += $oPrice->getBruttoPrice();
        }

        return $dSum;
    }

    /**
     * Returns the sum of list Netto prices
     *
     * @return double
     */
    public function getNettoSum(): int|float
    {
        $dSum = 0;
        foreach ($this->_aList as $oPrice) {
            $dSum += $oPrice->getNettoPrice();
        }

        return $dSum;
    }

    /**
     * Returns the sum of list Netto prices
     *
     * @param bool $isNettoMode mode in which calculate sum, default netto
     *
     * @return double
     */
    public function getSum($isNettoMode = true): int|float
    {
        if ($isNettoMode) {
            return $this->getNettoSum();
        }
        return $this->getBruttoSum();
    }

    /**
     * Returns VAT values sum separated to different array elements depending on VAT
     *
     * @param bool $isNettoMode mode in which calculate sum, default netto
     */
    public function getVatInfo($isNettoMode = true): array
    {
        $aVatValues = [];
        $aPrices = [];
        foreach ($this->_aList as $oPrice) {
            $sKey = (string) $oPrice->getVat();
            if (!isset($aPrices[$sKey])) {
                $aPrices[$sKey]['sum'] = 0;
                $aPrices[$sKey]['vat'] = $oPrice->getVat();
            }
            $aPrices[$sKey]['sum'] += $oPrice->getPrice();
        }

        foreach ($aPrices as $sKey => $aPrice) {
            if ($isNettoMode) {
                $dPrice = $aPrice['sum'] * $aPrice['vat'] / 100;
            } else {
                $dPrice = $aPrice['sum'] * $aPrice['vat'] / (100 + $aPrice['vat']);
            }
            $aVatValues[$sKey] = $dPrice;
        }

        return $aVatValues;
    }

    /**
     * Return prices separated to different array elements depending on VAT
     */
    public function getPriceInfo(): array
    {
        $aPrices = [];
        foreach ($this->_aList as $oPrice) {
            $sVat = (string) $oPrice->getVat();
            if (!isset($aPrices[$sVat])) {
                $aPrices[$sVat] = 0;
            }
            $aPrices[$sVat] += $oPrice->getBruttoPrice();
        }

        return $aPrices;
    }

    /**
     * Iterates through applied VATs and fetches VAT for delivery.
     * If not VAT was applied - default VAT (myConfig->dDefaultVAT) will be used
     *
     * @return double
     */
    public function getMostUsedVatPercent()
    {
        $aPrices = $this->getPriceInfo();
        if (count($aPrices) == 0) {
            return;
        }

        return max(array_keys($aPrices, max($aPrices)));
    }

    /**
     * Iterates through applied VATs and calculates proportional VAT
     *
     * @return double
     */
    public function getProportionalVatPercent(): int|float
    {
        $dTotalSum = 0;

        foreach ($this->_aList as $oPrice) {
            $dTotalSum += $oPrice->getNettoPrice();
        }

        $dProportionalVat = 0;

        foreach ($this->_aList as $oPrice) {
            if ($dTotalSum > 0) {
                $dProportionalVat += $oPrice->getNettoPrice() / $dTotalSum * $oPrice->getVat();
            }
        }

        return $dProportionalVat;
    }

    /**
     * Add an oxPrice object to prices array
     *
     * @param \OxidEsales\Eshop\Core\Price $oPrice oxprice object
     */
    public function addToPriceList($oPrice): void
    {
        $this->_aList[] = $oPrice;
    }

    /**
     * Recalculate price list to one price: sum total value of prices, and calculate VAT
     */
    public function calculateToPrice()
    {
        if (count($this->_aList) == 0) {
            return;
        }

        $dNetoTotal = 0;
        $dVatTotal = 0;

        foreach ($this->_aList as $oPrice) {
            $dNetoTotal += $oPrice->getNettoPrice();
            $dVatTotal += $oPrice->getVatValue();
        }

        $oPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);

        if ($dNetoTotal) {
            $dVat = $dVatTotal * 100 / $dNetoTotal;

            $oPrice->setNettoPriceMode();
            $oPrice->setPrice($dNetoTotal);
            $oPrice->setVat($dVat);
        }

        return $oPrice;
    }

    /**
     * Return count of added oxPrices
     */
    public function getCount(): int
    {
        return count($this->_aList);
    }
}
