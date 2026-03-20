<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Bridge;

class Admin_Theme_Bridge implements Admin_Theme_Bridge_Interface
{
    public function __construct(private readonly string $active_theme_name)
    {
    }
    public function get_active_theme(): string
    {
        return $this->active_theme_name;
    }
}