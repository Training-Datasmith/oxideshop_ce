<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Storage;

interface File_Storage_Factory_Interface
{
    public function create(string $file_path): Array_Storage_Interface;
}