<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Company VAT identification number (VATIN)
 */
class Company_Vat_In implements \Stringable
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
        private $_s_company_vat_number
    )
    {
    }
    /**
     * Returns country code from number.
     */
    public function get_country_code(): string
    {
        return (string) \Oxid_Esales\Eshop\Core\Str::get_str()->strtoupper(\Oxid_Esales\Eshop\Core\Str::get_str()->substr($this->clean_up($this->_s_company_vat_number), 0, 2));
    }
    /**
     * Returns country code from number.
     */
    public function get_numbers(): string
    {
        return (string) \Oxid_Esales\Eshop\Core\Str::get_str()->substr($this->clean_up($this->_s_company_vat_number), 2);
    }
    /**
     * Removes spaces and symbols: '-',',','.' from string
     *
     * @param string $sValue Value.
     */
    protected function clean_up($s_value): string
    {
        return (string) \Oxid_Esales\Eshop\Core\Str::get_str()->preg_replace("/\\s|-/", '', $s_value);
    }
    /**
     * Cast to string
     */
    public function __toString(): string
    {
        return $this->_s_company_vat_number;
    }
}