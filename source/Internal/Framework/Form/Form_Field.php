<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form;

class Form_Field implements Form_Field_Interface
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $value;
    /**
     * @var string
     */
    private $label;
    /**
     * @var bool
     */
    private $is_required;
    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * @param string $name
     */
    public function set_name($name): static
    {
        $this->name = $name;
        return $this;
    }
    /**
     * @return string
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * @param string $value
     */
    public function set_value($value): static
    {
        $this->value = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }
    /**
     * @param string $label
     */
    public function set_label($label): static
    {
        $this->label = $label;
        return $this;
    }
    /**
     * @return bool
     */
    public function is_required()
    {
        return $this->is_required;
    }
    /**
     * @param bool $isRequired
     */
    public function set_is_required($is_required): static
    {
        $this->is_required = $is_required;
        return $this;
    }
}