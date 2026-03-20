<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Validator;

interface File_Validator_Interface
{
    /**
     * @throws ImageValidationException
     */
    public function validate_image(string $file_path): bool;
}