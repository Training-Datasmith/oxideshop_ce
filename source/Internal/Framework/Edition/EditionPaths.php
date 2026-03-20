<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Edition;

enum Edition_Paths : string
{
    case Community = Edition::Community->value;
    case Professional = Edition::Professional->value;
    case Enterprise = Edition::Enterprise->value;
    public function get_vendor_folder_name(): string
    {
        return 'oxid-esales';
    }
    public function get_project_folder_name(): string
    {
        return match ($this) {
            self::Community => 'oxideshop-ce',
            self::Professional => 'oxideshop-pe',
            self::Enterprise => 'oxideshop-ee',
        };
    }
    public function get_source_folder_name(): string
    {
        return match ($this) {
            self::Community => 'source',
            default => '',
        };
    }
}