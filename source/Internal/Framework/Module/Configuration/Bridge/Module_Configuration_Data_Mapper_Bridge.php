<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\{Module_Configuration_Export_Data_Mapper_Interface};
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
readonly class Module_Configuration_Data_Mapper_Bridge implements Module_Configuration_Data_Mapper_Bridge_Interface
{
    public function __construct(private Module_Configuration_Export_Data_Mapper_Interface $module_configuration_data_mapper)
    {
    }
    public function to_data(Module_Configuration $configuration): array
    {
        return $this->module_configuration_data_mapper->to_data($configuration);
    }
}