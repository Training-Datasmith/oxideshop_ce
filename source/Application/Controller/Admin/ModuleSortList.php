<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Shop_Configuration_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Chain\Class_Extensions_Chain_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Class_Extensions_Chain;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
/**
 * Extensions sorting list handler.
 * Admin Menu: Extensions -> Module -> Installed Shop Modules.
 */
class Module_Sort_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * It is unsave to use a backslash as HTML id in conjunction with UI.sortable, so it will be replaced in the
     * view and restored in the controller
     */
    public const BACKSLASH_REPLACEMENT = '---';
    /**
     * Executes parent method parent::render(), loads active and disabled extensions,
     * checks if there are some deleted and registered modules and returns name of template file "module_sortlist".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $o_module_list = ox_new(\Oxid_Esales\Eshop\Core\Module\Module_List::class);
        $class_extensions_chain = $this->get_shop_configuration()->get_class_extensions_chain();
        $sanitized_extend_class = [];
        foreach ($class_extensions_chain as $extended_class => $class_chain) {
            $sanitized_key = str_replace('\\', self::BACKSLASH_REPLACEMENT, $extended_class);
            $sanitized_extend_class[$sanitized_key] = $class_chain;
        }
        $this->_a_view_data['aExtClasses'] = $sanitized_extend_class;
        $this->_a_view_data['aDisabledModules'] = $o_module_list->get_disabled_module_classes();
        // checking if there are any deleted extensions
        if (\Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('blSkipDeletedExtChecking') == false) {
            $a_deleted_ext = $o_module_list->get_deleted_extensions();
            if (!empty($a_deleted_ext)) {
                $this->_a_view_data['aDeletedExt'] = $a_deleted_ext;
            }
        }
        return 'module_sortlist';
    }
    /**
     * Saves updated aModules config var
     */
    public function save(): void
    {
        $class_extensions_chain_from_request = json_decode((string) Registry::get_request()->get_request_escaped_parameter('aModules'), true);
        $sanitized_class_extensions_chain = $this->sanitize_class_extensions_chain($class_extensions_chain_from_request);
        Container_Facade::get(Class_Extensions_Chain_Dao_Interface::class)->save_chain(Container_Facade::get(Context_Interface::class)->get_current_shop_id(), new Class_Extensions_Chain($sanitized_class_extensions_chain));
    }
    /**
     * Removes extension metadata from eShop
     */
    public function remove(): void
    {
        //if user selected not to update modules, skipping all updates
        if (Registry::get_request()->get_request_escaped_parameter('noButton')) {
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('blSkipDeletedExtChecking', true);
            return;
        }
        $o_module_list = ox_new(\Oxid_Esales\Eshop\Core\Module\Module_List::class);
        $o_module_list->cleanup();
    }
    private function sanitize_class_extensions_chain(array $chain): array
    {
        $sanitized_class_extensions_chain = [];
        foreach ($chain as $key => $value) {
            $sanitized_key = str_replace(self::BACKSLASH_REPLACEMENT, '\\', $key);
            $sanitized_class_extensions_chain[$sanitized_key] = $value;
        }
        return $sanitized_class_extensions_chain;
    }
    private function get_shop_configuration(): Shop_Configuration
    {
        return Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get();
    }
}