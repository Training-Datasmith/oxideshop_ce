<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting;

class Setting
{
    private ?string $name = null;
    private ?string $type = null;
    /**
     * @var mixed
     */
    private $value;
    private array $constraints = [];
    private string $group_name = '';
    private int $position_in_group = 0;
    public function get_name(): string
    {
        return $this->name;
    }
    public function set_name(string $name): Setting
    {
        $this->name = $name;
        return $this;
    }
    public function get_type(): string
    {
        if ($this->type === null) {
            return gettype($this->value);
        }
        return $this->type;
    }
    public function set_type(string $type): Setting
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
    public function set_value($value): Setting
    {
        $this->value = $value;
        return $this;
    }
    public function get_constraints(): array
    {
        return $this->constraints;
    }
    public function set_constraints(array $constraints): Setting
    {
        $this->constraints = $constraints;
        return $this;
    }
    public function get_group_name(): string
    {
        return $this->group_name;
    }
    public function set_group_name(string $group_name): Setting
    {
        $this->group_name = $group_name;
        return $this;
    }
    public function get_position_in_group(): int
    {
        return $this->position_in_group;
    }
    public function set_position_in_group(int $position_in_group): Setting
    {
        $this->position_in_group = $position_in_group;
        return $this;
    }
}