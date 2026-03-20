<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Class_Extensions_Chain;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Class_Extension;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Extension_Not_In_Chain_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Configuration_Not_Found_Exception;
class Module_Class_Extensions_Merging_Service implements Module_Class_Extensions_Merging_Service_Interface
{
    /**
     * @throws ExtensionNotInChainException
     * @throws ModuleConfigurationNotFoundException
     */
    public function merge(Shop_Configuration $shop_configuration, Module_Configuration $module_configuration): Class_Extensions_Chain
    {
        $chain = $shop_configuration->get_class_extensions_chain();
        if (!$shop_configuration->has_module_configuration($module_configuration->get_id())) {
            $chain->add_extensions($module_configuration->get_class_extensions());
        } else {
            $chain = $this->add_new_module_extensions_to_chain($module_configuration, $shop_configuration, $chain);
            $chain = $this->replace_existing_module_extensions_in_chain($module_configuration, $shop_configuration, $chain);
            $chain = $this->remove_deleted_module_extensions_from_chain($module_configuration, $shop_configuration, $chain);
        }
        return $chain;
    }
    /**
     *
     * @throws ModuleConfigurationNotFoundException
     * @throws ExtensionNotInChainException
     */
    private function remove_deleted_module_extensions_from_chain(Module_Configuration $module_configuration, Shop_Configuration $shop_configuration, Class_Extensions_Chain $class_extension_chain): Class_Extensions_Chain
    {
        $existent_module_configuration = $shop_configuration->get_module_configuration($module_configuration->get_id());
        foreach ($existent_module_configuration->get_class_extensions() as $extension) {
            if (!$this->is_extending_shop_class($extension, $module_configuration->get_class_extensions())) {
                $class_extension_chain->remove_extension($extension);
            }
        }
        return $class_extension_chain;
    }
    /**
     *
     * @throws ModuleConfigurationNotFoundException
     */
    private function replace_existing_module_extensions_in_chain(Module_Configuration $module_configuration, Shop_Configuration $shop_configuration, Class_Extensions_Chain $chain): Class_Extensions_Chain
    {
        $existent_module_configuration = $shop_configuration->get_module_configuration($module_configuration->get_id());
        foreach ($existent_module_configuration->get_class_extensions() as $existing_extension) {
            foreach ($module_configuration->get_class_extensions() as $new_extension) {
                if ($this->are_extensions_equal($existing_extension, $new_extension)) {
                    $this->replace_existing_extension($chain, $existing_extension, $new_extension);
                }
            }
        }
        return $chain;
    }
    /**
     *
     * @throws ModuleConfigurationNotFoundException
     */
    private function add_new_module_extensions_to_chain(Module_Configuration $module_configuration, Shop_Configuration $shop_configuration, Class_Extensions_Chain $chain): Class_Extensions_Chain
    {
        foreach ($module_configuration->get_class_extensions() as $class_extension) {
            $existent_module_configuration = $shop_configuration->get_module_configuration($module_configuration->get_id());
            if (!$existent_module_configuration->is_extending_shop_class($class_extension->get_shop_class_name())) {
                $chain->add_extension($class_extension);
            }
        }
        return $chain;
    }
    /**
     * @param ClassExtension[]          $newClassExtensions
     *
     */
    private function is_extending_shop_class(Class_Extension $existing_class_extension, array $new_class_extensions): bool
    {
        foreach ($new_class_extensions as $new_extension) {
            if ($new_extension->get_shop_class_name() === $existing_class_extension->get_shop_class_name()) {
                return true;
            }
        }
        return false;
    }
    private function are_extensions_equal(Class_Extension $existing_extension, Class_Extension $new_extension): bool
    {
        return $existing_extension->get_shop_class_name() === $new_extension->get_shop_class_name() && $existing_extension->get_module_extension_class_name() !== $new_extension->get_module_extension_class_name();
    }
    /**
     * Converts e.g. the chain [Class1, ClassOld, Class3] to [Class1, ClassNew, Class3]. Keeping the order is important
     * as the order can be changed in OXID eShop admin.
     */
    private function replace_existing_extension(Class_Extensions_Chain $chain, Class_Extension $existing_extension, Class_Extension $new_extension): void
    {
        $class_extension_chain = $chain->get_chain();
        $shop_class_namespace_in_chain = $class_extension_chain[$existing_extension->get_shop_class_name()];
        foreach ($shop_class_namespace_in_chain as $key => $existing_extension_in_chain) {
            if ($existing_extension_in_chain === $existing_extension->get_module_extension_class_name()) {
                $class_extension_chain[$existing_extension->get_shop_class_name()][$key] = $new_extension->get_module_extension_class_name();
            }
        }
        $chain->set_chain($class_extension_chain);
    }
}