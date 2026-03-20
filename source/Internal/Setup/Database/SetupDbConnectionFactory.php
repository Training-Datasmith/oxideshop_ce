<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver_Manager;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
class Setup_Db_Connection_Factory implements Setup_Db_Connection_Factory_Interface
{
    public function get_server_connection(Database_Configuration $database_configuration): Connection
    {
        $connection_parameters = $database_configuration->get_connection_parameters();
        unset($connection_parameters['dbname']);
        return $this->get_connection($connection_parameters);
    }
    public function get_database_connection(Database_Configuration $database_configuration): Connection
    {
        return $this->get_connection($database_configuration->get_connection_parameters());
    }
    private function get_connection(array $parameters): Connection
    {
        $connection = Driver_Manager::get_connection($parameters);
        $connection->get_server_version();
        return $connection;
    }
}