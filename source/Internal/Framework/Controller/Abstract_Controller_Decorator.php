<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Controller;

abstract class Abstract_Controller_Decorator implements View_Controller_Interface
{
    public function __construct(protected readonly View_Controller_Interface $controller)
    {
    }
    public function init(): void
    {
        $this->controller->init();
    }
    public function render()
    {
        return $this->controller->render();
    }
    public function get_fnc_name()
    {
        return $this->controller->get_fnc_name();
    }
    public function execute_function($function): void
    {
        $this->controller->execute_function($function);
    }
    public function get_is_call_for_cache()
    {
        return $this->controller->get_is_call_for_cache();
    }
    public function get_class_key()
    {
        return $this->controller->get_class_key();
    }
    public function get_view_data()
    {
        return $this->controller->get_view_data();
    }
    public function set_view_data($view_data = null): void
    {
        $this->controller->set_view_data($view_data);
    }
    public function get_view_id()
    {
        return $this->controller->get_view_id();
    }
    public function get_char_set()
    {
        return $this->controller->get_char_set();
    }
    public function set_class_key($class_key): void
    {
        $this->controller->set_class_key($class_key);
    }
    public function set_fnc_name($fnc_name): void
    {
        $this->controller->set_fnc_name($fnc_name);
    }
    public function set_view_parameters($params = null): void
    {
        $this->controller->set_view_parameters($params);
    }
    public function get_view_parameter($key)
    {
        return $this->controller->get_view_parameter($key);
    }
}