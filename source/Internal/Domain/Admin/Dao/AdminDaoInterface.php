<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Dao;

use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Data_Object\Admin;
interface Admin_Dao_Interface
{
    public function create(Admin $admin): void;
}