<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Mime_Base_Type_Mismatch_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Mime_Guess_Mismatch_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Mime_Type_Guess_Failed_Exception;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
use Symfony\Component\Mime\Mime_Type_Guesser_Interface;
readonly class Mime_Type_Constraint_Validator implements Media_Constraint_Validator_Interface
{
    public function __construct(private string $base_type_prefix, private Mime_Type_Guesser_Interface $mime_type_guesser)
    {
    }
    public function validate(Uploaded_File $uploaded_file): void
    {
        $path = $uploaded_file->get_pathname();
        $guessed_mime_type = $this->mime_type_guesser->guess_mime_type($path) ?? throw new Mime_Type_Guess_Failed_Exception($path);
        $client_mime_type = $uploaded_file->get_client_mime_type();
        if (!str_starts_with($guessed_mime_type, $this->base_type_prefix)) {
            throw new Mime_Base_Type_Mismatch_Exception($guessed_mime_type, $this->base_type_prefix);
        }
        if ($guessed_mime_type !== $client_mime_type) {
            throw new Mime_Guess_Mismatch_Exception($guessed_mime_type, $client_mime_type);
        }
    }
}