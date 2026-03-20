<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Locator;

// phpcs:disable
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade\Active_Modules_Data_Provider_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Locator\Frontend_Module_Translation_File_Locator_Interface as LocatorInterface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Translation\Locator\Module_Translation_File_Locator_Abstract as LocatorAbstract;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
// phpcs:enable
class Frontend_Module_Translation_File_Locator extends Locator_Abstract implements Locator_Interface
{
    public function __construct(private readonly Active_Modules_Data_Provider_Interface $active_modules_data_provider, private readonly Filesystem $filesystem)
    {
    }
    public function locate(string $lang): array
    {
        $lang_files = [];
        foreach ($this->active_modules_data_provider->get_module_paths() as $module_lang_path) {
            $module_lang_path = Path::join($this->check_and_add_application_folder($this->filesystem, $module_lang_path), 'translations', $lang);
            $lang_files = $this->append_lang_files($lang_files, $module_lang_path);
        }
        return $lang_files;
    }
}