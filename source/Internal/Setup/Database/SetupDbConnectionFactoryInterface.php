<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Database;

use Doctrine\DBAL\Connection;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
interface Setup_Db_Connection_Factory_Interface
{
    public function get_server_connection(Database_Configuration $database_configuration): Connection;
    public function get_database_connection(Database_Configuration $database_configuration): Connection;
}