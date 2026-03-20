<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Compiler_Pass;

use Symfony\Component\Dependency_Injection\Compiler\Compiler_Pass_Interface;
use Symfony\Component\Dependency_Injection\Container_Builder;
class View_Controller_Pass implements Compiler_Pass_Interface
{
    public function process(Container_Builder $container): void
    {
        $tagged_services = $container->find_tagged_service_ids('oxid.view_controller');
        $controllers_map = [];
        foreach ($tagged_services as $id => $tags) {
            foreach ($tags as $attributes) {
                $controllers_map[$attributes['controller_key']] = $id;
            }
        }
        $container->set_parameter('oxid.view_controllers_map', $controllers_map);
    }
}