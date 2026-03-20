<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Storage;

use Symfony\Component\Config\File_Locator_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Lock_Factory;
class Yaml_File_Storage_Factory implements File_Storage_Factory_Interface
{
    public function __construct(private readonly File_Locator_Interface $file_locator, private readonly Lock_Factory $lock_factory, private readonly Filesystem $filesystem_service)
    {
    }
    public function create(string $file_path): Array_Storage_Interface
    {
        return new Yaml_File_Storage($this->file_locator, $file_path, $this->lock_factory, $this->filesystem_service);
    }
}