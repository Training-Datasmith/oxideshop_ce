<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Database;

use Doctrine\DBAL\Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
use function sprintf;
class Setup_Db_Validator implements Setup_Db_Validator_Interface
{
    public function __construct(private readonly Setup_Db_Connection_Factory_Interface $database_connection_factory)
    {
    }
    public function validate(Database_Configuration $database_configuration): void
    {
        $this->validate_db_is_empty_or_not_yet_created($database_configuration);
    }
    private function validate_db_is_empty_or_not_yet_created(Database_Configuration $database_configuration): void
    {
        try {
            $connection = $this->database_connection_factory->get_database_connection($database_configuration);
            if (count($connection->create_schema_manager()->list_tables()) === 0) {
                return;
            }
        } catch (Exception) {
            return;
        }
        throw new Database_Not_Empty_Exception(sprintf('Database `%s` exists and is not empty.', $database_configuration->get_name()));
    }
}