<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Service;

/**
 * Manages application server information.
 *
 * @internal Do not make a module extension for this class.
 */
class Application_Server_Service implements \Oxid_Esales\Eshop\Core\Service\Application_Server_Service_Interface
{
    /**
     * The Dao object for application server.
     *
     * @var \OxidEsales\Eshop\Core\Dao\ApplicationServerDaoInterface
     */
    private $app_server_dao;
    /**
     * Server data manipulation class
     *
     * @var \OxidEsales\Eshop\Core\UtilsServer
     */
    private $utils_server;
    /**
     * ApplicationServerService constructor.
     *
     * @param \OxidEsales\Eshop\Core\Dao\ApplicationServerDaoInterface $appServerDao The Dao of application server.
     * @param \OxidEsales\Eshop\Core\UtilsServer                       $utilsServer
     * @param int                                                      $currentTime  The current time - timestamp.
     */
    public function __construct(
        \Oxid_Esales\Eshop\Core\Dao\Application_Server_Dao_Interface $app_server_dao,
        $utils_server,
        /**
         * Current checking time - timestamp.
         */
        private $current_time
    )
    {
        $this->app_server_dao = $app_server_dao;
        $this->utils_server = $utils_server;
    }
    /**
     * Returns an array of all application servers.
     *
     * @return array
     */
    public function load_app_server_list()
    {
        return $this->app_server_dao->find_all();
    }
    /**
     * Load the application server for given id.
     *
     * @param string $id The id of the application server to load.
     *
     * @throws \OxidEsales\Eshop\Core\Exception\NoResultException
     *
     * @return \OxidEsales\Eshop\Core\DataObject\ApplicationServer
     */
    public function load_app_server($id)
    {
        /** @var \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer */
        $app_server = $this->app_server_dao->find_app_server($id);
        if ($app_server === null) {
            /** @var \OxidEsales\Eshop\Core\Exception\NoResultException $exception */
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\No_Result_Exception::class);
            throw $exception;
        }
        return $app_server;
    }
    /**
     * Removes server node information.
     *
     * @param string $serverId The Id of the application server to delete.
     */
    public function delete_app_server_by_id($server_id): void
    {
        $this->app_server_dao->delete($server_id);
    }
    /**
     * Saves application server data.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer
     */
    public function save_app_server($app_server): void
    {
        $this->app_server_dao->save($app_server);
    }
    /**
     * Returns an array of all only active application servers.
     */
    public function load_active_app_server_list(): array
    {
        $all_found_servers = $this->load_app_server_list();
        return $this->filter_active_app_servers($all_found_servers);
    }
    /**
     * Filter only active application servers from given list.
     *
     * @param array $appServerList The list of application servers.
     */
    protected function filter_active_app_servers($app_server_list): array
    {
        $active_server_list = [];
        /** @var \OxidEsales\Eshop\Core\DataObject\ApplicationServer $server */
        foreach ($app_server_list as $server) {
            if ($server->is_in_use($this->current_time)) {
                $active_server_list[$server->get_id()] = $server;
            }
        }
        return $active_server_list;
    }
    /**
     * Deletes all application servers, that are longer not active.
     */
    private function cleanup_app_servers(): void
    {
        $all_found_servers = $this->load_app_server_list();
        /** @var \OxidEsales\Eshop\Core\DataObject\ApplicationServer $server */
        foreach ($all_found_servers as $server) {
            if ($server->need_to_delete($this->current_time)) {
                $this->delete_app_server_by_id($server->get_id());
            }
        }
    }
    /**
     * Renews application server information when it is call in admin area and
     * if it is outdated or if it does not exist.
     */
    public function update_app_server_information_in_admin(): void
    {
        $this->update_app_server_information(true);
    }
    /**
     * Renews application server information when it is call in frontend and
     * if it is outdated or if it does not exist.
     */
    public function update_app_server_information_in_frontend(): void
    {
        $this->update_app_server_information(false);
    }
    /**
     * Renews application server information if it is outdated or if it does not exist.
     *
     * @throws \Exception
     *
     * @param bool $adminMode The status of admin mode
     */
    public function update_app_server_information($admin_mode): void
    {
        $this->app_server_dao->start_transaction();
        try {
            /** @var \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer */
            $app_server = $this->app_server_dao->find_app_server($this->utils_server->get_server_node_id());
            if ($app_server === null) {
                $this->add_new_app_server_data($admin_mode);
            } elseif ($app_server->need_to_update($this->current_time)) {
                $this->update_app_server_data($app_server, $admin_mode);
            }
        } catch (\Exception $exception) {
            $this->app_server_dao->rollback_transaction();
            throw $exception;
        }
        $this->app_server_dao->commit_transaction();
    }
    /**
     * Updates application server with the newest information.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer The application server to update.
     * @param bool                                                $adminMode The status of admin mode.
     */
    private function update_app_server_data($app_server, $admin_mode): void
    {
        $app_server->set_id($this->utils_server->get_server_node_id());
        $app_server->set_ip($this->utils_server->get_server_ip());
        $app_server->set_timestamp($this->current_time);
        if ($admin_mode) {
            $app_server->set_last_admin_usage($this->current_time);
        } else {
            $app_server->set_last_frontend_usage($this->current_time);
        }
        $this->save_app_server($app_server);
        $this->cleanup_app_servers();
    }
    /**
     * Adds new application server.
     *
     * @param bool $adminMode The status of admin mode.
     */
    private function add_new_app_server_data($admin_mode): void
    {
        /** @var \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer */
        $app_server = ox_new(\Oxid_Esales\Eshop\Core\Data_Object\Application_Server::class);
        $app_server->set_id($this->utils_server->get_server_node_id());
        $app_server->set_ip($this->utils_server->get_server_ip());
        $app_server->set_timestamp($this->current_time);
        if ($admin_mode) {
            $app_server->set_last_admin_usage($this->current_time);
        } else {
            $app_server->set_last_frontend_usage($this->current_time);
        }
        $this->save_app_server($app_server);
        $this->cleanup_app_servers();
    }
}