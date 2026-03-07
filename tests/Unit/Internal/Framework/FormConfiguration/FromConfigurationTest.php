<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\FormConfiguration;

use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FieldConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfiguration;
use PHPUnit\Framework\TestCase;

final class FromConfigurationTest extends TestCase
{
    public function testAddFieldConfiguration(): void
    {
        $fieldConfiguration = new FieldConfiguration();
        $fieldConfiguration->setName('testField');

        $formConfiguration = new FormConfiguration();
        $formConfiguration->addFieldConfiguration($fieldConfiguration);

        $this->assertSame(
            [$fieldConfiguration],
            $formConfiguration->getFieldConfigurations()
        );
    }
}
