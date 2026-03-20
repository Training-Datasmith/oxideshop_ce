<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System;

use function dirname;
use function is_dir;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
class Project_Root_Locator
{
    public function get_project_root(): string
    {
        $path = __DIR__;
        while (!is_dir(Path::join($path, 'vendor'))) {
            if ($this->is_filesystem_root_dir($path)) {
                throw new RuntimeException('Can not determine project root directory!');
            }
            $path = $this->get_parent_dir($path);
        }
        return $path;
    }
    private function is_filesystem_root_dir(string $path): bool
    {
        return $path === $this->get_parent_dir($path);
    }
    private function get_parent_dir(string $path): string
    {
        return dirname($path);
    }
}