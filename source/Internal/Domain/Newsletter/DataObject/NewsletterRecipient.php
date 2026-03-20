<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Data_Object;

class Newsletter_Recipient
{
    private ?string $salutation = null;
    private ?string $fist_name = null;
    private ?string $last_name = null;
    private ?string $email = null;
    /**
     * @var string
     */
    private $otp_in_state;
    private ?string $country = null;
    private ?string $user_groups = null;
    private const OPT_IN_STATE_SUBSCRIBED = 'subscribed';
    private const OPT_IN_STATE_NOT_CONFIRMED = 'not confirmed';
    private const OPT_IN_STATE_NOT_SUBSCRIBED = 'not subscribed';
    private array $otp_in_state_list = [0 => self::OPT_IN_STATE_NOT_SUBSCRIBED, 1 => self::OPT_IN_STATE_SUBSCRIBED, 2 => self::OPT_IN_STATE_NOT_CONFIRMED];
    public function get_salutation(): string
    {
        return $this->salutation;
    }
    public function set_salutation(string $salutation): Newsletter_Recipient
    {
        $this->salutation = $salutation;
        return $this;
    }
    public function get_fist_name(): string
    {
        return $this->fist_name;
    }
    public function set_fist_name(string $fist_name): Newsletter_Recipient
    {
        $this->fist_name = $fist_name;
        return $this;
    }
    public function get_last_name(): string
    {
        return $this->last_name;
    }
    public function set_last_name(string $last_name): Newsletter_Recipient
    {
        $this->last_name = $last_name;
        return $this;
    }
    public function get_email(): string
    {
        return $this->email;
    }
    public function set_email(string $email): Newsletter_Recipient
    {
        $this->email = $email;
        return $this;
    }
    public function get_otp_in_state(): string
    {
        return $this->otp_in_state;
    }
    public function set_otp_in_state(string $otp_in_state): Newsletter_Recipient
    {
        $this->otp_in_state = self::OPT_IN_STATE_NOT_SUBSCRIBED;
        if (array_key_exists($otp_in_state, $this->otp_in_state_list)) {
            $this->otp_in_state = $this->otp_in_state_list[$otp_in_state];
        }
        return $this;
    }
    public function get_country(): string
    {
        return $this->country;
    }
    public function set_country(string $country): Newsletter_Recipient
    {
        $this->country = $country;
        return $this;
    }
    public function get_user_groups(): string
    {
        return $this->user_groups;
    }
    public function set_user_groups(string $user_groups): Newsletter_Recipient
    {
        $this->user_groups = $user_groups;
        return $this;
    }
}