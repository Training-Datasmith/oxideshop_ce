<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Data_Object\Di_Config_Wrapper;
interface Project_Yaml_Dao_Interface
{
    public function load_di_config_file(string $path): Di_Config_Wrapper;
    public function load_project_config_file(): Di_Config_Wrapper;
    public function save_project_config_file(Di_Config_Wrapper $config);
}