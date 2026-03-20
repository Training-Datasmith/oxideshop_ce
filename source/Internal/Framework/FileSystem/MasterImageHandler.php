<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Filesystem\Exception\Io_Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Master_Image_Handler implements Image_Handler_Interface
{
    public function __construct(private readonly Filesystem $filesystem, private readonly Context_Interface $context)
    {
    }
    /** @inheritDoc */
    public function copy(string $source, string $destination): void
    {
        $destination_path = $this->get_absolute_path($destination);
        $destination_directory = \dirname($destination_path);
        $this->filesystem->mkdir($destination_directory, 0744);
        $this->filesystem->copy($source, $destination_path, true);
        $this->filesystem->chmod($destination_path, 0644);
    }
    /** @inheritDoc */
    public function upload(string $source, string $destination): void
    {
        $destination_path = $this->get_absolute_path($destination);
        $destination_directory = \dirname($destination_path);
        $this->filesystem->mkdir($destination_directory, 0744);
        if ($source !== $destination_path && !move_uploaded_file($source, $destination_path)) {
            throw new Io_Exception('Can not move uploaded file');
        }
        $this->filesystem->chmod($destination_path, 0644);
    }
    /** @inheritDoc */
    public function remove(string $path): void
    {
        $this->filesystem->remove($this->get_absolute_path($path));
    }
    /** @inheritDoc */
    public function exists(string $path): bool
    {
        return $this->filesystem->exists($this->get_absolute_path($path));
    }
    private function get_absolute_path(string $path): string
    {
        return Path::join($this->context->get_source_path(), $path);
    }
}