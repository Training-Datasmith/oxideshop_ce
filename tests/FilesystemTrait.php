<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests;

use OxidEsales\EshopCommunity\Internal\Framework\FileSystem\ProjectRootLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

trait FilesystemTrait
{
    private Filesystem $filesystem;
    private string $varTestPath = '';

    public function createTestVarDirectory(): void
    {
        $shopRootPath = (new ProjectRootLocator())->getProjectRoot();
        $this->filesystem = new Filesystem();
        $varPath = Path::join($shopRootPath, 'var');
        $this->varTestPath = Path::join($shopRootPath, getenv('OXID_VAR_DIRECTORY'));

        $this->filesystem->mirror($varPath, $this->varTestPath);
    }

    public function deleteTestVarDirectory(): void
    {
        $this->filesystem->remove($this->varTestPath);
    }
}
