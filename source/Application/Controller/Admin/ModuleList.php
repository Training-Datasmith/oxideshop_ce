<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Module\Module;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Shop_Configuration_Dao_Bridge_Interface;
/**
 * Admin actionss manager.
 * Sets list template, list object class ('oxactions') and default sorting
 * field ('oxactions.oxtitle').
 * Admin Menu: Manage Products -> Actions.
 */
class Module_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * @var array Loaded modules array
     */
    protected $_a_modules = [];
    /**
     * Calls parent::render() and returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_a_view_data['mylist'] = $this->get_installed_modules();
        return 'module_list';
    }
    private function get_installed_modules(): array
    {
        $shop_configuration = Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get();
        $modules = [];
        foreach ($shop_configuration->get_module_configurations() as $module_configuration) {
            $module = ox_new(Module::class);
            $module->load($module_configuration->get_id());
            $modules[] = $module;
        }
        $modules = $this->sort_modules_by_title_alphabetically($modules);
        return $this->convert_modules_to_associative_array($modules);
    }
    private function sort_modules_by_title_alphabetically(array $modules): array
    {
        usort($modules, fn($a, $b): int => strcmp((string) $a->get_title(), (string) $b->get_title()));
        return $modules;
    }
    private function convert_modules_to_associative_array(array $modules): array
    {
        $modules_associative_array = [];
        foreach ($modules as $module) {
            $modules_associative_array[$module->get_id()] = $module;
        }
        return $modules_associative_array;
    }
}