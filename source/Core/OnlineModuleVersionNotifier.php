<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\{Module_Configuration_Data_Mapper_Bridge_Interface, Shop_Configuration_Dao_Bridge_Interface};
use stdClass;
/**
 * Performs Online Module Version Notifier check.
 *
 * The Online Module Version Notification is used for checking if newer versions of modules are available.
 * Will be used by the upcoming online one click installer.
 * Is still under development
 * - still changes at the remote server are necessary
 * - therefore ignoring the results for now
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_Module_Version_Notifier
{
    /** @var \OxidEsales\Eshop\Core\OnlineModuleVersionNotifierCaller */
    private $_o_caller;
    public function __construct(\Oxid_Esales\Eshop\Core\Online_Module_Version_Notifier_Caller $o_caller)
    {
        $this->_o_caller = $o_caller;
    }
    /**
     * Perform Online Module version Notification. Returns result
     */
    public function version_notify(): void
    {
        if (true === \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('preventModuleVersionNotify')) {
            return;
        }
        $o_omn_caller = $this->get_online_module_notifier_caller();
        $o_omn_caller->do_request($this->form_request());
    }
    /**
     * @return mixed[]
     */
    protected function prepare_modules_information(): array
    {
        $shop_configuration = Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get();
        $prepared_modules = [];
        foreach ($shop_configuration->get_module_configurations() as $module_configuration) {
            $prepared_modules[] = Container_Facade::get(Module_Configuration_Data_Mapper_Bridge_Interface::class)->to_data($module_configuration);
        }
        return $prepared_modules;
    }
    /**
     * Send request message to Online Module Version Notifier web service.
     */
    protected function form_request(): \Oxid_Esales\Eshop\Core\Online_Modules_Notifier_Request
    {
        $o_request_params = new \Oxid_Esales\Eshop\Core\Online_Modules_Notifier_Request();
        $o_request_params->modules = new stdClass();
        $o_request_params->modules->module = $this->prepare_modules_information();
        return $o_request_params;
    }
    /**
     * Returns caller.
     *
     * @return \OxidEsales\Eshop\Core\OnlineModuleVersionNotifierCaller
     */
    protected function get_online_module_notifier_caller()
    {
        return $this->_o_caller;
    }
}