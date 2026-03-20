<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Config\Data_Object;

class Shop_Configuration_Setting
{
    private ?int $shop_id = null;
    private ?string $name = null;
    private ?string $type = null;
    /**
     * @var mixed
     */
    private $value;
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
    public function set_shop_id(int $shop_id): Shop_Configuration_Setting
    {
        $this->shop_id = $shop_id;
        return $this;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function set_name(string $name): Shop_Configuration_Setting
    {
        $this->name = $name;
        return $this;
    }
    public function get_type(): string
    {
        return $this->type;
    }
    public function set_type(string $type): Shop_Configuration_Setting
    {
        $this->type = $type;
        return $this;
    }
    /**
     * @return mixed
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * @param mixed $value
     */
    public function set_value($value): Shop_Configuration_Setting
    {
        $this->value = $value;
        return $this;
    }
}