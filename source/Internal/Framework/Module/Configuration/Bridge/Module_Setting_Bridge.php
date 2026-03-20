<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Event\Setting_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
/**
 * @deprecated use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface
 */
class Module_Setting_Bridge implements Module_Setting_Bridge_Interface
{
    public function __construct(private readonly Context_Interface $context, private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    /**
     * @param mixed $value
     */
    public function save(string $name, $value, string $module_id): void
    {
        $module_configuration = $this->module_configuration_dao->get($module_id, $this->context->get_current_shop_id());
        $setting = $module_configuration->get_module_setting($name);
        $setting->set_value($value);
        $this->module_configuration_dao->save($module_configuration, $this->context->get_current_shop_id());
        $this->event_dispatcher->dispatch(new Setting_Changed_Event($name, $this->context->get_current_shop_id(), $module_id));
    }
    /**
     * @return mixed
     */
    public function get(string $name, string $module_id)
    {
        $configuration = $this->module_configuration_dao->get($module_id, $this->context->get_current_shop_id());
        return $configuration->get_module_setting($name)->get_value();
    }
}