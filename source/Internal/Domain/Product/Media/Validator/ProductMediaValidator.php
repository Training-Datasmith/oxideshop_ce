<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Validator;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Media_Constraint_Validator_Interface;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
class Product_Media_Validator implements Media_Constraint_Validator_Interface
{
    public function __construct(private readonly iterable $validators)
    {
    }
    public function validate(Uploaded_File $uploaded_file): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($uploaded_file);
        }
    }
}