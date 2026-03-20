<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Str;
/**
 * SEPA (Single Euro Payments Area) BIC validation class
 */
class Sepa_Bic_Validator
{
    /**
     * Business identifier code validation
     *
     * Structure
     *  - 4 letters: Institution Code or bank code.
     *  - 2 letters: ISO 3166-1 alpha-2 country code
     *  - 2 letters or digits: location code
     *  - 3 letters or digits: branch code, optional
     *
     * @param string $sBIC code to check
     */
    public function is_valid($s_bic): bool
    {
        $s_bic = strtoupper(trim($s_bic));
        return (bool) Str::get_str()->preg_match('(^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$)', $s_bic);
    }
}