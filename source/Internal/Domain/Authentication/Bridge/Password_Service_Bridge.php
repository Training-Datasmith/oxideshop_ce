<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Service\Password_Verification_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Utility\Hash\Service\Password_Hash_Service_Interface;
class Password_Service_Bridge implements Password_Service_Bridge_Interface
{
    public function __construct(private readonly Password_Hash_Service_Interface $password_hash_service, private readonly Password_Verification_Service_Interface $password_verification_service)
    {
    }
    public function hash(string $password): string
    {
        return $this->password_hash_service->hash($password);
    }
    public function password_needs_rehash(string $password_hash): bool
    {
        return $this->password_hash_service->password_needs_rehash($password_hash);
    }
    /**
     * Verify that a given password matches a given hash
     *
     *
     */
    public function verify_password(string $password, string $password_hash): bool
    {
        return $this->password_verification_service->verify_password($password, $password_hash);
    }
}