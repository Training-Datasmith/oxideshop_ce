<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Api;

use Symfony\Component\Routing\Loader\Attribute_Class_Loader;
use Symfony\Component\Routing\Route;
class Attribute_Route_Controller_Loader extends Attribute_Class_Loader
{
    protected function configure_route(Route $route, \ReflectionClass $class, \ReflectionMethod $method, object $attr): void
    {
        $route->set_default('_controller', [$class->get_name(), $method->get_name()]);
    }
}