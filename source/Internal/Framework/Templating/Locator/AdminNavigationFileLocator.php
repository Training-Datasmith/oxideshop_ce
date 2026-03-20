<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Locator;

class Admin_Navigation_File_Locator implements Navigation_File_Locator_Interface
{
    /**
     * @param NavigationFileLocatorInterface[] $menuFileLocators
     */
    public function __construct(private readonly iterable $menu_file_locators = [])
    {
    }
    /**
     * Returns a full path for a given file name.
     *
     * @return array An array of file paths
     *
     * @throws \Exception
     */
    public function locate(): array
    {
        $menu_file_paths = [];
        foreach ($this->menu_file_locators as $locator) {
            $menu_file_paths[] = $locator->locate();
        }
        return array_merge([], ...$menu_file_paths);
    }
}