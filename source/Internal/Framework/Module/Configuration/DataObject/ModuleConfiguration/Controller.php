<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;

class Controller
{
    public function __construct(
        private readonly string $id,
        private readonly string $controllerClassNameSpace
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getControllerClassNameSpace(): string
    {
        return $this->controllerClassNameSpace;
    }
}
