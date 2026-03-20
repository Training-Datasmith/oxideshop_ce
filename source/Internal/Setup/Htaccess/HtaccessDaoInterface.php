<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Htaccess;

interface Htaccess_Dao_Interface
{
    public function set_rewrite_base(string $rewrite_base): void;
}