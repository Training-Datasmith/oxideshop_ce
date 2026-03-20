<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Factory;

use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Data_Object\Admin;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Invalid_Email_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Invalid_Rights_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Invalid_Shop_Exception;
interface Admin_Factory_Interface
{
    /**
     * @throws InvalidEmailException
     * @throws InvalidShopException
     * @throws InvalidRightsException
     */
    public function create_admin(string $email, string $password, string $rights, int $shop_id): Admin;
}