<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Api;

use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
use Symfony\Component\Http_Foundation\Json_Response;
use Symfony\Component\Http_Foundation\Response;
use Symfony\Component\Http_Kernel\Event\Exception_Event;
use Symfony\Component\Http_Kernel\Exception\Http_Exception_Interface;
use Symfony\Component\Http_Kernel\Kernel_Events;
class Http_Exception_Listener implements Event_Subscriber_Interface
{
    public static function get_subscribed_events(): array
    {
        return [Kernel_Events::EXCEPTION => ['onKernelException', -10]];
    }
    public function on_kernel_exception(Exception_Event $event): void
    {
        $exception = $event->get_throwable();
        if ($exception instanceof Http_Exception_Interface) {
            $response = new Json_Response(['error' => $this->get_error_message($exception)], $exception->get_status_code(), $exception->get_headers());
            $event->set_response($response);
        }
    }
    private function get_error_message(Http_Exception_Interface $exception): string
    {
        if (filter_var(getenv('OXID_DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN)) {
            return $exception->get_message();
        }
        return Response::$status_texts[$exception->get_status_code()] ?? 'An error occurred';
    }
}