<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Rate_Limiter;

use Symfony\Component\Event_Dispatcher\Event_Subscriber_Interface;
use Symfony\Component\Http_Foundation\Json_Response;
use Symfony\Component\Http_Foundation\Request;
use Symfony\Component\Http_Foundation\Response;
use Symfony\Component\Http_Kernel\Event\Request_Event;
use Symfony\Component\Http_Kernel\Kernel_Events;
readonly class Api_Rate_Limit_Listener implements Event_Subscriber_Interface
{
    private const API_PATH_PREFIX = '/api/';
    public function __construct(private bool $enabled, private array $excluded_routes, private Api_Rate_Limiter_Factory_Interface $rate_limiter_factory, private Client_Identifier_Provider_Interface $client_identifier_provider)
    {
    }
    public static function get_subscribed_events(): array
    {
        return [Kernel_Events::REQUEST => ['onKernelRequest', 10]];
    }
    public function on_kernel_request(Request_Event $event): void
    {
        if (!$event->is_main_request()) {
            return;
        }
        $request = $event->get_request();
        if (!$this->is_api_request($request)) {
            return;
        }
        if (!$this->enabled) {
            return;
        }
        if ($this->is_route_excluded($request->get_path_info())) {
            return;
        }
        $limiter = $this->rate_limiter_factory->create($this->client_identifier_provider->get_client_identifier($request));
        $limit = $limiter->consume();
        if (!$limit->is_accepted()) {
            $event->set_response($this->create_rate_limit_exceeded_response($limit->get_retry_after()));
            return;
        }
        $request->attributes->set('_rate_limit_info', ['limit' => $limit->get_limit(), 'remaining' => $limit->get_remaining_tokens(), 'reset' => $limit->get_retry_after()->get_timestamp()]);
    }
    private function is_api_request(Request $request): bool
    {
        return str_starts_with($request->get_path_info(), self::API_PATH_PREFIX);
    }
    private function is_route_excluded(string $route): bool
    {
        foreach ($this->excluded_routes as $pattern) {
            if ($this->match_route($route, $pattern)) {
                return true;
            }
        }
        return false;
    }
    private function match_route(string $route, string $pattern): bool
    {
        if ($pattern === $route) {
            return true;
        }
        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace(['/', '*'], ['\/', '.*'], $pattern) . '$/';
            return (bool) preg_match($regex, $route);
        }
        return false;
    }
    private function create_rate_limit_exceeded_response(\DateTimeImmutable $retry_after): Json_Response
    {
        $retry_after_seconds = max(0, $retry_after->get_timestamp() - time());
        $response = new Json_Response(['error' => 'rate_limit_exceeded', 'message' => 'Too many requests. Please try again later.', 'retry_after' => $retry_after_seconds], Response::HTTP_TOO_MANY_REQUESTS);
        $response->headers->set('Retry-After', (string) $retry_after_seconds);
        $response->headers->set('X-RateLimit-Reset', (string) $retry_after->get_timestamp());
        return $response;
    }
}