<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface Password_Service_Bridge_Interface
{
    public function hash(string $password): string;
    public function password_needs_rehash(string $password_hash): bool;
    /**
     * Verify that a given password matches a given hash
     *
     *
     */
    public function verify_password(string $password, string $password_hash): bool;
}