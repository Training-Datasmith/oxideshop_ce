<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Service;

/**
 * @internal
 */
interface Project_Yaml_Import_Service_Interface
{
    /**
     * @return void
     */
    public function add_import(string $service_dir);
    public function remove_import(string $service_dir);
    /**
     * Checks if the import files exist and if not removes them
     *
     * @return void
     */
    public function remove_non_existing_imports();
}