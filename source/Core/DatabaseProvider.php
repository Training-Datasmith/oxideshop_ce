<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Core;

use Exception;
use Oxid_Esales\Eshop\Core\Database\Adapter\Database_Interface;
use Oxid_Esales\Eshop\Core\Database\Adapter\Doctrine\Database;
use Oxid_Esales\Eshop\Core\Exception\Database_Connection_Exception;
/**
 * @deprecated since v6.4.0 (2019-09-24) use QueryBuilderFactoryInterface
 * @see \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
 */
class Database_Provider
{
    /**
     * @var ?DatabaseProvider
     */
    protected static $instance;
    /**
     * @var null|DatabaseInterface Database connection object
     */
    protected static $db;
    /**
     * @var array Database tables descriptions cache array
     */
    protected static $tbl_desc_cache = [];
    /**
     * This class is a singleton and should be instantiated with getInstance().
     */
    private function __construct()
    {
    }
    /**
     * @throws Exception
     */
    public function __clone()
    {
        throw new Exception('This object is a singleton, thou shalt not clone.');
    }
    /**
     * Returns the singleton instance of this class or of a subclass of this class.
     *
     * @return DatabaseProvider The singleton instance.
     */
    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * Return the database connection instance as a singleton.
     *
     * @param int $fetchMode The fetch mode. Default is numeric (0).
     *
     * @return DatabaseInterface
     * @throws DatabaseConnectionException Error while initiating connection to DB.
     *
     */
    public static function get_db()
    {
        if (null === static::$db) {
            $database_factory = static::get_instance();
            static::$db = $database_factory->create_database();
            /** Post connect actions will be taken only once per connection */
            $database_factory->on_post_connect();
        }
        return static::$db;
    }
    /**
     * Return the database master connection instance as a singleton.
     * In case the shop is not allowed a master/slave setup, this function
     * is simply a wrapper for DatabaseProvider::getDb.
     *
     * @param int $fetchMode The fetch mode. Default is numeric (0).
     *
     * @return DatabaseInterface
     * @throws DatabaseConnectionException Error while initiating connection to DB
     *
     */
    public static function get_master()
    {
        static::get_db()->force_master_connection();
        return static::get_db();
    }
    /**
     * @param string $tableName Name of table to invest.
     *
     * @return array
     */
    public function get_table_description($table_name)
    {
        if (!isset(self::$tbl_desc_cache[$table_name])) {
            self::$tbl_desc_cache[$table_name] = $this->fetch_table_description($table_name);
        }
        return self::$tbl_desc_cache[$table_name];
    }
    /**
     * Extracts and returns table metadata from DB.
     * This method is extended in the Enterprise Edition.
     *
     * @param string $tableName
     *
     * @return array
     */
    protected function fetch_table_description($table_name)
    {
        return static::get_db()->meta_columns($table_name);
    }
    /**
     * @return DatabaseInterface
     * @throws DatabaseConnectionException
     *
     */
    protected function create_database(): \Oxid_Esales\Eshop\Core\Database\Adapter\Doctrine\Database
    {
        $database_adapter = new Database();
        $database_adapter->connect();
        return $database_adapter;
    }
    /**
     * Post connect hook. This method is called only once per connection right after the connection to the database has
     * been established.
     */
    protected function on_post_connect()
    {
    }
}