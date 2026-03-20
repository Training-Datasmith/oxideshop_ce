<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object;

use Recursive_Array_Iterator;
class Module_Template_Extension_Chain extends Recursive_Array_Iterator
{
    public function get_template_loading_priority(string $template_name): Module_Id_Chain
    {
        return new Module_Id_Chain($this->offsetExists($template_name) ? $this->offsetGet($template_name) : []);
    }
}