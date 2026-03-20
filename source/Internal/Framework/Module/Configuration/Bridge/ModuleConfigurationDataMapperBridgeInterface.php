<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
interface Module_Configuration_Data_Mapper_Bridge_Interface
{
    public function to_data(Module_Configuration $configuration): array;
}