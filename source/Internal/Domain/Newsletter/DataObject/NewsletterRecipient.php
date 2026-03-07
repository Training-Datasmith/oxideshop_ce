<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Newsletter\DataObject;

class NewsletterRecipient
{
    private ?string $salutation = null;

    private ?string $fistName = null;

    private ?string $lastName = null;

    private ?string $email = null;

    /**
     * @var string
     */
    private $otpInState;

    private ?string $country = null;

    private ?string $userGroups = null;

    private const OPT_IN_STATE_SUBSCRIBED = 'subscribed';
    private const OPT_IN_STATE_NOT_CONFIRMED = 'not confirmed';
    private const OPT_IN_STATE_NOT_SUBSCRIBED = 'not subscribed';

    private array $otpInStateList = [
        0 => self::OPT_IN_STATE_NOT_SUBSCRIBED,
        1 => self::OPT_IN_STATE_SUBSCRIBED,
        2 => self::OPT_IN_STATE_NOT_CONFIRMED,
    ];

    public function getSalutation(): string
    {
        return $this->salutation;
    }

    public function setSalutation(string $salutation): NewsletterRecipient
    {
        $this->salutation = $salutation;

        return $this;
    }

    public function getFistName(): string
    {
        return $this->fistName;
    }

    public function setFistName(string $fistName): NewsletterRecipient
    {
        $this->fistName = $fistName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): NewsletterRecipient
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): NewsletterRecipient
    {
        $this->email = $email;

        return $this;
    }

    public function getOtpInState(): string
    {
        return $this->otpInState;
    }

    public function setOtpInState(string $otpInState): NewsletterRecipient
    {
        $this->otpInState = self::OPT_IN_STATE_NOT_SUBSCRIBED;
        if (array_key_exists($otpInState, $this->otpInStateList)) {
            $this->otpInState = $this->otpInStateList[$otpInState];
        }

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): NewsletterRecipient
    {
        $this->country = $country;

        return $this;
    }

    public function getUserGroups(): string
    {
        return $this->userGroups;
    }

    public function setUserGroups(string $userGroups): NewsletterRecipient
    {
        $this->userGroups = $userGroups;

        return $this;
    }
}
