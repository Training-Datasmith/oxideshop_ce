<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception;

class Mime_Type_Guess_Failed_Exception extends Media_Validation_Exception
{
    public function __construct(private readonly string $path)
    {
        parent::__construct(\sprintf('Unable to guess MIME type for file "%s".', $this->path));
    }
    public function get_path(): string
    {
        return $this->path;
    }
}