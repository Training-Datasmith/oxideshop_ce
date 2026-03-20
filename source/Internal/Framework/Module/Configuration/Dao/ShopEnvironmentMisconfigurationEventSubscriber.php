<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Psr\Log\Logger_Interface;
use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
class Shop_Environment_Misconfiguration_Event_Subscriber implements Event_Subscriber_Interface
{
    public function __construct(private readonly Logger_Interface $logger)
    {
    }
    public function log_orphan_setting(Shop_Environment_With_Orphan_Setting_Event $event): void
    {
        $this->logger->warning('Environment configuration tries to change non-existing module setting. Environment value will be ignored', ['shopId' => $event->get_shop_id(), 'moduleId' => $event->get_module_id(), 'settingId' => $event->get_setting_id()]);
    }
    /** @return string[] */
    public static function get_subscribed_events(): array
    {
        return [Shop_Environment_With_Orphan_Setting_Event::class => 'logOrphanSetting'];
    }
}