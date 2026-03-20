<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Extension_Not_In_Chain_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Configuration_Not_Found_Exception;
class Module_Configuration_Merging_Service implements Module_Configuration_Merging_Service_Interface
{
    public function __construct(private readonly Settings_Merging_Service_Interface $settings_merging_service, private readonly Module_Class_Extensions_Merging_Service_Interface $class_extensions_merging_service)
    {
    }
    /**
     * @inheritDoc
     * @throws ModuleConfigurationNotFoundException
     * @throws ExtensionNotInChainException
     */
    public function merge(Shop_Configuration $shop_configuration, Module_Configuration $module_configuration): Shop_Configuration
    {
        $module_configuration_clone = $this->clone_module_configuration($module_configuration);
        $merged_class_extension_chain = $this->class_extensions_merging_service->merge($shop_configuration, $module_configuration_clone);
        $shop_configuration->set_class_extensions_chain($merged_class_extension_chain);
        $merged_module_configuration = $this->settings_merging_service->merge($shop_configuration, $module_configuration_clone);
        $this->set_activated_option_to_merged_configuration($shop_configuration, $merged_module_configuration);
        $shop_configuration->add_module_configuration($merged_module_configuration);
        return $shop_configuration;
    }
    private function clone_module_configuration(Module_Configuration $module_configuration): Module_Configuration
    {
        $module_setting_clones = [];
        foreach ($module_configuration->get_module_settings() as $module_setting) {
            $module_setting_clones[] = clone $module_setting;
        }
        $module_configuration_clone = clone $module_configuration;
        $module_configuration_clone->set_module_settings($module_setting_clones);
        return $module_configuration_clone;
    }
    /**
     * @throws ModuleConfigurationNotFoundException
     */
    private function set_activated_option_to_merged_configuration(Shop_Configuration $shop_configuration, Module_Configuration $merged_module_configuration): void
    {
        if ($shop_configuration->has_module_configuration($merged_module_configuration->get_id())) {
            $is_configured = $shop_configuration->get_module_configuration($merged_module_configuration->get_id())->is_activated();
            $merged_module_configuration->set_activated($is_configured);
        }
    }
}