<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
use Oxid_Esales\Eshop\Core\Exception\File_Exception;
use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
use Oxid_Esales\Eshop\Core\Registry;
if (!defined('OX_IS_ADMIN')) {
    define('OX_IS_ADMIN', true);
}
if (!defined('OX_ADMIN_DIR')) {
    define('OX_ADMIN_DIR', __DIR__);
}
require_once __DIR__ . '/../bootstrap.php';
// processing ..
$bl_ajax_call = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
if ($bl_ajax_call) {
    $my_config = Registry::get_config();
    $my_config->init();
    // Includes Utility module.
    $s_util_module = $my_config->get_config_param('sUtilModule');
    if ($s_util_module && file_exists(get_shop_base_path() . 'modules/' . $s_util_module)) {
        include_once get_shop_base_path() . 'modules/' . $s_util_module;
    }
    $my_config->set_config_param('blAdmin', true);
    // authorization
    if (!(Registry::get_session()->check_session_challenge() && count(Registry::get_utils_server()->get_ox_cookie()) && Registry::get_utils()->check_access_rights())) {
        header('location:index.php');
        Registry::get_utils()->show_message_and_exit('');
    }
    if ($s_container = Registry::get_request()->get_request_parameter('container')) {
        $s_container = strtolower(trim(basename((string) $s_container)));
        try {
            // Controller name for ajax class is automatically done from the request.
            // Request comes from the same named class without _ajax.
            $ajax_container_class_name = $s_container . '_ajax';
            // Ensures that the right name is returned when a module introduce an ajax class.
            $container_class = Registry::get_controller_class_name_resolver()->get_class_name_by_id($ajax_container_class_name);
            $o_ajax_component = ox_new($container_class);
        } catch (System_Component_Exception) {
            $o_ex = new File_Exception();
            $o_ex->set_message('EXCEPTION_FILENOTFOUND' . ' ' . $ajax_container_class_name);
            throw $o_ex;
        }
        $o_ajax_component->set_name($s_container);
        $o_ajax_component->process_request(Registry::get_request()->get_request_parameter('fnc'));
    }
    $my_config->page_close();
}