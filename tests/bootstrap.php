<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

use OxidEsales\EshopCommunity\Core\Autoload\BackwardsCompatibilityAutoload;
use OxidEsales\EshopCommunity\Core\Autoload\ModuleAutoload;
use OxidEsales\EshopCommunity\Internal\Framework\Env\DotenvLoader;
use OxidEsales\EshopCommunity\Internal\Framework\FileSystem\ProjectRootLocator;
use Symfony\Component\Filesystem\Path;

define('INSTALLATION_ROOT_PATH', (new ProjectRootLocator())->getProjectRoot());
const VENDOR_PATH = INSTALLATION_ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
define('OX_BASE_PATH', Path::join(INSTALLATION_ROOT_PATH, 'source') . DIRECTORY_SEPARATOR);

require VENDOR_PATH . DIRECTORY_SEPARATOR . 'autoload.php';
spl_autoload_register([BackwardsCompatibilityAutoload::class, 'autoload']);
spl_autoload_register([ModuleAutoload::class, 'autoload']);

require_once Path::join(OX_BASE_PATH, 'oxfunctions.php');
require_once Path::join(OX_BASE_PATH, 'overridablefunctions.php');

(new DotenvLoader(INSTALLATION_ROOT_PATH))->loadEnvironmentVariables();

$buildDirectory = getenv('OXID_BUILD_DIRECTORY') ?: '';
if ($buildDirectory === '' || !is_dir($buildDirectory) || !is_writable($buildDirectory)) {
    $buildDirectory = Path::join(INSTALLATION_ROOT_PATH, 'var', 'cache') . DIRECTORY_SEPARATOR;
    putenv('OXID_BUILD_DIRECTORY=' . $buildDirectory);
    $_ENV['OXID_BUILD_DIRECTORY'] = $buildDirectory;
    $_SERVER['OXID_BUILD_DIRECTORY'] = $buildDirectory;
}
if (!is_dir($buildDirectory)) {
    mkdir($buildDirectory, 0777, true);
}

date_default_timezone_set(getenv('OXID_DEFAULT_TIMEZONE') ?: 'Europe/Berlin');
