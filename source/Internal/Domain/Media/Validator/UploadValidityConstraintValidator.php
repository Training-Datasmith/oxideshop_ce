<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Upload_Invalid_Exception;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
readonly class Upload_Validity_Constraint_Validator implements Media_Constraint_Validator_Interface
{
    public function validate(Uploaded_File $uploaded_file): void
    {
        if (!$uploaded_file->is_valid()) {
            throw new Upload_Invalid_Exception($uploaded_file->get_error());
        }
    }
}