<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object;

class Oxid_Eshop_Package
{
    public function __construct(private readonly string $package_path)
    {
    }
    public function get_package_path(): string
    {
        return $this->package_path;
    }
}