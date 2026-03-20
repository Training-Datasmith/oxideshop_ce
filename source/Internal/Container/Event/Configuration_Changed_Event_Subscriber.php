<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Container\Event;

use Oxid_Esales\Eshop_Community\Internal\Container\Container_Factory;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Event\Project_Yaml_Changed_Event;
use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
class Configuration_Changed_Event_Subscriber implements Event_Subscriber_Interface
{
    public function reset_container(Project_Yaml_Changed_Event $event): void
    {
        Container_Factory::reset_container();
    }
    public static function get_subscribed_events(): array
    {
        return [Project_Yaml_Changed_Event::class => 'resetContainer'];
    }
}