<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Bridge;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service\Module_Activation_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\State\Module_State_Service_Interface;
class Module_Activation_Bridge implements Module_Activation_Bridge_Interface
{
    public function __construct(private readonly Module_Activation_Service_Interface $module_activation_service, private readonly Module_State_Service_Interface $module_state_service)
    {
    }
    public function activate(string $module_id, int $shop_id): void
    {
        $this->module_activation_service->activate($module_id, $shop_id);
        Registry::get_config()->reinitialize();
    }
    public function deactivate(string $module_id, int $shop_id): void
    {
        $this->module_activation_service->deactivate($module_id, $shop_id);
        Registry::get_config()->reinitialize();
    }
    public function is_active(string $module_id, int $shop_id): bool
    {
        return $this->module_state_service->is_active($module_id, $shop_id);
    }
}