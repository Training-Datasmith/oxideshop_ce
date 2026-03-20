<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Compiler_Pass;

use Oxid_Esales\Eshop_Community\Internal\Framework\Api\Attribute_Route_Controller_Loader;
use Symfony\Component\Dependency_Injection\Compiler\Compiler_Pass_Interface;
use Symfony\Component\Dependency_Injection\Container_Builder;
use Symfony\Component\Routing\Matcher\Dumper\Compiled_Url_Matcher_Dumper;
use Symfony\Component\Routing\Route_Collection;
class Route_Pass implements Compiler_Pass_Interface
{
    public function process(Container_Builder $container): void
    {
        $loader = new Attribute_Route_Controller_Loader();
        $routes = new Route_Collection();
        foreach ($container->get_definitions() as $definition) {
            $class = $definition->get_class();
            if ($definition->is_public() && !$definition->is_abstract() && $class !== null && class_exists($class)) {
                $routes->add_collection($loader->load($class));
            }
        }
        $compiled_routes = (new Compiled_Url_Matcher_Dumper($routes))->get_compiled_routes();
        $container->set_parameter('oxid.routes', $compiled_routes);
    }
}