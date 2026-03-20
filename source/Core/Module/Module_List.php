<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Module;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Shop_Configuration_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Bridge\Module_Activation_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\State\Module_State_Service_Interface;
/**
 * Modules list class.
 *
 * @deprecated since v6.4.0 (2019-03-22); Use service 'OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface'.
 * @internal Do not make a module extension for this class.
 */
class Module_List extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * All module extensions.
     *
     * @var array
     */
    protected $_a_module_extensions;
    /**
     * @return array
     */
    public function get_disabled_module_classes()
    {
        $disabled_modules = $this->get_disabled_module_configurations();
        $disabled_module_classes = [];
        foreach ($disabled_modules as $module) {
            if ($module->has_class_extensions()) {
                foreach ($module->get_class_extensions() as $extension_class) {
                    $disabled_module_classes[] = $extension_class->get_module_extension_class_name();
                }
            }
        }
        return $disabled_module_classes;
    }
    /**
     * Removes extension metadata from shop.
     */
    public function cleanup(): void
    {
        $deleted_modules = $this->get_deleted_extensions();
        $deleted_module_ids = array_keys($deleted_modules);
        $module_activation_bridge = Container_Facade::get(Module_Activation_Bridge_Interface::class);
        foreach ($deleted_module_ids as $module_id) {
            if ($module_activation_bridge->is_active($module_id, Registry::get_config()->get_shop_id())) {
                $module_activation_bridge->deactivate($module_id, Registry::get_config()->get_shop_id());
            }
        }
    }
    /**
     * Checks module list - if there is extensions that are registered, but extension directory is missing
     *
     * @return array
     */
    public function get_deleted_extensions()
    {
        $a_modules_ids = Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get()->get_module_ids_of_module_configurations();
        $o_module = $this->get_module();
        $a_deleted_ext = [];
        foreach ($a_modules_ids as $s_module_id) {
            $o_module->set_module_data(['id' => $s_module_id]);
            $a_invalid_extensions = $this->get_invalid_extensions($s_module_id);
            if ($a_invalid_extensions) {
                $a_deleted_ext[$s_module_id]['extensions'] = $a_invalid_extensions;
            }
        }
        return $a_deleted_ext;
    }
    /**
     * Returns oxModule object.
     *
     * @return \OxidEsales\Eshop\Core\Module\Module
     */
    public function get_module()
    {
        return ox_new(\Oxid_Esales\Eshop\Core\Module\Module::class);
    }
    /**
     * Parse array of module chains to nested array
     *
     * @param array $modules Module array (config format)
     *
     * @return array
     */
    public function parse_module_chains($modules)
    {
        $module_array = [];
        if (is_array($modules)) {
            foreach ($modules as $class => $module_chain) {
                if (strstr((string) $module_chain, '&')) {
                    $module_chain = explode('&', (string) $module_chain);
                } else {
                    $module_chain = [$module_chain];
                }
                $module_array[$class] = $module_chain;
            }
        }
        return $module_array;
    }
    /**
     * Returns module extensions.
     *
     * @param string $sModuleId
     *
     * @return array
     */
    public function get_module_extensions($s_module_id)
    {
        if (!isset($this->_a_module_extensions)) {
            $a_module_extension = $this->get_activate_modules_with_extended_class();
            $o_module = $this->get_module();
            $a_extension = [];
            foreach ($a_module_extension as $s_ox_class => $a_files) {
                foreach ($a_files as $s_file_path) {
                    $s_id = $o_module->get_id_by_path($s_file_path);
                    $a_extension[$s_id][$s_ox_class][] = $s_file_path;
                }
            }
            $this->_a_module_extensions = $a_extension;
        }
        return $this->_a_module_extensions[$s_module_id] ?? [];
    }
    /**
     * Returns shop classes and associated invalid module classes for a given module id
     *
     * @param string $moduleId Module id
     */
    private function get_invalid_extensions($module_id): array
    {
        $extended_shop_classes = $this->get_module_extensions($module_id);
        $invalid_module_classes = [];
        foreach ($extended_shop_classes as $extended_shop_class => $module_classes) {
            foreach ($module_classes as $module_class) {
                /** @var \Composer\Autoload\ClassLoader $composerClassLoader */
                $composer_class_loader = include VENDOR_PATH . 'autoload.php';
                if (!$composer_class_loader->find_file($module_class)) {
                    $invalid_module_classes[$extended_shop_class][] = $module_class;
                }
            }
        }
        return $invalid_module_classes;
    }
    /**
     * @return ModuleConfiguration[]
     */
    private function get_disabled_module_configurations(): array
    {
        $module_configurations = Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get()->get_module_configurations();
        $disabled_module_configurations = [];
        $module_state_service = Container_Facade::get(Module_State_Service_Interface::class);
        foreach ($module_configurations as $module_configuration) {
            if (!$module_state_service->is_active($module_configuration->get_id(), Registry::get_config()->get_shop_id())) {
                $disabled_module_configurations[] = $module_configuration;
            }
        }
        return $disabled_module_configurations;
    }
    /**
     * Returns Active module ids which have extensions or files.
     *
     * @return ModuleConfiguration[]
     */
    private function get_active_module_configurations(): array
    {
        $module_configurations = Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get()->get_module_configurations();
        $active_modules = [];
        $module_state_service = Container_Facade::get(Module_State_Service_Interface::class);
        foreach ($module_configurations as $module_configuration) {
            if ($module_state_service->is_active($module_configuration->get_id(), Registry::get_config()->get_shop_id())) {
                $active_modules[] = $module_configuration;
            }
        }
        return $active_modules;
    }
    /**
     * Get activate modules with Extended classes
     *
     * @return array
     */
    private function get_activate_modules_with_extended_class()
    {
        $extended_classes = [];
        foreach ($this->get_active_module_configurations() as $module_configuration) {
            if ($module_configuration->has_class_extensions()) {
                foreach ($module_configuration->get_class_extensions() as $extensions) {
                    if (!isset($extended_classes[$extensions->get_shop_class_name()])) {
                        $extended_classes[$extensions->get_shop_class_name()] = $extensions->get_module_extension_class_name();
                    } else {
                        $extended_classes[$extensions->get_shop_class_name()] .= '&' . $extensions->get_module_extension_class_name();
                    }
                }
            }
        }
        return $this->parse_module_chains($extended_classes);
    }
}