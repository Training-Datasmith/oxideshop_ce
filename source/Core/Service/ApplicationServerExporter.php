<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Service;

/**
 * Prepare application servers information for export.
 *
 * @internal Do not make a module extension for this class.
 */
class Application_Server_Exporter implements \Oxid_Esales\Eshop\Core\Service\Application_Server_Exporter_Interface
{
    /**
     * The service class of application server.
     *
     * @var \OxidEsales\Eshop\Core\Service\ApplicationServerServiceInterface
     */
    private $app_server_service;
    /**
     * ApplicationServerExporter constructor.
     */
    public function __construct(\Oxid_Esales\Eshop\Core\Service\Application_Server_Service_Interface $app_server_service)
    {
        $this->app_server_service = $app_server_service;
    }
    /**
     * Return an array of active application servers.
     */
    public function export_app_server_list(): array
    {
        $active_server_collection = [];
        $active_servers = $this->app_server_service->load_active_app_server_list();
        if (is_array($active_servers) && !empty($active_servers)) {
            foreach ($active_servers as $server) {
                $active_server_collection[] = $this->convert_to_array($server);
            }
        }
        return $active_server_collection;
    }
    /**
     * Converts ApplicationServer object into array for export.
     *
     * @param \OxidEsales\Eshop\Core\DataObject\ApplicationServer $server
     */
    private function convert_to_array($server): array
    {
        return ['id' => $server->get_id(), 'ip' => $server->get_ip(), 'lastFrontendUsage' => $server->get_last_frontend_usage(), 'lastAdminUsage' => $server->get_last_admin_usage()];
    }
}