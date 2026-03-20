<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\File_Generator;

interface File_Generator_Interface
{
    public function generate(string $filename, array $data): void;
}