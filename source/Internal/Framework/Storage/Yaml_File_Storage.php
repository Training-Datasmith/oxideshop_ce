<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Storage;

use Symfony\Component\Config\Exception\File_Locator_File_Not_Found_Exception;
use Symfony\Component\Config\File_Locator_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Lock_Factory;
use Symfony\Component\Yaml\Yaml;
class Yaml_File_Storage implements Array_Storage_Interface
{
    public function __construct(private readonly File_Locator_Interface $file_locator, private readonly string $file_path, private readonly Lock_Factory $lock_factory, private readonly Filesystem $filesystem_service)
    {
    }
    public function get(): array
    {
        $file_content = file_get_contents($this->get_located_file_path());
        $yaml = Yaml::parse($file_content);
        return $yaml ?? [];
    }
    public function save(array $data): void
    {
        $lock = $this->lock_factory->create_lock($this->get_lock_id());
        if ($lock->acquire(true)) {
            try {
                file_put_contents($this->get_located_file_path(), Yaml::dump($data, 10, 2));
            } finally {
                $lock->release();
            }
        }
    }
    private function get_located_file_path(): string
    {
        try {
            $file_path = $this->file_locator->locate($this->file_path);
        } catch (File_Locator_File_Not_Found_Exception) {
            $this->create_file_directory();
            $this->create_file();
            $file_path = $this->file_locator->locate($this->file_path);
        }
        return $file_path;
    }
    /**
     * Creates file directory if it doesn't exist.
     */
    private function create_file_directory(): void
    {
        if (!$this->filesystem_service->exists(\dirname($this->file_path))) {
            $this->filesystem_service->mkdir(\dirname($this->file_path));
        }
    }
    /**
     * Creates file.
     */
    private function create_file(): void
    {
        $this->filesystem_service->touch($this->file_path);
    }
    private function get_lock_id(): string
    {
        return md5($this->file_path);
    }
}