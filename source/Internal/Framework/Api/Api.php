<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Api;

use Oxid_Esales\Eshop_Community\Internal\Container\Container_Factory;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Component\Http_Foundation\Request;
use Symfony\Component\Http_Foundation\Request_Stack;
use Symfony\Component\Http_Kernel\Controller\Argument_Resolver;
use Symfony\Component\Http_Kernel\Controller\Container_Controller_Resolver;
use Symfony\Component\Http_Kernel\Event_Listener\Router_Listener;
use Symfony\Component\Http_Kernel\Http_Kernel;
use Symfony\Component\Routing\Matcher\Compiled_Url_Matcher;
use Symfony\Component\Routing\Request_Context;
class Api
{
    public function run(): void
    {
        $container = Container_Factory::get_instance()->get_container();
        $request = Request::create_from_globals();
        $context = new Request_Context();
        $context->from_request($request);
        $matcher = new Compiled_Url_Matcher($container->get_parameter('oxid.routes'), $context);
        $request_stack = new Request_Stack();
        $dispatcher = $container->get(Event_Dispatcher_Interface::class);
        $dispatcher->add_subscriber(new Router_Listener($matcher, $request_stack));
        $kernel = new Http_Kernel($dispatcher, new Container_Controller_Resolver($container), $request_stack, new Argument_Resolver(namedResolvers: $container));
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
    }
}