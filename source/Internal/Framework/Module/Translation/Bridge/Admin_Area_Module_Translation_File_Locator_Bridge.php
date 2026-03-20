<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\{Locator\Admin_Area_Module_Translation_File_Locator_Interface};
class Admin_Area_Module_Translation_File_Locator_Bridge implements Admin_Area_Module_Translation_File_Locator_Bridge_Interface
{
    public function __construct(private readonly Admin_Area_Module_Translation_File_Locator_Interface $module_translation_file_locator)
    {
    }
    public function locate(string $lang): array
    {
        return $this->module_translation_file_locator->locate($lang);
    }
}