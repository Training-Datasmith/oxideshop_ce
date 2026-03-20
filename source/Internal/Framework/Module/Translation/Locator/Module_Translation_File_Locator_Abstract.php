<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Locator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
abstract class Module_Translation_File_Locator_Abstract
{
    protected function check_and_add_application_folder(Filesystem $filesystem, string $module_lang_path): string
    {
        $application_folder = Path::join($module_lang_path, 'Application');
        if ($filesystem->exists($application_folder)) {
            return $application_folder;
        }
        return $module_lang_path;
    }
    protected function append_lang_files(array $lang_files, string $module_lang_path): array
    {
        $files = glob(Path::join($module_lang_path, '*_lang.php'));
        if (\is_array($files) && count($files)) {
            foreach ($files as $file) {
                if (!strpos($file, 'cust_lang.php')) {
                    $lang_files[] = $file;
                }
            }
        }
        return $lang_files;
    }
}