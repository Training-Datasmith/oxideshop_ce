<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Service;

use Oxid_Esales\Eshop_Community\Internal\Utility\Authentication\Policy\Password_Policy_Interface;
class Password_Verification_Service implements Password_Verification_Service_Interface
{
    public function __construct(private readonly Password_Policy_Interface $password_policy)
    {
    }
    /**
     * Verify that a given password matches a given hash
     *
     *
     */
    public function verify_password(string $password, string $password_hash): bool
    {
        $this->password_policy->enforce_password_policy($password);
        return password_verify($password, $password_hash);
    }
}