<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\File_Extension_Mismatch_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Mime_Type_Guess_Failed_Exception;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
use Symfony\Component\Mime\Mime_Types_Interface;
readonly class File_Extension_Constraint_Validator implements Media_Constraint_Validator_Interface
{
    public function __construct(private Mime_Types_Interface $mime_type_guesser)
    {
    }
    public function validate(Uploaded_File $uploaded_file): void
    {
        $client_extension = strtolower($uploaded_file->get_client_original_extension());
        $path = $uploaded_file->get_pathname();
        $guessed_mime_type = $this->mime_type_guesser->guess_mime_type($path) ?? throw new Mime_Type_Guess_Failed_Exception($path);
        $valid_extensions = array_map(strtolower(...), $this->mime_type_guesser->get_extensions($guessed_mime_type));
        if (empty($valid_extensions) || !in_array($client_extension, $valid_extensions, true)) {
            throw new File_Extension_Mismatch_Exception($client_extension, $valid_extensions);
        }
    }
}