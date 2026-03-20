<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Template\Locator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade\Active_Modules_Data_Provider_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Locator\Navigation_File_Locator_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Modules_Menu_File_Locator implements Navigation_File_Locator_Interface
{
    private string $file_name = 'menu.xml';
    public function __construct(private readonly Active_Modules_Data_Provider_Interface $active_modules_data_provider, private readonly Filesystem $filesystem)
    {
    }
    /** @inheritDoc */
    public function locate(): array
    {
        $menu_files = [];
        foreach ($this->active_modules_data_provider->get_module_paths() as $module_path) {
            $menu_file_path = Path::join($module_path, $this->file_name);
            if ($this->filesystem->exists($menu_file_path)) {
                $menu_files[] = $menu_file_path;
            }
        }
        return $menu_files;
    }
}