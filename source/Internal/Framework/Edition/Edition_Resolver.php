<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Edition;

use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Directory_Not_Existent_Exception;
readonly class Edition_Resolver
{
    public function get_edition(): Edition
    {
        $edition_directories_locator = new Edition_Directories_Locator();
        try {
            $edition_directories_locator->get_edition_root_path(Edition::Enterprise);
            return Edition::Enterprise;
        } catch (Directory_Not_Existent_Exception) {
            try {
                $edition_directories_locator->get_edition_root_path(Edition::Professional);
                return Edition::Professional;
            } catch (Directory_Not_Existent_Exception) {
                return Edition::Community;
            }
        }
    }
}