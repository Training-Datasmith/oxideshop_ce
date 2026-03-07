<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge;

use OxidEsales\EshopCommunity\Internal\Domain\Authentication\Exception\UnavailableSourceOfRandomnessException;

interface RandomTokenGeneratorBridgeInterface
{
    /**
     * @throws UnavailableSourceOfRandomnessException
     */
    public function getAlphanumericToken(int $length): string;

    /**
     * @throws UnavailableSourceOfRandomnessException
     */
    public function getHexToken(int $length): string;
}
