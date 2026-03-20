<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Database;

use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Migration\Migration_Executor_Interface;
use Symfony\Component\Filesystem\Path;
class Shop_Db_Manager implements Shop_Db_Manager_Interface
{
    private Database_Configuration $database_configuration;
    public function __construct(private readonly Setup_Db_Connection_Factory_Interface $database_connection_factory, private readonly Migration_Executor_Interface $migration_executor, private readonly Views_Generator_Factory_Interface $database_views_generator_factory)
    {
    }
    public function create(Database_Configuration $database_configuration): void
    {
        $this->database_configuration = $database_configuration;
        $this->create_database();
        $this->load_sql_dumps();
        $this->migration_executor->execute();
        $this->database_views_generator_factory->create()->generate();
    }
    private function create_database(): void
    {
        $connection = $this->database_connection_factory->get_server_connection($this->database_configuration);
        $connection->execute_statement(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8 COLLATE utf8_general_ci;', $this->database_configuration->get_name()));
        $connection->close();
    }
    private function load_sql_dumps(): void
    {
        $connection = $this->database_connection_factory->get_database_connection($this->database_configuration);
        $connection->execute_statement($this->read_dump_from_file('database_schema'));
        $connection->execute_statement($this->read_dump_from_file('initial_data'));
        $connection->close();
    }
    private function read_dump_from_file(string $file): string
    {
        return file_get_contents(Path::join(__DIR__, 'sql', "{$file}.sql"));
    }
}