<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Event;
class Events_Data_Mapper implements Module_Configuration_Data_Mapper_Interface
{
    public const MAPPING_KEY = 'events';
    public function to_data(Module_Configuration $configuration): array
    {
        $data = [];
        if ($configuration->has_events()) {
            $data[self::MAPPING_KEY] = $this->get_events($configuration);
        }
        return $data;
    }
    public function from_data(Module_Configuration $module_configuration, array $data): Module_Configuration
    {
        if (isset($data[self::MAPPING_KEY])) {
            $this->set_events($module_configuration, $data[self::MAPPING_KEY]);
        }
        return $module_configuration;
    }
    private function set_events(Module_Configuration $module_configuration, array $event): void
    {
        foreach ($event as $action => $method) {
            $module_configuration->add_event(new Event($action, $method));
        }
    }
    private function get_events(Module_Configuration $configuration): array
    {
        $events = [];
        if ($configuration->has_events()) {
            foreach ($configuration->get_events() as $event) {
                $events[$event->get_action()] = $event->get_method();
            }
        }
        return $events;
    }
}