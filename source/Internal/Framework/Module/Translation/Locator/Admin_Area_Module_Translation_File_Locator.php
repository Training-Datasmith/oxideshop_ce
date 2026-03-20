<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Locator;

// phpcs:disable
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade\Modules_Data_Provider_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Locator\Admin_Area_Module_Translation_File_Locator_Interface as LocatorInterface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Locator\Module_Translation_File_Locator_Abstract as LocatorAbstract;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
// phpcs:enable
class Admin_Area_Module_Translation_File_Locator extends Locator_Abstract implements Locator_Interface
{
    public function __construct(private readonly Modules_Data_Provider_Interface $modules_data_provider, private readonly Filesystem $filesystem, private readonly string $admin_theme_name)
    {
    }
    public function locate(string $lang): array
    {
        $lang_files = [];
        foreach ($this->modules_data_provider->get_module_paths() as $module_lang_path) {
            $module_lang_path = Path::join($this->check_and_add_application_folder($this->filesystem, $module_lang_path), 'views', $this->admin_theme_name, $lang);
            $lang_files = $this->append_lang_files($lang_files, $module_lang_path);
            $lang_files = $this->append_module_options_file($lang_files, $module_lang_path);
        }
        return $lang_files;
    }
    private function append_module_options_file(array $lang_files, string $module_lang_path): array
    {
        $lang_file_path = Path::join($module_lang_path, 'module_options.php');
        if ($this->filesystem->exists($lang_file_path)) {
            $lang_files[] = $lang_file_path;
        }
        return $lang_files;
    }
}