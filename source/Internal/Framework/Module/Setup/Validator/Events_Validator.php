<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Exception\Module_Setting_Not_Valid_Exception;
class Events_Validator implements Module_Configuration_Validator_Interface
{
    private array $valid_events = ['onActivate', 'onDeactivate'];
    /**
     * There is another service for syntax validation and we won't validate syntax in this method.
     *
     *
     * @throws ModuleSettingNotValidException
     */
    public function validate(Module_Configuration $configuration, int $shop_id): void
    {
        if ($configuration->has_events()) {
            $events = [];
            foreach ($configuration->get_events() as $event) {
                $events[$event->get_action()] = $event->get_method();
            }
            foreach ($this->valid_events as $valid_event_name) {
                if (\array_key_exists($valid_event_name, $events)) {
                    $this->check_if_method_is_callable($events[$valid_event_name]);
                }
            }
        }
    }
    /**
     * @throws ModuleSettingNotValidException
     */
    private function check_if_method_is_callable(string $method): void
    {
        if (!\is_callable($method)) {
            throw new Module_Setting_Not_Valid_Exception('The method ' . $method . ' is not callable.');
        }
    }
}