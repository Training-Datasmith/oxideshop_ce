<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception;

class Upload_Invalid_Exception extends Media_Validation_Exception
{
    public function __construct(private readonly int $error_code)
    {
        parent::__construct('Upload error with PHP code ' . $error_code);
    }
    public function get_error_code(): int
    {
        return $this->error_code;
    }
}