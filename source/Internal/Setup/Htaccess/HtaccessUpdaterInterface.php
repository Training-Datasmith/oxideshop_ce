<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Htaccess;

interface Htaccess_Updater_Interface
{
    /** @param string $url */
    public function update_rewrite_base_directive(Shop_Base_Url $shop_base_url): void;
}