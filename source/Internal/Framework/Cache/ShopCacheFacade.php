<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Cache;

use Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Adapter\Tag_Aware_Adapter_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Event\Clear_Shop_Cache_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Cache\Shop_Template_Cache_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Shop_Adapter_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
readonly class Shop_Cache_Facade implements Shop_Cache_Cleaner_Interface
{
    public function __construct(private Context_Interface $context, private Tag_Aware_Adapter_Factory_Interface $tag_aware_adapter_factory, private Shop_Adapter_Interface $shop_adapter, private Shop_Template_Cache_Service_Interface $template_cache_service, private Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    public function clear(int $shop_id): void
    {
        $this->shop_adapter->invalidate_modules_cache();
        $this->template_cache_service->invalidate_cache($shop_id);
        $this->tag_aware_adapter_factory->create($shop_id)->clear();
        $this->event_dispatcher->dispatch(new Clear_Shop_Cache_Event($shop_id));
    }
    public function clear_all(): void
    {
        $this->shop_adapter->invalidate_modules_cache();
        $this->template_cache_service->invalidate_all_shops_cache();
        foreach ($this->context->get_all_shop_ids() as $shop_id) {
            $this->tag_aware_adapter_factory->create($shop_id)->clear();
            $this->event_dispatcher->dispatch(new Clear_Shop_Cache_Event($shop_id));
        }
    }
}