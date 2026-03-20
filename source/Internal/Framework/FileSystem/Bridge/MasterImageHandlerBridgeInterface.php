<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Bridge;

interface Master_Image_Handler_Bridge_Interface
{
    public function copy(string $source, string $destination): void;
    public function upload(string $source, string $destination): void;
    public function remove(string $path): void;
    public function exists(string $path): bool;
}