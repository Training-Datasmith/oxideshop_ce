<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Exception\Invalid_Class_Extension_Namespace_Exception;
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Shop_Adapter_Interface;
class Class_Extensions_Validator implements Module_Configuration_Validator_Interface
{
    public function __construct(private readonly Shop_Adapter_Interface $shop_adapter)
    {
    }
    /**
     *
     * @throws InvalidClassExtensionNamespaceException
     */
    public function validate(Module_Configuration $configuration, int $shop_id): void
    {
        if ($configuration->has_class_extensions()) {
            foreach ($configuration->get_class_extensions() as $extension) {
                if ($this->shop_adapter->is_namespace($extension->get_shop_class_name())) {
                    $this->validate_class_to_be_patched_namespace($extension->get_shop_class_name());
                }
            }
        }
    }
    /**
     * @throws InvalidClassExtensionNamespaceException
     */
    private function validate_class_to_be_patched_namespace(string $namespace): void
    {
        if ($this->shop_adapter->is_shop_edition_namespace($namespace)) {
            throw new Invalid_Class_Extension_Namespace_Exception('Module should not extend shop edition class: ' . $namespace);
        }
        if ($this->shop_adapter->is_shop_unified_namespace($namespace) && !class_exists($namespace)) {
            throw new Invalid_Class_Extension_Namespace_Exception('Module tries to extend non existent shop class: ' . $namespace);
        }
    }
}