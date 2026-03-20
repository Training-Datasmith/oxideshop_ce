<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
interface Media_Uploader_Interface
{
    public function upload_to(Uploaded_File $uploaded_file, Media_Path $target_path): Media_Path;
}