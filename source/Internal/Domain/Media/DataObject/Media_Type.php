<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object;

readonly class Media_Type implements \Stringable
{
    public function __construct(private string $type)
    {
        $this->validate($type);
    }
    public function __toString(): string
    {
        return $this->type;
    }
    private function validate(string $type): void
    {
        if ($type !== '' && !preg_match('#^[\w.\-]+/[\w.\-+]+$#', $type)) {
            throw new \InvalidArgumentException('Invalid MIME type format.');
        }
    }
}