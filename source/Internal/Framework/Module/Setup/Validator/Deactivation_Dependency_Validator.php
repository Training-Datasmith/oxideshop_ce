<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Exception\Dependency_Validation_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service\Module_Dependency_Resolver_Interface;
class Deactivation_Dependency_Validator implements Module_Configuration_Validator_Interface
{
    public function __construct(private readonly Module_Dependency_Resolver_Interface $module_dependency_resolver)
    {
    }
    /**
     * @throws DependencyValidationException
     */
    public function validate(Module_Configuration $configuration, int $shop_id): void
    {
        $unresolved_dependencies = $this->module_dependency_resolver->get_unresolved_deactivation_dependencies($configuration->get_id(), $shop_id);
        if (!$unresolved_dependencies->has_module_dependencies()) {
            return;
        }
        throw new Dependency_Validation_Exception(sprintf('Module "%s" has unfulfilled dependencies in shop "%d" and can not be deactivated. 
                "%1$s" requires the following modules to be deactivated: "%s"
                Make sure all dependencies are resolved and try again.', $configuration->get_id(), $shop_id, implode(', ', $unresolved_dependencies->get_module_ids())));
    }
}