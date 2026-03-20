<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception;

class File_Extension_Mismatch_Exception extends Media_Validation_Exception
{
    public function __construct(private readonly string $client_extension, private readonly array $valid_extensions)
    {
        parent::__construct('Extension ' . $client_extension . ' not valid for detected MIME type');
    }
    public function get_client_extension(): string
    {
        return $this->client_extension;
    }
    /**
     * @return array<string>
     */
    public function get_valid_extensions(): array
    {
        return $this->valid_extensions;
    }
}