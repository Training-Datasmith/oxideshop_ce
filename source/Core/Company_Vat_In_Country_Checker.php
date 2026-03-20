<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Model\Company_Vat_In;
use Oxid_Esales\Eshop\Application\Model\Country;
/**
 * Company VAT identification number checker. Check if number belongs to the country.
 */
class Company_Vat_In_Country_Checker extends \Oxid_Esales\Eshop\Core\Company_Vat_In_Checker implements \Oxid_Esales\Eshop\Core\Contract\I_Country_Aware
{
    /**
     * Error string if country mismatch
     */
    public const ERROR_ID_NOT_VALID = 'ID_NOT_VALID';
    /**
     * Country
     *
     * @var Country
     */
    private $_o_country;
    /**
     * Country setter
     */
    public function set_country(Country $country): void
    {
        $this->_o_country = $country;
    }
    /**
     * Country getter
     *
     * @return Country
     */
    public function get_country()
    {
        return $this->_o_country;
    }
    public function validate(Company_Vat_In $vat_in)
    {
        $country = $this->get_country();
        if (is_null($country)) {
            return false;
        }
        if (!$this->has_valid_vat_prefix($country)) {
            $this->set_error('MISSING_COUNTRY_PREFIX');
            return false;
        }
        return $this->is_vat_prefix_matching($country, $vat_in);
    }
    private function has_valid_vat_prefix(Country $country): bool
    {
        return !empty($country->get_vat_identification_number_prefix());
    }
    private function is_vat_prefix_matching(Country $country, Company_Vat_In $company_vat_in): bool
    {
        $prefix = $country->get_vat_identification_number_prefix();
        $is_valid = $prefix === $company_vat_in->get_country_code();
        if (!$is_valid) {
            $this->set_error(self::ERROR_ID_NOT_VALID);
        }
        return $is_valid;
    }
}