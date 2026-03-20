<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Configuration_Not_Found_Exception;
class Shop_Configuration
{
    /** @var ModuleConfiguration[] */
    private array $module_configurations = [];
    private \Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Class_Extensions_Chain $class_extensions_chain;
    private \Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Template_Extension_Chain $module_template_extensions_chain;
    public function __construct()
    {
        $this->set_class_extensions_chain(new Class_Extensions_Chain());
        $this->set_module_template_extension_chain(new Module_Template_Extension_Chain());
    }
    /**
     * @throws ModuleConfigurationNotFoundException
     */
    public function get_module_configuration(string $module_id): Module_Configuration
    {
        if (\array_key_exists($module_id, $this->module_configurations)) {
            return $this->module_configurations[$module_id];
        }
        throw new Module_Configuration_Not_Found_Exception('There is no module configuration with id ' . $module_id);
    }
    /**
     * @return ModuleConfiguration[]
     */
    public function get_module_configurations(): array
    {
        return $this->module_configurations;
    }
    /**
     * @return $this
     */
    public function add_module_configuration(Module_Configuration $module_configuration): static
    {
        $this->module_configurations[$module_configuration->get_id()] = $module_configuration;
        return $this;
    }
    /**
     * @deprecated use ModuleConfigurationDaoInterface::delete() instead
     *
     *
     * @throws ModuleConfigurationNotFoundException
     */
    public function delete_module_configuration(string $module_id): void
    {
        if (\array_key_exists($module_id, $this->module_configurations)) {
            $this->remove_module_extension_from_class_chain($module_id);
            unset($this->module_configurations[$module_id]);
        } else {
            throw new Module_Configuration_Not_Found_Exception('There is no module configuration with id ' . $module_id);
        }
    }
    public function get_module_ids_of_module_configurations(): array
    {
        return array_keys($this->module_configurations);
    }
    public function set_class_extensions_chain(Class_Extensions_Chain $chain): self
    {
        $this->class_extensions_chain = $chain;
        return $this;
    }
    public function get_class_extensions_chain(): Class_Extensions_Chain
    {
        return $this->class_extensions_chain;
    }
    public function set_module_template_extension_chain(Module_Template_Extension_Chain $module_template_extensions_chain): void
    {
        $this->module_template_extensions_chain = $module_template_extensions_chain;
    }
    public function get_module_template_extension_chain(): Module_Template_Extension_Chain
    {
        return $this->module_template_extensions_chain;
    }
    public function has_module_configuration(string $module_id): bool
    {
        return isset($this->module_configurations[$module_id]);
    }
    private function remove_module_extension_from_class_chain(string $module_id): void
    {
        $module_configuration = $this->module_configurations[$module_id];
        foreach ($module_configuration->get_class_extensions() as $class_extension) {
            $this->get_class_extensions_chain()->remove_extension($class_extension);
        }
    }
}