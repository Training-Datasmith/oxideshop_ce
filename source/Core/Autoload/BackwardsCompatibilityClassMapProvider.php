<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Core\Autoload;

/**
 * @internal Do not make a module extension for this class.
 */
class Backwards_Compatibility_Class_Map_Provider
{
    public function get_map(): array
    {
        return include __DIR__ . DIRECTORY_SEPARATOR . 'BackwardsCompatibilityClassMap.php';
    }
}