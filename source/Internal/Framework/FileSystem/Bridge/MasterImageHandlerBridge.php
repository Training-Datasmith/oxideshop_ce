<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Image_Handler_Interface;
class Master_Image_Handler_Bridge implements Master_Image_Handler_Bridge_Interface
{
    public function __construct(private readonly Image_Handler_Interface $master_image_handler)
    {
    }
    /** @inheritdoc */
    public function copy(string $source, string $destination): void
    {
        $this->master_image_handler->copy($source, $destination);
    }
    /** @inheritdoc */
    public function upload(string $source, string $destination): void
    {
        $this->master_image_handler->upload($source, $destination);
    }
    /** @inheritdoc */
    public function remove(string $path): void
    {
        $this->master_image_handler->remove($path);
    }
    /** @inheritdoc */
    public function exists(string $path): bool
    {
        return $this->master_image_handler->exists($path);
    }
}