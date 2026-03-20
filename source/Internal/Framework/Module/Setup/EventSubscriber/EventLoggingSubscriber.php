<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event_Subscriber;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Services_Yaml_Configuration_Error_Event;
use Psr\Log\Logger_Interface;
use Psr\Log\Log_Level;
use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
class Event_Logging_Subscriber implements Event_Subscriber_Interface
{
    public function __construct(private readonly Logger_Interface $logger)
    {
    }
    public function log_configuration_error(Services_Yaml_Configuration_Error_Event $event): void
    {
        $this->logger->log(Log_Level::ERROR, $event->get_error_message() . ' (' . $event->get_configuration_file_path() . ')');
    }
    public static function get_subscribed_events(): array
    {
        return [Services_Yaml_Configuration_Error_Event::class => 'logConfigurationError'];
    }
}