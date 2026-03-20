<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Type;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
class Data_Mapper implements Data_Mapper_Interface
{
    public function to_data(Media $media): array
    {
        return ['id' => (string) $media->get_id(), 'path' => (string) $media->get_media_path(), 'type' => (string) $media->get_media_type()];
    }
    public function from_data(array $data): Media
    {
        return new Media(Id::from_string($data['id']), new Media_Path($data['path']), new Media_Type($data['type']));
    }
}