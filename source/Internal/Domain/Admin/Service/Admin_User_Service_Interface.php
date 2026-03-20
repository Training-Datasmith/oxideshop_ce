<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Service;

interface Admin_User_Service_Interface
{
    public function create_admin(string $email, string $password, string $rights, int $shop_id): void;
}