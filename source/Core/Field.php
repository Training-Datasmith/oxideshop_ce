<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

#[\Allow_Dynamic_Properties]
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
        $this->raw_value = $value;
        if ((int) $type === self::T_RAW) {
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
            $this->value = $this->raw_value;
            unset($this->raw_value);
        }
        return $this->value;
    }
    public function __toString(): string
    {
        return (string) $this->value;
    }
    /**
     * @param $value
     * @param $type
     */
    public function set_value($value = null, $type = self::T_TEXT): void
    {
        unset($this->raw_value, $this->value);
        $this->init_value($value, $type);
    }
    public function get_raw_value(): mixed
    {
        return $this->raw_value ?? $this->value;
    }
    /**
     * @param $value
     * @param $type
     */
    protected function init_value($value = null, $type = self::T_TEXT): void
    {
        if ((int) $type === self::T_TEXT) {
            $this->raw_value = $value;
        } else {
            $this->value = $value;
        }
    }
}