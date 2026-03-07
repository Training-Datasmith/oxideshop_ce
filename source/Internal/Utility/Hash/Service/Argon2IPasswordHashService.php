<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Utility\Hash\Service;

use OxidEsales\EshopCommunity\Internal\Utility\Authentication\Policy\PasswordPolicyInterface;
use OxidEsales\EshopCommunity\Internal\Utility\Hash\Exception\PasswordHashException;

/**
 * Hashes with the ARGON2I algorithm
 */
class Argon2IPasswordHashService implements PasswordHashServiceInterface
{
    public function __construct(
        private readonly PasswordPolicyInterface $passwordPolicy,
        private readonly int $memoryCost,
        private readonly int $timeCost,
        private readonly int $threads
    ) {
    }

    /**
     * Creates a password hash
     *
     *
     * @throws PasswordHashException
     *
     */
    public function hash(string $password): string
    {
        $this->passwordPolicy->enforcePasswordPolicy($password);

        $hash = password_hash(
            $password,
            PASSWORD_ARGON2I,
            $this->getOptions()
        );

        if ($hash === false) {
            throw new PasswordHashException(
                'The password could not have been hashed.'
            );
        }

        return $hash;
    }

    public function passwordNeedsRehash(string $passwordHash): bool
    {
        return password_needs_rehash($passwordHash, PASSWORD_ARGON2I, $this->getOptions());
    }

    private function getOptions(): array
    {
        return [
            'memory_cost' => $this->memoryCost,
            'time_cost' => $this->timeCost,
            'threads' => $this->threads,
        ];
    }
}
