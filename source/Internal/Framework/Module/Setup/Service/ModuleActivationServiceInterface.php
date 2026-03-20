<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

interface Module_Activation_Service_Interface
{
    public function activate(string $module_id, int $shop_id);
    public function deactivate(string $module_id, int $shop_id);
}