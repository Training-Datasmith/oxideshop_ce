<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception;

class Mime_Base_Type_Mismatch_Exception extends Media_Validation_Exception
{
    public function __construct(private readonly string $guessed_mime, private readonly string $required_base_prefix)
    {
        parent::__construct('MIME type ' . $guessed_mime . ' does not match required base ' . $required_base_prefix);
    }
    public function get_guessed_mime(): string
    {
        return $this->guessed_mime;
    }
    public function get_required_base_prefix(): string
    {
        return $this->required_base_prefix;
    }
}