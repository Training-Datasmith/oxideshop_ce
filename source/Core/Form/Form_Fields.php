<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Form;

/**
 * Unified way to define fields that are used in form fields.
 */
class Form_Fields
{
    public function __construct(private readonly array $updatable_fields)
    {
    }
    public function get_updatable_fields(): \ArrayIterator
    {
        return new \ArrayIterator($this->updatable_fields);
    }
}