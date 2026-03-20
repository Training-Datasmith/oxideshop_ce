<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Dao;

/**
 * Application server data access manager.
 *
 * @internal Do not make a module extension for this class.
 */
class Application_Server_Dao implements \Oxid_Esales\Eshop\Core\Dao\Application_Server_Dao_Interface
{
    /**
     * The name of config option for saving servers data information.
     */
    public const CONFIG_NAME_FOR_SERVER_INFO = 'aServersData_';
    /**
     * @var \OxidEsales\Eshop\Core\DataObject\ApplicationServer[]
     */
    private array $app_server = [];
    /**
     * @var \OxidEsales\Eshop\Core\Config Main shop configuration class.
     */
    private $config;
    /**
     * @var \OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface
     */
    protected $database;
    /**
     * ApplicationServerDao constructor.
     *
     * @param \OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface $database Database connection class.
     * @param \OxidEsales\Eshop\Core\Config                             $config   Main shop configuration class.
     */
    public function __construct($database, $config)
    {
        $this->database = $database;
        $this->config = $config;
    }
    /**
     * Finds all application servers.
     */
    public function find_all(): array
    {
        $app_server_list = [];
        /** @var \OxidEsales\Eshop\Core\Database\Adapter\ResultSetInterface $resultList */
        $result_list = $this->select_all_data();
        if ($result_list != false && $result_list->count() > 0) {
            $result = $result_list->get_fields();
            $server_id = $this->get_server_id_from_config($result['oxvarname']);
            $information = $this->get_value_from_config($result['oxvarvalue']);
            $app_server_list[$server_id] = $this->create_server($information);
            while ($result = $result_list->fetch_row()) {
                $server_id = $this->get_server_id_from_config($result['oxvarname']);
                $information = $this->get_value_from_config($result['oxvarvalue']);
                $app_server_list[$server_id] = $this->create_server($information);
            }
        }
        return $app_server_list;
    }
    /**
     * Deletes the entity with the given id.
     *
     * @param string $id An id of the entity to delete.
     */
    public function delete(string $id): void
    {
        unset($this->app_server[$id]);
        $query = 'DELETE FROM oxconfig WHERE oxvarname = :oxvarname and oxshopid = :oxshopid';
        $this->database->execute($query, ['oxvarname' => self::CONFIG_NAME_FOR_SERVER_INFO . $id, 'oxshopid' => $this->config->get_base_shop_id()]);
    }
    /**
     * Finds an application server by given id, null if none is found.
     *
     * @param string $id An id of the entity to find.
     *
     * @return \OxidEsales\Eshop\Core\DataObject\ApplicationServer|null
     */
    public function find_app_server($id)
    {
        if (!isset($this->app_server[$id])) {
            $server_data = $this->select_data_by_id($id);
            if ($server_data != false) {
                $app_server_properties = (array) unserialize($server_data);
            } else {
                return null;
            }
            $this->app_server[$id] = $this->create_server($app_server_properties);
        }
        return $this->app_server[$id];
    }
    /**
     * Updates or insert the given entity.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer
     */
    public function save($app_server): void
    {
        $id = $app_server->get_id();
        if ($this->find_app_server($id)) {
            $this->update($app_server);
            unset($this->app_server[$id]);
        } else {
            $this->insert($app_server);
        }
    }
    /**
     * Start a database transaction.
     */
    public function start_transaction(): void
    {
        $this->database->start_transaction();
    }
    /**
     * Commit a database transaction.
     */
    public function commit_transaction(): void
    {
        $this->database->commit_transaction();
    }
    /**
     * RollBack a database transaction.
     */
    public function rollback_transaction(): void
    {
        $this->database->rollback_transaction();
    }
    /**
     * Updates the given entity.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer
     */
    protected function update($app_server)
    {
        $query = 'UPDATE oxconfig SET oxvarvalue = :value
                  WHERE oxvarname = :oxvarname and oxshopid = :oxshopid';
        $parameter = ['value' => $this->convert_app_server_to_config_option($app_server), 'oxvarname' => self::CONFIG_NAME_FOR_SERVER_INFO . $app_server->get_id(), 'oxshopid' => $this->config->get_base_shop_id()];
        $this->database->execute($query, $parameter);
    }
    /**
     * Insert new application server entity.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer
     */
    protected function insert($app_server)
    {
        $query = "insert into oxconfig (oxid, oxshopid, oxmodule, oxvarname, oxvartype, oxvarvalue)\n                  values (:oxid, :oxshopid, '', :oxvarname, :oxvartype, :value)";
        $parameter = ['oxid' => \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->generate_uid(), 'oxshopid' => $this->config->get_base_shop_id(), 'oxvarname' => self::CONFIG_NAME_FOR_SERVER_INFO . $app_server->get_id(), 'oxvartype' => 'arr', 'value' => $this->convert_app_server_to_config_option($app_server)];
        $this->database->execute($query, $parameter);
    }
    /**
     * Returns all application server entities from database.
     *
     * @param string $id An id of the entity to find.
     *
     * @return string
     */
    private function select_data_by_id(string $id)
    {
        $query = 'SELECT oxvarvalue FROM oxconfig 
            WHERE oxvarname = :oxvarname 
              AND oxshopid = :oxshopid FOR UPDATE';
        $parameter = ['oxvarname' => self::CONFIG_NAME_FOR_SERVER_INFO . $id, 'oxshopid' => $this->config->get_base_shop_id()];
        return $this->database->get_one($query, $parameter);
    }
    /**
     * Returns all application server entities from database.
     *
     * @return \OxidEsales\Eshop\Core\Database\Adapter\ResultSetInterface
     */
    private function select_all_data()
    {
        $query = 'SELECT oxvarname, oxvarvalue
                    FROM oxconfig
                    WHERE oxvarname like :oxvarname AND oxshopid = :oxshopid';
        $parameter = ['oxvarname' => self::CONFIG_NAME_FOR_SERVER_INFO . '%', 'oxshopid' => $this->config->get_base_shop_id()];
        return $this->database->select($query, $parameter);
    }
    /**
     * Parses config option name to get the server id.
     *
     * @param string $varName The name of the config option.
     *
     * @return string The id of server.
     */
    private function get_server_id_from_config($var_name): string
    {
        $const_name_length = strlen(self::CONFIG_NAME_FOR_SERVER_INFO);
        return substr($var_name, $const_name_length);
    }
    /**
     * Unserializes config option value.
     *
     * @param string $varValue The serialized value of the config option.
     *
     * @return array The information of server.
     */
    private function get_value_from_config($var_value): array
    {
        return (array) unserialize($var_value);
    }
    /**
     * Creates ApplicationServer from given server id and data.
     *
     * @param array $data The array of server data.
     *
     * @return \OxidEsales\Eshop\Core\DataObject\ApplicationServer
     */
    protected function create_server(array $data)
    {
        /** @var \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer */
        $app_server = ox_new(\Oxid_Esales\Eshop\Core\Data_Object\Application_Server::class);
        $app_server->set_id($this->get_server_parameter($data, 'id'));
        $app_server->set_timestamp($this->get_server_parameter($data, 'timestamp'));
        $app_server->set_ip($this->get_server_parameter($data, 'ip'));
        $app_server->set_last_frontend_usage($this->get_server_parameter($data, 'lastFrontendUsage'));
        $app_server->set_last_admin_usage($this->get_server_parameter($data, 'lastAdminUsage'));
        return $app_server;
    }
    /**
     * Gets server parameter.
     *
     * @param array  $data The array of server data.
     * @param string $name The name of searched parameter.
     *
     * @return mixed
     */
    private function get_server_parameter(array $data, string $name)
    {
        return array_key_exists($name, $data) ? $data[$name] : null;
    }
    /**
     * Convert ApplicationServer object into simple array for saving into database oxconfig table.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer An application server object.
     *
     * @return array
     */
    private function convert_app_server_to_config_option($app_server): string
    {
        $server_data = ['id' => $app_server->get_id(), 'timestamp' => $app_server->get_timestamp(), 'ip' => $app_server->get_ip(), 'lastFrontendUsage' => $app_server->get_last_frontend_usage(), 'lastAdminUsage' => $app_server->get_last_admin_usage()];
        return serialize($server_data);
    }
}