<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

/**
 * Company VAT identification number (VATIN)
 */
class CompanyVatIn implements \Stringable
{
    /**
     * Constructor
     *
     * @param string $_sCompanyVatNumber - company vat identification number.
     */
    public function __construct(
        /**
         * VAT identification number
         */
        private $_sCompanyVatNumber
    ) {
    }

    /**
     * Returns country code from number.
     */
    public function getCountryCode(): string
    {
        return (string) \OxidEsales\Eshop\Core\Str::getStr()->strtoupper(\OxidEsales\Eshop\Core\Str::getStr()->substr($this->cleanUp($this->_sCompanyVatNumber), 0, 2));
    }

    /**
     * Returns country code from number.
     */
    public function getNumbers(): string
    {
        return (string) \OxidEsales\Eshop\Core\Str::getStr()->substr($this->cleanUp($this->_sCompanyVatNumber), 2);
    }

    /**
     * Removes spaces and symbols: '-',',','.' from string
     *
     * @param string $sValue Value.
     */
    protected function cleanUp($sValue): string
    {
        return (string) \OxidEsales\Eshop\Core\Str::getStr()->preg_replace("/\s|-/", '', $sValue);
    }

    /**
     * Cast to string
     */
    public function __toString(): string
    {
        return $this->_sCompanyVatNumber;
    }
}
