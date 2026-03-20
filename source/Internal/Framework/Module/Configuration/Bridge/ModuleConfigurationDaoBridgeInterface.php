<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface Module_Configuration_Dao_Bridge_Interface
{
    public function get(string $module_id): Module_Configuration;
    public function save(Module_Configuration $module_configuration);
}