<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Database;

use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
interface Setup_Db_Validator_Interface
{
    /** @throws DatabaseNotEmptyException */
    public function validate(Database_Configuration $database_configuration): void;
}