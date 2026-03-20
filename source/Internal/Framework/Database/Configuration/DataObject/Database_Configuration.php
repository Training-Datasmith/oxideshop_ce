<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object;

use Doctrine\DBAL\Tools\Dsn_Parser;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Invalid_Database_Configuration_Exception;
class Database_Configuration
{
    private array $url_components;
    /**
     * List of URL schemes from a database URL and their mappings to driver.
     * @see \Doctrine\DBAL\DriverManager::$driverSchemeAliases
     */
    private static array $driver_scheme_aliases = [
        'db2' => 'ibm_db2',
        'mssql' => 'pdo_sqlsrv',
        'mysql' => 'pdo_mysql',
        'mysql2' => 'pdo_mysql',
        // Amazon RDS, for some weird reason
        'postgres' => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql' => 'pdo_pgsql',
        'sqlite' => 'pdo_sqlite',
        'sqlite3' => 'pdo_sqlite',
    ];
    public function __construct(private readonly string $database_url)
    {
        $this->url_components = (new Dsn_Parser(self::$driver_scheme_aliases))->parse($database_url);
        $this->validate_required_url_components();
    }
    public function get_driver(): string
    {
        return $this->url_components['driver'];
    }
    public function get_database_url(): string
    {
        return $this->database_url;
    }
    public function get_user(): string
    {
        return $this->url_components['user'] ?? '';
    }
    public function get_pass(): string
    {
        return $this->url_components['password'] ?? '';
    }
    public function get_host(): string
    {
        return $this->url_components['host'];
    }
    public function get_port(): int
    {
        return $this->url_components['port'] ?? 3306;
    }
    public function get_name(): string
    {
        return $this->url_components['dbname'] ?? '';
    }
    public function get_options(): array
    {
        return $this->url_components['driverOptions'] ?? [];
    }
    public function get_charset(): ?string
    {
        return $this->url_components['charset'] ?? null;
    }
    public function is_socket_connection(): bool
    {
        return isset($this->url_components['socket']);
    }
    public function get_socket(): string
    {
        return trim((string) $this->url_components['socket'], '()');
    }
    public function get_connection_parameters(): array
    {
        return $this->url_components;
    }
    private function validate_required_url_components(): void
    {
        if (empty($this->url_components['host']) || !isset($this->url_components['driver']) || !in_array($this->url_components['driver'], self::$driver_scheme_aliases, true)) {
            throw new Invalid_Database_Configuration_Exception('Provided database URL is not valid');
        }
    }
}