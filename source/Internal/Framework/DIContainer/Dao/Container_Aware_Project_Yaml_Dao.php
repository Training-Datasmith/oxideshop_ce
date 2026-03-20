<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Data_Object\Di_Config_Wrapper;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Event\Project_Yaml_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Component\Filesystem\Filesystem;
class Container_Aware_Project_Yaml_Dao extends Project_Yaml_Dao
{
    public function __construct(Basic_Context_Interface $context, private readonly Event_Dispatcher_Interface $event_dispatcher, Filesystem $filesystem)
    {
        parent::__construct($context, $filesystem);
    }
    public function save_project_config_file(Di_Config_Wrapper $config): void
    {
        parent::save_project_config_file($config);
        $this->event_dispatcher->dispatch(new Project_Yaml_Changed_Event());
    }
}