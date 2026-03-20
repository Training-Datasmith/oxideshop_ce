<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Validator;

class File_Validator_Bridge implements File_Validator_Bridge_Interface
{
    public function __construct(private readonly File_Validator_Interface $file_validator)
    {
    }
    public function validate_image(string $file_path): bool
    {
        return $this->file_validator->validate_image($file_path);
    }
}