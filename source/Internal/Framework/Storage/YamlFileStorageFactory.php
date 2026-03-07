<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Storage;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;

class YamlFileStorageFactory implements FileStorageFactoryInterface
{
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly LockFactory $lockFactory,
        private readonly Filesystem $filesystemService
    ) {
    }

    public function create(string $filePath): ArrayStorageInterface
    {
        return new YamlFileStorage(
            $this->fileLocator,
            $filePath,
            $this->lockFactory,
            $this->filesystemService
        );
    }
}
