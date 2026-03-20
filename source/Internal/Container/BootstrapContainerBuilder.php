<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Container;

use Symfony\Component\Config\File_Locator;
use Symfony\Component\Dependency_Injection\Container_Builder;
use Symfony\Component\Dependency_Injection\Loader\Yaml_File_Loader;
use Symfony\Component\Event_Dispatcher\Dependency_Injection\Register_Listeners_Pass;
/**
 * @internal
 */
class Bootstrap_Container_Builder
{
    public function create(): Container_Builder
    {
        $symfony_container = new Container_Builder();
        $symfony_container->add_compiler_pass(new Register_Listeners_Pass());
        $loader = new Yaml_File_Loader($symfony_container, new File_Locator(__DIR__));
        $loader->load('bootstrap-services.yaml');
        return $symfony_container;
    }
}