<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\Eshop\Core\Price;
use OxidEsales\Eshop\Core\Registry;

class FormatPriceLogic
{
    public function formatPrice(array $params): string
    {
        $output = '';
        $inputPrice = $params['price'];
        if (!is_null($inputPrice)) {
            return $this->calculatePrice($inputPrice, $params);
        }

        return $output;
    }

    /**
     * @param mixed $inputPrice
     *
     */
    private function calculatePrice($inputPrice, array $params): string
    {
        $config = Registry::getConfig();
        $price = ($inputPrice instanceof Price) ? $inputPrice->getPrice() : (float) $inputPrice;
        $currency = isset($params['currency']) ? (object) $params['currency'] : $config->getActShopCurrencyObject();

        if (is_numeric($price)) {
            return $this->getFormattedPrice($currency, $price);
        }

        return '';
    }

    /**
     * @param object $currency active currency object
     * @param mixed  $price
     */
    private function getFormattedPrice($currency, $price): string
    {
        $output = '';
        $decimalSeparator = $currency->dec ?? ',';
        $thousandsSeparator = $currency->thousand ?? '.';
        $currencySymbol = $currency->sign ?? '';
        $currencySymbolLocation = $currency->side ?? '';
        $decimals = isset($currency->decimal) ? (int) $currency->decimal : 2;

        if ((float) $price > 0 || $currencySymbol) {
            $price = number_format($price, $decimals, $decimalSeparator, $thousandsSeparator);
            $output = (isset($currencySymbolLocation) && $currencySymbolLocation == 'Front')
                ? $currencySymbol . $price
                : $price . ' ' . $currencySymbol;
        }

        return trim($output);
    }
}
