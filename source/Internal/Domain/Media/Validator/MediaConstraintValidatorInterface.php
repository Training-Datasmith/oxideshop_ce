<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator;

use Symfony\Component\Http_Foundation\File\Uploaded_File;
interface Media_Constraint_Validator_Interface
{
    public function validate(Uploaded_File $uploaded_file): void;
}