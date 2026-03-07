<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Form;

/**
 * Unified way to define fields that are used in form fields.
 */
class FormFields
{
    public function __construct(private readonly array $updatableFields)
    {
    }

    public function getUpdatableFields(): \ArrayIterator
    {
        return new \ArrayIterator($this->updatableFields);
    }
}
