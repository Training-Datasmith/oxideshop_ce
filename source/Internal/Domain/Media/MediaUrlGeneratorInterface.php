<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
interface Media_Url_Generator_Interface
{
    public function generate_sized_image_url(Media $media, string $size): string;
}