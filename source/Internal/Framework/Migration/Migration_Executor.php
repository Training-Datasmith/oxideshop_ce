<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Migration;

use Oxid_Esales\Doctrine_Migration_Wrapper\Migrations;
use Oxid_Esales\Doctrine_Migration_Wrapper\Migrations_Builder;
class Migration_Executor implements Migration_Executor_Interface
{
    public function execute(): void
    {
        (new Migrations_Builder())->build()->execute(Migrations::MIGRATE_COMMAND);
    }
}