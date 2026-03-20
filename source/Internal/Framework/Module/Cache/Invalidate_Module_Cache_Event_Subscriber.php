<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Cache;

use Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Shop_Cache_Cleaner_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Event\Module_Configuration_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Finalizing_Module_Activation_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Finalizing_Module_Deactivation_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Module_Setup_Event;
use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
class Invalidate_Module_Cache_Event_Subscriber implements Event_Subscriber_Interface
{
    public function __construct(private readonly Shop_Cache_Cleaner_Interface $shop_cache_cleaner)
    {
    }
    public function invalidate_module_cache(Module_Setup_Event|Module_Configuration_Changed_Event $event): void
    {
        $this->shop_cache_cleaner->clear($event->get_shop_id());
    }
    public static function get_subscribed_events(): array
    {
        return [Finalizing_Module_Activation_Event::class => 'invalidateModuleCache', Finalizing_Module_Deactivation_Event::class => 'invalidateModuleCache', Module_Configuration_Changed_Event::class => 'invalidateModuleCache'];
    }
}