<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object;

use Recursive_Array_Iterator;
class Module_Dependencies extends Recursive_Array_Iterator
{
    public function get_required_module_ids(): array
    {
        return $this->offsetExists('modules') ? $this->offsetGet('modules') : [];
    }
    public function is_required_module(string $module_id): bool
    {
        return in_array($module_id, $this->get_required_module_ids(), true);
    }
}