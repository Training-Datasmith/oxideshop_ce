<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Dao\Admin_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Factory\Admin_Factory_Interface;
class Admin_User_Service implements Admin_User_Service_Interface
{
    public function __construct(private readonly Admin_Dao_Interface $admin_dao, private readonly Admin_Factory_Interface $admin_factory)
    {
    }
    /**
     * @inheritDoc
     * @throws \InvalidArgumentException
     */
    public function create_admin(string $email, string $password, string $rights, int $shop_id): void
    {
        $this->admin_dao->create($this->admin_factory->create_admin($email, $password, $rights, $shop_id));
    }
}