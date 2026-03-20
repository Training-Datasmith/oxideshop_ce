<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Config\Data_Object;

class Theme_Setting
{
    private int $shop_id;
    private string $name;
    private string $type;
    private mixed $value;
    private string $theme_id;
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
    public function set_shop_id(int $shop_id): self
    {
        $this->shop_id = $shop_id;
        return $this;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function set_name(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function get_type(): string
    {
        return $this->type;
    }
    public function set_type(string $type): self
    {
        $this->type = $type;
        return $this;
    }
    public function get_value(): mixed
    {
        return $this->value;
    }
    public function set_value(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }
    public function get_theme_id(): string
    {
        return $this->theme_id;
    }
    public function set_theme_id(string $theme_id): self
    {
        $this->theme_id = $theme_id;
        return $this;
    }
}