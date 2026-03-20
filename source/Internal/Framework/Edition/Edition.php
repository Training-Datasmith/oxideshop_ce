<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Edition;

enum Edition : string
{
    case Community = 'CE';
    case Professional = 'PE';
    case Enterprise = 'EE';
    public function is_community_edition(): bool
    {
        return match ($this) {
            self::Community => true,
            default => false,
        };
    }
    public function get_full_edition_name(): string
    {
        return match ($this) {
            self::Community => 'Community Edition',
            self::Professional => 'Professional Edition',
            self::Enterprise => 'Enterprise Edition',
        };
    }
}