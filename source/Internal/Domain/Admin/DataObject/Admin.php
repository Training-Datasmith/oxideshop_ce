<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Data_Object;

class Admin
{
    public const MALL_ADMIN = 'malladmin';
    public function __construct(
        /** @var string */
        private $id,
        /** @var string */
        private $email,
        /** @var string */
        private $password_hash,
        /** @var string */
        private $rights,
        /** @var int */
        private $shop_id
    )
    {
    }
    public function get_id(): string
    {
        return $this->id;
    }
    public function get_email(): string
    {
        return $this->email;
    }
    public function get_password_hash(): string
    {
        return $this->password_hash;
    }
    public function get_rights(): string
    {
        return $this->rights;
    }
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
}