<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Edition;

use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Directory_Not_Existent_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Project_Directories_Locator;
use Symfony\Component\Filesystem\Path;
readonly class Edition_Directories_Locator
{
    public function get_edition_root_path(Edition $edition): string
    {
        $project_directories_locator = new Project_Directories_Locator();
        $path = Path::join($project_directories_locator->get_vendor_path(), Edition_Paths::from($edition->value)->get_vendor_folder_name(), Edition_Paths::from($edition->value)->get_project_folder_name());
        if (!is_dir($path)) {
            if ($edition->is_community_edition()) {
                return $project_directories_locator->get_root_path();
            }
            throw new Directory_Not_Existent_Exception("Root directory for {$edition->name} does not exist!");
        }
        return $path;
    }
    public function get_edition_source_path(Edition $edition): string
    {
        return Path::join($this->get_edition_root_path($edition), Edition_Paths::from($edition->value)->get_source_folder_name());
    }
}