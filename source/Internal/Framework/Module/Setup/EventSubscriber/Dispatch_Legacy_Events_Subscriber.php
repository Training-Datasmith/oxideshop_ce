<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event_Subscriber;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Before_Module_Deactivation_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Finalizing_Module_Activation_Event;
use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
class Dispatch_Legacy_Events_Subscriber implements Event_Subscriber_Interface
{
    public function __construct(private readonly Module_Configuration_Dao_Interface $module_configuration_dao)
    {
    }
    public function execute_metadata_on_activation_event(Finalizing_Module_Activation_Event $event): void
    {
        $this->execute_metadata_event('onActivate', $event->get_module_id(), $event->get_shop_id());
    }
    public function execute_metadata_on_deactivation_event(Before_Module_Deactivation_Event $event): void
    {
        $this->execute_metadata_event('onDeactivate', $event->get_module_id(), $event->get_shop_id());
    }
    private function execute_metadata_event(string $event_name, string $module_id, int $shop_id): void
    {
        $module_configuration = $this->module_configuration_dao->get($module_id, $shop_id);
        if ($module_configuration->has_events()) {
            $events = [];
            foreach ($module_configuration->get_events() as $event) {
                $events[$event->get_action()] = $event->get_method();
            }
            if (array_key_exists($event_name, $events)) {
                \call_user_func($events[$event_name]);
            }
        }
    }
    public static function get_subscribed_events(): array
    {
        return [Finalizing_Module_Activation_Event::class => 'executeMetadataOnActivationEvent', Before_Module_Deactivation_Event::class => 'executeMetadataOnDeactivationEvent'];
    }
}