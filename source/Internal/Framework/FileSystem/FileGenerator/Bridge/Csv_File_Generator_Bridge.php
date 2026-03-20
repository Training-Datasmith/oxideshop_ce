<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\File_Generator\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\File_Generator\File_Generator_Interface;
class Csv_File_Generator_Bridge implements File_Generator_Bridge_Interface
{
    public function __construct(private readonly File_Generator_Interface $file_generator)
    {
    }
    public function generate(string $filename, array $data): void
    {
        $this->file_generator->generate($filename, $data);
    }
}