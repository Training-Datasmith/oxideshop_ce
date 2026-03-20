<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object;

class Unresolved_Module_Dependencies
{
    private array $module_dependency_ids = [];
    public function get_module_ids(): array
    {
        return $this->module_dependency_ids;
    }
    public function add_module_id(string $module_id): static
    {
        $this->module_dependency_ids[] = $module_id;
        return $this;
    }
    public function has_module_dependencies(): bool
    {
        return !empty($this->module_dependency_ids);
    }
}