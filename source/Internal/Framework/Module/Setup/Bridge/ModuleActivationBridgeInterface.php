<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Bridge;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface Module_Activation_Bridge_Interface
{
    public function activate(string $module_id, int $shop_id);
    public function deactivate(string $module_id, int $shop_id);
    public function is_active(string $module_id, int $shop_id): bool;
}