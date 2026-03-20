<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\System_Requirements;

use Exception;
use function random_bytes;
class System_Security_Checker implements System_Security_Checker_Interface
{
    /** @inheritdoc */
    public function is_cryptographically_secure(): bool
    {
        try {
            random_bytes(1);
            return true;
        } catch (Exception) {
            return false;
        }
    }
}