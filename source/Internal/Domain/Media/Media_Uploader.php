<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Image_Handler_Interface;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
readonly class Media_Uploader implements Media_Uploader_Interface
{
    public function __construct(private Image_Handler_Interface $image_handler)
    {
    }
    public function upload_to(Uploaded_File $uploaded_file, Media_Path $target_path): Media_Path
    {
        $this->image_handler->upload($uploaded_file->get_pathname(), (string) $target_path);
        return $target_path;
    }
}