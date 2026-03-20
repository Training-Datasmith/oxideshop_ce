<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Rate_Limiter;

use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
use Symfony\Component\Http_Kernel\Event\Response_Event;
use Symfony\Component\Http_Kernel\Kernel_Events;
readonly class Rate_Limit_Headers_Listener implements Event_Subscriber_Interface
{
    public static function get_subscribed_events(): array
    {
        return [Kernel_Events::RESPONSE => ['onKernelResponse', -10]];
    }
    public function on_kernel_response(Response_Event $event): void
    {
        if (!$event->is_main_request()) {
            return;
        }
        $rate_limit_info = $event->get_request()->attributes->get('_rate_limit_info');
        if ($rate_limit_info === null) {
            return;
        }
        $response = $event->get_response();
        $response->headers->set('X-RateLimit-Limit', (string) $rate_limit_info['limit']);
        $response->headers->set('X-RateLimit-Remaining', (string) $rate_limit_info['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $rate_limit_info['reset']);
    }
}