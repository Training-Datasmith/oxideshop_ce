<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Bridge\Module_Activation_Bridge_Interface;
class Module_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        if (Registry::get_request()->get_request_escaped_parameter('moduleId')) {
            $s_module_id = Registry::get_request()->get_request_escaped_parameter('moduleId');
        } else {
            $s_module_id = $this->get_edit_object_id();
        }
        $o_module = ox_new(\Oxid_Esales\Eshop\Core\Module\Module::class);
        if ($s_module_id) {
            if ($o_module->load($s_module_id)) {
                $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_tpl_language();
                $this->_a_view_data['oModule'] = $o_module;
                $this->_a_view_data['sModuleName'] = basename((string) $o_module->get_info('title', $i_lang));
                $this->_a_view_data['sModuleId'] = $o_module->get_id();
            } else {
                \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display(new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('EXCEPTION_MODULE_NOT_LOADED'));
            }
        }
        parent::render();
        return 'module_main';
    }
    /**
     * Activate module
     */
    public function activate_module(): void
    {
        if (Registry::get_config()->is_demo_shop()) {
            Registry::get_utils_view()->add_error_to_display('MODULE_ACTIVATION_NOT_POSSIBLE_IN_DEMOMODE');
            return;
        }
        try {
            Container_Facade::get(Module_Activation_Bridge_Interface::class)->activate($this->get_edit_object_id(), Registry::get_config()->get_shop_id());
            $this->_a_view_data['updatenav'] = '1';
        } catch (\Exception $exception) {
            Registry::get_utils_view()->add_error_to_display($exception);
            Registry::get_logger()->error($exception->get_message(), [$exception]);
        }
    }
    /**
     * Deactivate module
     */
    public function deactivate_module(): void
    {
        if (Registry::get_config()->is_demo_shop()) {
            Registry::get_utils_view()->add_error_to_display('MODULE_ACTIVATION_NOT_POSSIBLE_IN_DEMOMODE');
            return;
        }
        try {
            Container_Facade::get(Module_Activation_Bridge_Interface::class)->deactivate($this->get_edit_object_id(), Registry::get_config()->get_shop_id());
            $this->_a_view_data['updatenav'] = '1';
        } catch (\Exception $exception) {
            Registry::get_utils_view()->add_error_to_display($exception);
            Registry::get_logger()->error($exception->get_message(), [$exception]);
        }
    }
}