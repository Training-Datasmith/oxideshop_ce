<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Controller;
interface Active_Modules_Data_Provider_Bridge_Interface
{
    /**
     * @return string[]
     */
    public function get_module_ids(): array;
    /**
     * @return string[]
     */
    public function get_module_paths(): array;
    /**
     * @return Controller[]
     */
    public function get_controllers(): array;
    /**
     * @return string[]
     */
    public function get_class_extensions(): array;
}