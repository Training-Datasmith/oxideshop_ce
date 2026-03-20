<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Directory_Iterator;
use function dirname;
use function in_array;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Chain\Class_Extensions_Chain_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Chain\Template_Extension_Chain_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Shop_Configuration_Not_Found_Exception;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Filesystem;
class Shop_Configuration_Dao implements Shop_Configuration_Dao_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context, private readonly Filesystem $file_system, private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Class_Extensions_Chain_Dao_Interface $class_extensions_chain_dao, private readonly Template_Extension_Chain_Dao_Interface $template_extension_chain_dao)
    {
    }
    /**
     * @throws ShopConfigurationNotFoundException
     */
    public function get(int $shop_id): Shop_Configuration
    {
        if (!$this->is_shop_id_exists($shop_id)) {
            throw new Shop_Configuration_Not_Found_Exception('Configuration for ShopID ' . $shop_id . ' not found');
        }
        $configuration = new Shop_Configuration();
        $configuration->set_class_extensions_chain($this->class_extensions_chain_dao->get_chain($shop_id));
        $configuration->set_module_template_extension_chain($this->template_extension_chain_dao->get_chain($shop_id));
        foreach ($this->module_configuration_dao->get_all($shop_id) as $module_configuration) {
            $configuration->add_module_configuration($module_configuration);
        }
        return $configuration;
    }
    /**
     * @deprecated use ModuleConfigurationDaoInterface::save() and ClassExtensionsChainDaoInterface::saveChain() instead
     */
    public function save(Shop_Configuration $shop_configuration, int $shop_id): void
    {
        $this->module_configuration_dao->delete_all($shop_id);
        foreach ($shop_configuration->get_module_configurations() as $module_configuration) {
            $this->module_configuration_dao->save($module_configuration, $shop_id);
        }
        $this->class_extensions_chain_dao->save_chain($shop_id, $shop_configuration->get_class_extensions_chain());
    }
    /**
     * @return ShopConfiguration[]
     * @throws ShopConfigurationNotFoundException
     */
    public function get_all(): array
    {
        $configurations = [];
        foreach ($this->get_shop_ids() as $shop_id) {
            $configurations[$shop_id] = $this->get($shop_id);
        }
        return $configurations;
    }
    /**
     * @deprecated will be completely removed
     */
    public function delete_all(): void
    {
        if ($this->file_system->exists($this->get_shops_configuration_directory())) {
            $this->file_system->remove($this->get_shops_configuration_directory());
        }
    }
    /**
     * @return int[]
     */
    private function get_shop_ids(): array
    {
        $shop_ids = [];
        if (file_exists($this->get_shops_configuration_directory())) {
            $dir = new Directory_Iterator($this->get_shops_configuration_directory());
            foreach ($dir as $file_info) {
                if ($file_info->is_dir() && is_numeric($file_info->get_filename())) {
                    $shop_ids[] = (int) $file_info->get_filename();
                }
            }
        }
        return $shop_ids;
    }
    private function get_shops_configuration_directory(): string
    {
        return dirname($this->context->get_shop_configuration_directory($this->context->get_default_shop_id()));
    }
    private function is_shop_id_exists(int $shop_id): bool
    {
        return in_array($shop_id, $this->get_shop_ids(), true);
    }
}