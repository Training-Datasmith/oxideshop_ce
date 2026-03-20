<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Bridge;

interface Frontend_Module_Translation_File_Locator_Bridge_Interface
{
    public function locate(string $lang): array;
}