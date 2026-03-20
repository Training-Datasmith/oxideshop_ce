<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\File_Generator;

class Csv_File_Generator implements File_Generator_Interface
{
    public function generate(string $filename, array $data): void
    {
        $file = fopen($filename, 'wb');
        foreach ($data as $value) {
            fputcsv(stream: $file, fields: $value, escape: '');
        }
        fclose($file);
    }
}