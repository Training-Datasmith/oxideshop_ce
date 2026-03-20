<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database;

use Doctrine\DBAL\Configuration as DbalConfiguration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver_Manager;
readonly class Connection_Factory implements Connection_Factory_Interface
{
    private Connection $connection;
    public function __construct(private Connection_Parameter_Provider_Interface $connection_parameter_provider, private iterable $middlewares)
    {
    }
    public function create(): Connection
    {
        if (!isset($this->connection)) {
            $dbal_configuration = new Dbal_Configuration();
            $dbal_configuration->set_middlewares(iterator_to_array($this->middlewares));
            $this->connection = Driver_Manager::get_connection($this->connection_parameter_provider->get_parameters(), $dbal_configuration);
        }
        return $this->connection;
    }
}