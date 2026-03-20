<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\File_System;

/**
 * Wrapper for actions related to file system.
 *
 * @internal Do not make a module extension for this class.
 */
class File_System
{
    /**
     * Connect all parameters with backslash to single path.
     * Ensure that no double backslash appears if parameter already ends with backslash.
     */
    public function combine_paths(): string
    {
        $path_elements = func_get_args();
        foreach ($path_elements as $key => $path_element) {
            $path_elements[$key] = rtrim((string) $path_element, DIRECTORY_SEPARATOR);
        }
        return implode(DIRECTORY_SEPARATOR, $path_elements);
    }
    /**
     * Check if file exists and is readable
     *
     * @param string $filePath
     */
    public function is_readable($file_path): bool
    {
        return is_file($file_path) && is_readable($file_path);
    }
}