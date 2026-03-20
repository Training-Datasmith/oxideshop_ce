<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database;

readonly class Id implements \Stringable
{
    private string $uid;
    private function __construct(?string $uid = null)
    {
        $this->uid = $uid ?? $this->generate_uid();
    }
    public static function generate(): self
    {
        return new self();
    }
    public static function from_string(string $string): self
    {
        return new self($string);
    }
    public function __toString(): string
    {
        return $this->uid;
    }
    private function generate_uid(): string
    {
        return md5(uniqid('', true) . '|' . microtime());
    }
}