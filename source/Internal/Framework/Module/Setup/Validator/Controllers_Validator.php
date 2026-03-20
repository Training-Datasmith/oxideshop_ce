<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Validator;

// phpcs:disable
use function array_key_exists;
use function in_array;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Controller;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Exception\Controllers_Duplication_Module_Configuration_Exception;
// phpcs:enable
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Shop_Adapter_Interface;
use Psr\Log\Logger_Interface;
class Controllers_Validator implements Module_Configuration_Validator_Interface
{
    public function __construct(private readonly Shop_Adapter_Interface $shop_adapter, private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao, private readonly Logger_Interface $logger)
    {
    }
    /**
     *
     * @throws ControllersDuplicationModuleConfigurationException
     */
    public function validate(Module_Configuration $configuration, int $shop_id): void
    {
        if ($configuration->has_controllers()) {
            $controller_class_map = $this->get_controllers_class_map($shop_id);
            foreach ($configuration->get_controllers() as $controller) {
                if (!$this->controller_already_exists_in_map($controller, $controller_class_map)) {
                    $this->validate_key_duplication($controller, $controller_class_map);
                    $this->validate_namespace_duplication($controller, $controller_class_map);
                } else {
                    /**
                     * @TODO this is a wrong place to check and log database discrepancy, not only controllers should be
                     *       checked. It should be moved to separate module data discrepancy checker outside the module
                     *       validation.
                     */
                    $this->logger->error('Module data discrepancy error: module data (controller with id ' . $controller->get_id() . ' and namespace: ' . $controller->get_controller_class_name_space() . ' ) for module ' . $configuration->get_id() . ' was present in the database before the module activation');
                }
            }
        }
    }
    private function controller_already_exists_in_map(Controller $controller, array $controller_class_map): bool
    {
        return array_key_exists(strtolower($controller->get_id()), $controller_class_map) && $controller_class_map[strtolower($controller->get_id())] === $controller->get_controller_class_name_space();
    }
    private function get_modules_controller_class_map(int $shop_id): array
    {
        $module_controllers_class_map = [];
        foreach ($this->shop_configuration_dao->get($shop_id)->get_module_configurations() as $module_configuration) {
            if ($module_configuration->is_activated()) {
                foreach ($module_configuration->get_controllers() as $controller) {
                    $module_controllers_class_map[$controller->get_id()] = $controller->get_controller_class_name_space();
                }
            }
        }
        return $module_controllers_class_map;
    }
    /**
     * @throws ControllersDuplicationModuleConfigurationException
     */
    private function validate_key_duplication(Controller $controller, array $controller_class_map): void
    {
        if (array_key_exists(strtolower($controller->get_id()), $controller_class_map)) {
            throw new Controllers_Duplication_Module_Configuration_Exception('Controller key duplication: ' . $controller->get_id());
        }
    }
    /**
     * @throws ControllersDuplicationModuleConfigurationException
     */
    private function validate_namespace_duplication(Controller $controller, array $controller_class_map): void
    {
        if (in_array($controller->get_controller_class_name_space(), $controller_class_map, true)) {
            throw new Controllers_Duplication_Module_Configuration_Exception('Controller namespace duplication: ' . $controller->get_controller_class_name_space());
        }
    }
    private function get_controllers_class_map(int $shop_id): array
    {
        return array_merge($this->shop_adapter->get_shop_controller_class_map(), $this->get_modules_controller_class_map($shop_id));
    }
}