<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade;

interface Modules_Data_Provider_Interface
{
    /**
     * @return string[]
     */
    public function get_module_ids(): array;
    /**
     * @return string[]
     */
    public function get_module_paths(): array;
}