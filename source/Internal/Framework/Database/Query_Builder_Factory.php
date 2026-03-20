<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database;

use Doctrine\DBAL\Query\Query_Builder;
class Query_Builder_Factory implements Query_Builder_Factory_Interface
{
    public function __construct(private readonly Connection_Factory_Interface $connection_factory)
    {
    }
    public function create(): Query_Builder
    {
        $connection = $this->connection_factory->create();
        return new Query_Builder($connection);
    }
}