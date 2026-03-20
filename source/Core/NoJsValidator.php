<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Validates if there are no Javascript.
 */
class No_Js_Validator
{
    /**
     * Checks if provided config value is not vulnerable.
     *
     * @param string $configValue
     */
    public function is_valid($config_value): bool
    {
        return preg_match('/<script.*>/', $config_value) === 0;
    }
}