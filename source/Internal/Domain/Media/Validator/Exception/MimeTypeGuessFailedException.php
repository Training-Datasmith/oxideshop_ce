<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Media\Validator\Exception;

class MimeTypeGuessFailedException extends MediaValidationException
{
    public function __construct(private readonly string $path)
    {
        parent::__construct(\sprintf('Unable to guess MIME type for file "%s".', $this->path));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
