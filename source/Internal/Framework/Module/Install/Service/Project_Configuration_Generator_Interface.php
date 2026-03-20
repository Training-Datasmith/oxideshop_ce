<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

interface Project_Configuration_Generator_Interface
{
    /**
     * Generates default project configuration.
     */
    public function generate(): void;
}