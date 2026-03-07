<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\FileSystem;

/**
 * Wrapper for actions related to file system.
 *
 * @internal Do not make a module extension for this class.
 */
class FileSystem
{
    /**
     * Connect all parameters with backslash to single path.
     * Ensure that no double backslash appears if parameter already ends with backslash.
     */
    public function combinePaths(): string
    {
        $pathElements = func_get_args();
        foreach ($pathElements as $key => $pathElement) {
            $pathElements[$key] = rtrim((string) $pathElement, DIRECTORY_SEPARATOR);
        }

        return implode(DIRECTORY_SEPARATOR, $pathElements);
    }

    /**
     * Check if file exists and is readable
     *
     * @param string $filePath
     */
    public function isReadable($filePath): bool
    {
        return (is_file($filePath) && is_readable($filePath));
    }
}
