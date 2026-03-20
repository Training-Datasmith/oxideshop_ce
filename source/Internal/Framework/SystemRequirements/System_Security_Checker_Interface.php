<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\System_Requirements;

interface System_Security_Checker_Interface
{
    /**
     * Checks whether system is configured to access an appropriate source of randomness for
     * Cryptographically-Secure PseudoRandom Number Generators.
     */
    public function is_cryptographically_secure(): bool;
}