<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
interface Module_Configuration_Data_Mapper_Interface extends Module_Configuration_Export_Data_Mapper_Interface
{
    public function from_data(Module_Configuration $module_configuration, array $data): Module_Configuration;
}