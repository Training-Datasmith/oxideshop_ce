<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception;

class Mime_Guess_Mismatch_Exception extends Media_Validation_Exception
{
    public function __construct(private readonly string $guessed_mime, private readonly string $client_mime)
    {
        parent::__construct('Guessed MIME ' . $guessed_mime . ' does not match client MIME ' . $client_mime);
    }
    public function get_guessed_mime(): string
    {
        return $this->guessed_mime;
    }
    public function get_client_mime(): string
    {
        return $this->client_mime;
    }
}