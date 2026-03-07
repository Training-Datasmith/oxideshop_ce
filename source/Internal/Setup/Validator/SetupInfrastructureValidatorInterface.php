<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Setup\Validator;

use OxidEsales\EshopCommunity\Internal\Setup\Parameters\SetupParameters;

interface SetupInfrastructureValidatorInterface
{
    public function validate(SetupParameters $setupParameters): void;
}
