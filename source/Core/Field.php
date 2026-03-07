<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

#[\AllowDynamicProperties]
class Field implements \Stringable
{
    /**
     * escaping functionality type: expected value is escaped text.
     */
    public const T_TEXT = 1;

    /**
     * escaping functionality type: expected value is not escaped (raw) text.
     */
    public const T_RAW = 2;

    public function __construct($value = null, $type = self::T_TEXT)
    {
        $this->rawValue = $value;
        if ((int)$type === self::T_RAW) {
            $this->value = $value;
        }
    }

    public function __isset(string $name): bool
    {
        return $this->{$name} !== null;
    }

    /**
     * @return mixed|string|null
     */
    public function __get(string $name): mixed
    {
        if (!($name === 'value' || $name === 'rawValue')) {
            return null;
        }
        if ($name === 'value') {
            $this->value = $this->rawValue;
            unset($this->rawValue);
        }
        return $this->value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }

    /**
     * @param $value
     * @param $type
     */
    public function setValue($value = null, $type = self::T_TEXT): void
    {
        unset($this->rawValue, $this->value);
        $this->initValue($value, $type);
    }

    public function getRawValue(): mixed
    {
        return $this->rawValue ?? $this->value;
    }

    /**
     * @param $value
     * @param $type
     */
    protected function initValue($value = null, $type = self::T_TEXT): void
    {
        if ((int)$type === self::T_TEXT) {
            $this->rawValue = $value;
        } else {
            $this->value = $value;
        }
    }
}
