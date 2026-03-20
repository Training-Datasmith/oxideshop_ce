<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
use Oxid_Esales\Eshop_Community\Core\Autoload\Backwards_Compatibility_Autoload;
use Oxid_Esales\Eshop_Community\Core\Autoload\Module_Autoload;
use Oxid_Esales\Eshop_Community\Core\Exception\Exception_Handler;
use Oxid_Esales\Eshop_Community\Internal\Framework\Env\Dotenv_Loader;
define('INSTALLATION_ROOT_PATH', dirname(__DIR__));
const OX_BASE_PATH = INSTALLATION_ROOT_PATH . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR;
const VENDOR_PATH = INSTALLATION_ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
require_once VENDOR_PATH . 'autoload.php';
(new Dotenv_Loader(INSTALLATION_ROOT_PATH))->load_environment_variables();
if (!function_exists('oxTriggerOfflinePageDisplay')) {
    function ox_trigger_offline_page_display(): void
    {
        if (strtolower(PHP_SAPI) !== 'cli') {
            header('HTTP/1.1 500 Internal Server Error');
            header('Connection: close');
            $offline_file = OX_BASE_PATH . 'offline.html';
            if (is_readable($offline_file)) {
                echo file_get_contents($offline_file);
            }
        }
    }
}
/** For errors not caught by the application. */
register_shutdown_function(static function (): void {
    $last_error = error_get_last();
    $fatal_errors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
    if ($last_error && in_array($last_error['type'], $fatal_errors, true)) {
        file_put_contents(OX_BASE_PATH . 'log' . DIRECTORY_SEPARATOR . 'oxideshop.log', "Application has shut down with an UNCAUGHT ERROR: '" . print_r($last_error, true) . "'\n", FILE_APPEND);
        if (!filter_var(getenv('OXID_DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN)) {
            ox_trigger_offline_page_display();
        }
        if ($last_error['type'] === E_ERROR) {
            setcookie(name: 'sid', path: '/');
            setcookie(name: 'admin_sid', path: '/');
        }
    }
});
spl_autoload_register(Backwards_Compatibility_Autoload::autoload(...));
spl_autoload_register(Module_Autoload::autoload(...));
/** Set exception handler before including modules/functions.php, so it can be overwritten by shop operators. */
set_exception_handler([new Exception_Handler(filter_var(getenv('OXID_DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN)), 'handleUncaughtException']);
require_once OX_BASE_PATH . 'oxfunctions.php';
if (is_readable(OX_BASE_PATH . 'modules/functions.php')) {
    include OX_BASE_PATH . 'modules/functions.php';
}
require_once OX_BASE_PATH . 'overridablefunctions.php';
ini_set('session.name', 'sid');
ini_set('session.use_cookies', 0);
ini_set('session.use_trans_sid', 0);
ini_set('url_rewriter.tags', '');
date_default_timezone_set(getenv('OXID_DEFAULT_TIMEZONE') ?: 'Europe/Berlin');