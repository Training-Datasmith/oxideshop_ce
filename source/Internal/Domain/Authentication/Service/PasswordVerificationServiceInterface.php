<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Service;

interface Password_Verification_Service_Interface
{
    /**
     * Verify that a given password matches a given hash
     *
     *
     */
    public function verify_password(string $password, string $password_hash): bool;
}