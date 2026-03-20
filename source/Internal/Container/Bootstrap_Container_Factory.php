<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Container;

use Psr\Container\Container_Interface;
class Bootstrap_Container_Factory
{
    /**
     * This is a minimal container that does not need the shop
     * to be installed.
     */
    public static function get_bootstrap_container(): Container_Interface
    {
        $symfony_container = (new Bootstrap_Container_Builder())->create();
        $symfony_container->compile(true);
        return $symfony_container;
    }
}