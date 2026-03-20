<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Htaccess;

interface Htaccess_Dao_Factory_Interface
{
    public function create_root_htaccess_dao(): Htaccess_Dao_Interface;
}