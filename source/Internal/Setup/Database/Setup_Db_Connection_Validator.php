<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Database;

use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
readonly class Setup_Db_Connection_Validator implements Setup_Db_Connection_Validator_Interface
{
    public function __construct(private Setup_Db_Connection_Factory_Interface $database_connection_factory)
    {
    }
    public function validate(Database_Configuration $database_configuration): void
    {
        if ($database_configuration->is_socket_connection() || !$database_configuration->get_user() || !$database_configuration->get_pass() || !$database_configuration->get_name()) {
            throw new Unsupported_Database_Configuration_Exception("Invalid or unsupported database URL '{$database_configuration->get_database_url()}'!");
        }
        $this->can_connect_to_server($database_configuration);
    }
    private function can_connect_to_server(Database_Configuration $database_configuration): void
    {
        $connection = $this->database_connection_factory->get_server_connection($database_configuration);
        $connection->close();
    }
}