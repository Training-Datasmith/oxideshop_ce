<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

/**
 * @internal
 */
interface Module_Services_Importer_Interface
{
    public function add_import(string $service_dir, int $shop_id): void;
    public function remove_import(string $service_dir, int $shop_id): void;
}