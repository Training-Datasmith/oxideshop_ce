<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Controller;

interface View_Controller_Interface
{
    public function init();
    public function render();
    public function execute_function($function);
    public function set_class_key($class_key);
    public function get_class_key();
    public function set_fnc_name($fnc_name);
    public function get_fnc_name();
    public function set_view_parameters($params = null);
    public function get_view_parameter($key);
    public function set_view_data($view_data = null);
    public function get_view_data();
    public function get_view_id();
    public function get_is_call_for_cache();
    /*
     * @deprecated
     *
     * Added only for BC and will be removed in the next major with 'charset' language string.
     */
    public function get_char_set();
}