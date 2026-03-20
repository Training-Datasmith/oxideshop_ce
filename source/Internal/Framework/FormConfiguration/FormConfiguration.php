<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration;

class Form_Configuration implements Form_Configuration_Interface
{
    private array $field_configurations = [];
    public function add_field_configuration(Field_Configuration_Interface $field_configuration): static
    {
        $this->field_configurations[] = $field_configuration;
        return $this;
    }
    public function get_field_configurations(): array
    {
        return $this->field_configurations;
    }
}