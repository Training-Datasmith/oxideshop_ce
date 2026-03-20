<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\File_Size_Too_Large_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\File_Size_Too_Small_Exception;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
class File_Size_Constraint_Validator implements Media_Constraint_Validator_Interface
{
    private readonly int $min_size_bytes;
    private readonly int $max_size_bytes;
    public function __construct(private readonly int $min_size_kb, private readonly int $max_size_kb)
    {
        $this->min_size_bytes = $min_size_kb * 1024;
        $this->max_size_bytes = $max_size_kb * 1024;
    }
    public function validate(Uploaded_File $uploaded_file): void
    {
        $filesize = $uploaded_file->get_size();
        if ($filesize < $this->min_size_bytes) {
            throw new File_Size_Too_Small_Exception($filesize, $this->min_size_kb);
        }
        if ($filesize > $this->max_size_bytes) {
            throw new File_Size_Too_Large_Exception($filesize, $this->max_size_kb);
        }
    }
}