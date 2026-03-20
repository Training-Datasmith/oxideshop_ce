<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

/**
 * @deprecated use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface
 */
interface Module_Setting_Bridge_Interface
{
    /**
     * @param bool|int|string|array $value
     */
    public function save(string $name, $value, string $module_id): void;
    public function get(string $name, string $module_id);
}