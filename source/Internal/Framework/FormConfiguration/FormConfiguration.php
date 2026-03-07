<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration;

class FormConfiguration implements FormConfigurationInterface
{
    private array $fieldConfigurations = [];

    public function addFieldConfiguration(FieldConfigurationInterface $fieldConfiguration): static
    {
        $this->fieldConfigurations[] = $fieldConfiguration;
        return $this;
    }

    public function getFieldConfigurations(): array
    {
        return $this->fieldConfigurations;
    }
}
