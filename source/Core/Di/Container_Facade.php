<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Core\Di;

use Oxid_Esales\Eshop_Community\Internal\Container\Container_Factory;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Contracts\Event_Dispatcher\Event;
/**
 * A Service Locator fallback to use in the application areas, not managed by the Dependency Injection Component (e.g. Application, Core).
 * Never use this class (or Container directly) in other namespaces (e.g. Internal)!
 */
final class Container_Facade
{
    public static function has(string $id): bool
    {
        return Container_Factory::get_instance()->get_container()->has($id);
    }
    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public static function get(string $id): object
    {
        return Container_Factory::get_instance()->get_container()->get($id);
    }
    public static function has_parameter(string $name): bool
    {
        return Container_Factory::get_instance()->get_container()->has_parameter($name);
    }
    public static function get_parameter(string $name): mixed
    {
        return Container_Factory::get_instance()->get_container()->get_parameter($name);
    }
    /**
     * @template T of Event
     * @param T $event
     * @return T
     */
    public static function dispatch(Event $event): Event
    {
        return Container_Factory::get_instance()->get_container()->get(Event_Dispatcher_Interface::class)->dispatch($event);
    }
}