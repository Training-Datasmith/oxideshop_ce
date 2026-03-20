<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Service;

/**
 * Manages application servers information.
 *
 * @internal Do not make a module extension for this class.
 */
interface Application_Server_Service_Interface
{
    /**
     * Returns all servers information array from configuration.
     *
     * @return \OxidEsales\Eshop\Core\DataObject\ApplicationServer[]
     */
    public function load_app_server_list();
    /**
     * Load the application server for given id.
     *
     * @param string $id The id of the application server to load.
     *
     * @return \OxidEsales\Eshop\Core\DataObject\ApplicationServer
     */
    public function load_app_server($id);
    /**
     * Removes server node information.
     *
     * @param string $serverId
     */
    public function delete_app_server_by_id($server_id);
    /**
     * Saves application server data.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $appServer
     */
    public function save_app_server($app_server);
    /**
     * Returns an array of all only active application servers.
     *
     * @return array
     */
    public function load_active_app_server_list();
    /**
     * Renews application server information when it is call in admin area and
     * if it is outdated or if it does not exist.
     */
    public function update_app_server_information_in_admin();
    /**
     * Renews application server information when it is call in frontend and
     * if it is outdated or if it does not exist.
     */
    public function update_app_server_information_in_frontend();
}